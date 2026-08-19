<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Log;

/**
 * Stands in for a real SMS/USSD gateway (e.g. Africa's Talking) until an
 * account is set up — see docs/COMPLIANCE.md. Logs the OTP instead of
 * sending it so the 2FA flow can be built and tested now.
 */
class LoggingOtpSender implements OtpSender
{
    public function send(string $phone, string $otp): void
    {
        Log::info("LoggingOtpSender: would send OTP {$otp} to {$phone}");
    }
}
