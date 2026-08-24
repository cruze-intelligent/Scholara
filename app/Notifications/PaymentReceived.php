<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification
{
    public function __construct(public Payment $payment) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invoice = $this->payment->invoice;

        return (new MailMessage)
            ->subject("Payment received — {$invoice->student->full_name}")
            ->line("We've received a payment of {$this->payment->amount} {$this->payment->currency} for {$invoice->term}.")
            ->line('Method: '.ucfirst($this->payment->method))
            ->line('Invoice status: '.str_replace('_', ' ', $invoice->fresh()->status));
    }

    public function toDatabase(object $notifiable): array
    {
        $invoice = $this->payment->invoice;

        return [
            'message' => "Payment of {$this->payment->amount} {$this->payment->currency} received for {$invoice->student->full_name} ({$invoice->term})",
            'url' => route('dashboard'),
        ];
    }
}
