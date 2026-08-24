<?php

namespace App\Notifications;

use App\Models\MedicationAdministration;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Deliberately not ShouldQueue — see the same note on ClinicVisitLogged.
 */
class MedicationAdministered extends Notification
{
    public function __construct(public MedicationAdministration $administration) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
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

    public function toDatabase(object $notifiable): array
    {
        $student = $this->administration->student;

        return [
            'message' => "{$student->full_name} was given {$this->administration->medication_name}",
            'url' => route('medications.index'),
        ];
    }
}
