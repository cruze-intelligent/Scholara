<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use Illuminate\Support\Str;

/**
 * Stands in for SchoolPay / MTN & Airtel mobile money until a merchant
 * account is set up — see docs/ARCHITECTURE.md. Always "succeeds" so the
 * Financial Center can be built and tested now.
 */
class FakePaymentGateway implements PaymentGateway
{
    public function charge(Invoice $invoice, float $amount, string $method): array
    {
        return [
            'success' => true,
            'reference' => 'FAKE-'.Str::upper(Str::random(10)),
            'raw' => ['note' => 'FakePaymentGateway — no real charge made', 'method' => $method],
        ];
    }
}
