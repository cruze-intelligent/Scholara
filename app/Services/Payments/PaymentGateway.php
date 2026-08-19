<?php

namespace App\Services\Payments;

use App\Models\Invoice;

interface PaymentGateway
{
    /**
     * Charge the given amount against an invoice and return the gateway's
     * raw response payload.
     *
     * @return array{success: bool, reference: string, raw: array}
     */
    public function charge(Invoice $invoice, float $amount, string $method): array;
}
