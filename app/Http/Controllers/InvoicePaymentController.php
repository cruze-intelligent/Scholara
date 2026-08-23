<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoicePaymentController extends Controller
{
    public function create(Request $request, Invoice $invoice): View
    {
        $this->authorizeGuardian($request, $invoice);

        return view('invoices.pay', ['invoice' => $invoice]);
    }

    public function store(Request $request, Invoice $invoice, PaymentGateway $gateway): RedirectResponse
    {
        $this->authorizeGuardian($request, $invoice);

        $validated = $request->validate([
            'method' => ['required', 'in:mobile_money,card'],
            'phone_number' => ['required_if:method,mobile_money', 'nullable', 'string'],
        ]);

        $remaining = $invoice->amount_due - $invoice->payments()->where('status', Payment::STATUS_COMPLETED)->sum('amount');

        $result = $gateway->collect($invoice, (float) $remaining, $validated['method'], $validated['phone_number'] ?? null);

        $payment = $invoice->payments()->create([
            'amount' => $remaining,
            'currency' => config('services.dgateway.default_currency'),
            'method' => $validated['method'],
            'reference' => $result['reference'],
            'status' => $result['status'],
            'provider' => $result['provider'] ?? null,
            'paid_at' => $result['status'] === Payment::STATUS_COMPLETED ? now() : null,
            'gateway_response' => $result['raw'],
        ]);

        if ($payment->status === Payment::STATUS_COMPLETED) {
            $invoice->syncPaymentStatus();
        }

        return redirect()->route('invoices.pay.status', [$invoice, $payment]);
    }

    public function status(Request $request, Invoice $invoice, Payment $payment, PaymentGateway $gateway): View
    {
        $this->authorizeGuardian($request, $invoice);

        return view('invoices.payment-status', ['invoice' => $invoice, 'payment' => $payment]);
    }

    /**
     * Polled every 5s by invoices/payment-status.blade.php. Re-checks with the gateway directly
     * rather than only trusting the webhook — the webhook can't reach a local/unexposed dev
     * server, so this is the path that actually resolves "pending" during local testing.
     */
    public function statusCheck(Request $request, Invoice $invoice, Payment $payment, PaymentGateway $gateway): JsonResponse
    {
        $this->authorizeGuardian($request, $invoice);

        if ($payment->status === Payment::STATUS_PENDING) {
            $result = $gateway->checkStatus($payment->reference);

            if ($result['status'] !== Payment::STATUS_PENDING) {
                $payment->update([
                    'status' => $result['status'],
                    'paid_at' => $result['status'] === Payment::STATUS_COMPLETED ? now() : null,
                    'gateway_response' => $result['raw'],
                ]);

                if ($payment->status === Payment::STATUS_COMPLETED) {
                    $invoice->syncPaymentStatus();
                }
            }
        }

        return response()->json(['status' => $payment->status]);
    }

    private function authorizeGuardian(Request $request, Invoice $invoice): void
    {
        $guardian = $request->user()->guardian;

        abort_unless($guardian && $guardian->students()->where('students.id', $invoice->student_id)->exists(), 403);
    }
}
