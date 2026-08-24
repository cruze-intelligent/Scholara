<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Notifications\PaymentReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Bursar-facing invoice management — Invoice has no BelongsToSchool scope of its own (it's tied
 * to a school only via student_id), so every query here filters through the student relation
 * explicitly, same pattern as MedicationAdministrationController/ClinicVisitController.
 */
class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = Invoice::with('student', 'payments')
            ->whereHas('student', fn ($q) => $q->where('school_id', $request->user()->school_id))
            ->latest('due_date')
            ->paginate(20);

        return view('invoices.index', compact('invoices'));
    }

    public function create(): View
    {
        return view('invoices.create', [
            'students' => Student::orderBy('first_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => [
                'required',
                Rule::exists('students', 'id')->where('school_id', $request->user()->school_id),
            ],
            'term' => ['required', 'string', 'max:50'],
            'amount_due' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
        ]);

        Invoice::create([...$validated, 'status' => 'unpaid']);

        return redirect()->route('invoices.index')->with('status', 'Invoice created.');
    }

    public function show(Request $request, Invoice $invoice): View
    {
        // $invoice->student can itself come back null here: Student has its own BelongsToSchool
        // scope, so loading a cross-school invoice's student relation is already filtered out —
        // treat that the same as "not this bursar's invoice" rather than crashing on ->school_id.
        abort_unless($invoice->student?->school_id === $request->user()->school_id, 403);

        $invoice->load('student', 'payments');

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Bursar recording a payment taken outside DGateway (cash handed over, a bank deposit slip)
     * — these complete immediately since the bursar is confirming money already received, unlike
     * the guardian card/mobile-money checkout which starts "pending" and resolves via webhook.
     */
    public function recordPayment(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->student?->school_id === $request->user()->school_id, 403);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,bank'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $payment = $invoice->payments()->create([
            ...$validated,
            'currency' => config('services.dgateway.default_currency'),
            'status' => Payment::STATUS_COMPLETED,
            'provider' => 'manual',
            'paid_at' => now(),
        ]);

        $invoice->syncPaymentStatus();

        Notification::send($invoice->student->guardians->map->user->filter(), new PaymentReceived($payment));

        return redirect()->route('invoices.show', $invoice)->with('status', 'Payment recorded.');
    }
}
