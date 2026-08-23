<?php

namespace App\Providers;

use App\Services\Identity\FakeNiraVerifier;
use App\Services\Identity\NiraVerifier;
use App\Services\Notifications\LoggingOtpSender;
use App\Services\Notifications\OtpSender;
use App\Services\Payments\DGatewayClient;
use App\Services\Payments\DGatewayPaymentGateway;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\PaymentGateway;
use App\Models\InventoryTransaction;
use App\Observers\InventoryTransactionObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Real NIRA/SMS-gateway implementations replace these bindings once
        // credentials exist — see docs/ARCHITECTURE.md.
        $this->app->bind(NiraVerifier::class, FakeNiraVerifier::class);
        $this->app->bind(OtpSender::class, LoggingOtpSender::class);

        // DGateway (docs/DECISIONS.md) auto-upgrades from the fake the moment a real
        // DGATEWAY_API_KEY is set — no code change needed to go from placeholder to live.
        $this->app->bind(PaymentGateway::class, function ($app) {
            $apiKey = config('services.dgateway.key');

            if (! $apiKey) {
                return new FakePaymentGateway;
            }

            return new DGatewayPaymentGateway(
                new DGatewayClient(config('services.dgateway.api_url'), $apiKey),
                config('services.dgateway.default_currency'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        InventoryTransaction::observe(InventoryTransactionObserver::class);
    }
}
