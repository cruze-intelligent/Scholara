<?php

namespace App\Notifications;

use App\Models\MedicationAdministration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MedicationAdministered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public MedicationAdministration $administration) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $student = $this->administration->student;

        return (new MailMessage)
            ->subject("Medication given: {$student->full_name}")
            ->line("{$student->full_name} was given {$this->administration->medication_name} ({$this->administration->dose}) at {$this->administration->administered_at->format('d M Y H:i')}.")
            ->line($this->administration->five_rights_checked
                ? 'All five rights were verified before administration.'
                : 'Note: not all five rights checks were completed — see the school nurse.');
    }
}
