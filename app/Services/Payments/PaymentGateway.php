<?php

namespace App\Services\Payments;

use App\Models\Invoice;

interface PaymentGateway
{
    /**
     * Start a charge against an invoice via mobile money or card. Real gateways don't settle
     * synchronously — the caller gets a reference back in "pending" status and learns the final
     * result via webhook (see DGatewayWebhookController) or by polling checkStatus().
     *
     * @return array{reference: string, status: string, provider: ?string, raw: array}
     */
    public function collect(Invoice $invoice, float $amount, string $method, ?string $phoneNumber = null): array;

    /**
     * Re-check a previously started charge's current status — used as the client-facing poll
     * target and as a fallback for environments (e.g. local dev) where the webhook can't reach us.
     *
     * @return array{status: string, raw: array}
     */
    public function checkStatus(string $reference): array;
}
