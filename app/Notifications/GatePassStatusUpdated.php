<?php

namespace App\Notifications;

use App\Models\GatePass;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GatePassStatusUpdated extends Notification
{
    public function __construct(public GatePass $gatePass) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Gate pass update')
            ->line("Your gate pass request for {$this->gatePass->student->full_name} was {$this->gatePass->status}.")
            ->line("Reason: {$this->gatePass->reason}");
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => "Gate pass for {$this->gatePass->student->full_name} was {$this->gatePass->status}",
            'url' => route('gate-passes.index'),
        ];
    }
}
