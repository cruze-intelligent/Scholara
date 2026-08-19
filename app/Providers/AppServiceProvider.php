<?php

namespace App\Providers;

use App\Services\Identity\FakeNiraVerifier;
use App\Services\Identity\NiraVerifier;
use App\Services\Notifications\LoggingOtpSender;
use App\Services\Notifications\OtpSender;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\PaymentGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Real NIRA/SMS-gateway/SchoolPay implementations replace these
        // bindings once credentials exist — see docs/ARCHITECTURE.md.
        $this->app->bind(NiraVerifier::class, FakeNiraVerifier::class);
        $this->app->bind(OtpSender::class, LoggingOtpSender::class);
        $this->app->bind(PaymentGateway::class, FakePaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
