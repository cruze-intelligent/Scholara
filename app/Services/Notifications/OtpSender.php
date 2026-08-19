<?php

namespace App\Services\Notifications;

interface OtpSender
{
    /**
     * Send a one-time password to the given phone number.
     */
    public function send(string $phone, string $otp): void;
}
