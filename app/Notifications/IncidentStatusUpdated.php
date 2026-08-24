<?php

namespace App\Notifications;

use App\Models\IncidentReport;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentStatusUpdated extends Notification
{
    public function __construct(public IncidentReport $incident) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your issue report has been updated')
            ->line("Status: {$this->incident->status}.")
            ->line('Category: '.ucfirst($this->incident->category));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => 'Your issue report is now '.str_replace('_', ' ', $this->incident->status),
            'url' => route('incidents.index'),
        ];
    }
}
