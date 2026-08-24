<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Notifications\PaymentReceived;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class DGatewayWebhookController extends Controller
{
    /**
     * DGateway calls this once a collect request initiated via PaymentGateway::collect()
     * finishes (docs/DECISIONS.md). It always gets a 200 quickly, per DGateway's own retry
     * contract — processing here is cheap enough not to need queuing.
     */
    public function handle(Request $request): Response
    {
        $secret = config('services.dgateway.webhook_secret');
        $signature = $request->header('X-DGateway-Signature', '');

        if (! $secret || ! $signature || ! hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature)) {
            Log::warning('DGateway webhook rejected: bad or missing signature');

            return response('', 401);
        }

        $event = $request->input('event');
        $data = $request->input('data', []);
        $reference = $data['reference'] ?? null;

        if (! $reference || ! in_array($event, ['transaction.completed', 'transaction.failed'], true)) {
            // Subscription events and anything else aren't handled — Scholara only uses one-time
            // collect for invoices, no DGateway subscriptions (see docs/DECISIONS.md).
            return response('', 200);
        }

        $payment = Payment::where('reference', $reference)->first();

        if (! $payment) {
            Log::warning('DGateway webhook for unknown payment reference', ['reference' => $reference]);

            return response('', 200);
        }

        if ($payment->status !== Payment::STATUS_PENDING) {
            // Already processed — DGateway retries deliveries 2-3 times per their docs.
            return response('', 200);
        }

        $payment->update([
            'status' => $event === 'transaction.completed' ? Payment::STATUS_COMPLETED : Payment::STATUS_FAILED,
            'provider' => $data['provider'] ?? $payment->provider,
            'paid_at' => $event === 'transaction.completed' ? now() : null,
            'gateway_response' => $data,
        ]);

        if ($payment->status === Payment::STATUS_COMPLETED) {
            $payment->invoice->syncPaymentStatus();

            Notification::send($payment->invoice->student->guardians->map->user->filter(), new PaymentReceived($payment));
        }

        return response('', 200);
    }
}
