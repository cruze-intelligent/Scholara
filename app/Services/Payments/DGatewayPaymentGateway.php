<?php

namespace App\Services\Payments;

use App\Models\Invoice;

/**
 * Real DGateway (https://dgateway.desispay.com) implementation — a single API that routes
 * "mobile_money" to Iotec/Relworx and "card" to Stripe based on currency, so the parent-facing
 * checkout only ever shows those two options and never DGateway itself. See docs/DECISIONS.md.
 */
class DGatewayPaymentGateway implements PaymentGateway
{
    /**
     * DGateway's own transaction states map straight onto ours except this one — "cancelled" is
     * theirs, not documented on our Payment model, so it's folded into "failed".
     */
    private const STATUS_MAP = [
        'pending' => 'pending',
        'completed' => 'completed',
        'failed' => 'failed',
        'cancelled' => 'failed',
    ];

    public function __construct(
        private readonly DGatewayClient $client,
        private readonly string $defaultCurrency,
    ) {
    }

    public function collect(Invoice $invoice, float $amount, string $method, ?string $phoneNumber = null): array
    {
        $payload = [
            // Amounts are the smallest currency unit (e.g. whole UGX, USD cents) — see
            // docs/DECISIONS.md for why school fees are treated as zero-decimal UGX here.
            'amount' => (int) round($amount),
            'currency' => $this->defaultCurrency,
            'description' => "Invoice #{$invoice->id} ({$invoice->term})",
            'callback_url' => route('webhooks.dgateway'),
            'metadata' => ['invoice_id' => $invoice->id],
        ];

        if ($method === 'mobile_money') {
            $payload['phone_number'] = $this->normalizePhone($phoneNumber);
        }

        $response = $this->client->post('/v1/payments/collect', $payload);
        $data = $response['data'] ?? [];

        return [
            'reference' => $data['reference'],
            'status' => self::STATUS_MAP[$data['status']] ?? 'pending',
            'provider' => $data['provider'] ?? null,
            'raw' => $data,
        ];
    }

    public function checkStatus(string $reference): array
    {
        // Not a typo: DGateway's status-check endpoint really is under /v1/webhooks/verify,
        // not something like /v1/transactions/{reference}/status — see docs/DECISIONS.md.
        $response = $this->client->post('/v1/webhooks/verify', ['reference' => $reference]);
        $data = $response['data'] ?? [];

        return [
            'status' => self::STATUS_MAP[$data['status']] ?? 'pending',
            'raw' => $data,
        ];
    }

    private function normalizePhone(?string $phoneNumber): ?string
    {
        if ($phoneNumber === null) {
            return null;
        }

        // DGateway only accepts digits-only 256XXXXXXXXX or 0XXXXXXXXX — strip anything else
        // (spaces, dashes, a leading +) rather than rejecting it ourselves and let DGateway's
        // own INVALID_PHONE error handle genuinely malformed input.
        return preg_replace('/[^0-9]/', '', $phoneNumber);
    }
}
