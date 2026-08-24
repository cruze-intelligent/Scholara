<?php

namespace App\Notifications;

use App\Models\Notice;
use Illuminate\Notifications\Notification;

/**
 * Database-only, not mail — a notice can go to every guardian/learner at the school, and mailing
 * all of them on every publish is noisy for something this routine (unlike a specific clinic
 * visit or payment, which is about one family).
 */
class NoticePublished extends Notification
{
    public function __construct(public Notice $notice) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => "New notice: {$this->notice->title}",
            'url' => route('dashboard'),
        ];
    }
}
