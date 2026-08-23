<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeGuardianWithInvoice(): array
    {
        Role::findOrCreate('parent');

        $school = School::factory()->create();
        $student = Student::factory()->for($school)->create();
        $guardianUser = User::factory()->create(['school_id' => $school->id]);
        $guardianUser->assignRole('parent');
        $guardian = Guardian::create(['user_id' => $guardianUser->id, 'relationship_to_student' => 'mother']);
        $guardian->students()->attach($student->id);

        $invoice = Invoice::create([
            'student_id' => $student->id,
            'term' => 'Term 2 2026',
            'amount_due' => 50000,
            'due_date' => now()->addWeek(),
            'status' => 'unpaid',
        ]);

        return [$guardianUser, $invoice];
    }

    public function test_guardian_can_pay_by_mobile_money_and_the_webhook_completes_it(): void
    {
        Config::set('services.dgateway.key', 'dgw_test_fake');
        Config::set('services.dgateway.webhook_secret', 'whsec_test');

        [$guardianUser, $invoice] = $this->makeGuardianWithInvoice();

        Http::fake([
            '*/v1/payments/collect' => Http::response([
                'data' => ['reference' => 'dgw_abc123', 'status' => 'pending', 'provider' => 'iotec'],
            ], 200),
        ]);

        $response = $this->actingAs($guardianUser)->post(route('invoices.pay.store', $invoice), [
            'method' => 'mobile_money',
            'phone_number' => '0771234567',
        ]);

        $payment = Payment::where('reference', 'dgw_abc123')->firstOrFail();
        $response->assertRedirect(route('invoices.pay.status', [$invoice, $payment]));
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertSame('unpaid', $invoice->fresh()->status);

        $webhookBody = json_encode([
            'event' => 'transaction.completed',
            'data' => ['reference' => 'dgw_abc123', 'status' => 'completed', 'provider' => 'iotec'],
        ]);
        $signature = hash_hmac('sha256', $webhookBody, 'whsec_test');

        $webhookResponse = $this->call('POST', route('webhooks.dgateway'), [], [], [], [
            'HTTP_X-DGateway-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $webhookBody);

        $webhookResponse->assertOk();
        $this->assertSame(Payment::STATUS_COMPLETED, $payment->fresh()->status);
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_webhook_rejects_a_bad_signature(): void
    {
        Config::set('services.dgateway.webhook_secret', 'whsec_test');

        $response = $this->call('POST', route('webhooks.dgateway'), [], [], [], [
            'HTTP_X-DGateway-Signature' => 'not-the-right-signature',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['event' => 'transaction.completed', 'data' => ['reference' => 'whatever']]));

        $response->assertStatus(401);
    }

    public function test_guardian_cannot_pay_another_guardians_invoice(): void
    {
        Role::findOrCreate('parent');

        [, $invoice] = $this->makeGuardianWithInvoice();

        $school = School::factory()->create();
        $otherGuardianUser = User::factory()->create(['school_id' => $school->id]);
        $otherGuardianUser->assignRole('parent');
        Guardian::create(['user_id' => $otherGuardianUser->id, 'relationship_to_student' => 'father']);

        $this->actingAs($otherGuardianUser)->get(route('invoices.pay', $invoice))->assertForbidden();
    }
}
