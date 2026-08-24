<?php

namespace App\Notifications;

use App\Models\ClinicVisit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Deliberately not ShouldQueue — that would defer every channel (including the database row) to
 * a queue worker, and nothing guarantees one is running (see docs/HARDENING_TODO.md Phase 5). A
 * small school's notification volume doesn't need queuing; synchronous keeps the in-app bell
 * accurate immediately.
 */
class ClinicVisitLogged extends Notification
{
    public function __construct(public ClinicVisit $visit) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
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

    public function toDatabase(object $notifiable): array
    {
        $student = $this->visit->student;

        return [
            'message' => "{$student->full_name} visited the clinic — {$this->visit->reason}",
            'url' => route('clinic-visits.index'),
        ];
    }
}
