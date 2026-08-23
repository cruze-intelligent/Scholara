<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * Stands in for DGateway until a real API key is set (see docs/DECISIONS.md) — always
 * "completes" immediately so the Financial Center's checkout flow can be built and clicked
 * through now, with no real charge made and no external network call.
 */
class FakePaymentGateway implements PaymentGateway
{
    public function collect(Invoice $invoice, float $amount, string $method, ?string $phoneNumber = null): array
    {
        return [
            'reference' => 'FAKE-'.Str::upper(Str::random(10)),
            'status' => Payment::STATUS_COMPLETED,
            'provider' => 'fake',
            'raw' => ['note' => 'FakePaymentGateway — no real charge made', 'method' => $method],
        ];
    }

    public function checkStatus(string $reference): array
    {
        return [
            'status' => Payment::STATUS_COMPLETED,
            'raw' => ['note' => 'FakePaymentGateway — no real charge made'],
        ];
    }
}
