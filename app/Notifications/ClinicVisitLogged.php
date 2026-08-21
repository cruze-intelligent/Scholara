<?php

namespace App\Notifications;

use App\Models\ClinicVisit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClinicVisitLogged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ClinicVisit $visit) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $student = $this->visit->student;

        return (new MailMessage)
            ->subject("Clinic visit: {$student->full_name}")
            ->line("{$student->full_name} visited the school clinic on {$this->visit->occurred_at->format('d M Y H:i')}.")
            ->line("Reason: {$this->visit->reason}")
            ->line('Outcome: '.str_replace('_', ' ', $this->visit->outcome));
    }
}
