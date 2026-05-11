<?php

namespace App\Providers;

use App\Payments\FakePaymentProvider;
use App\Payments\PaymentProviderInterface;
use App\Payments\PaymentProviderManager;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentProviderManager::class);
        $this->app->bind(PaymentProviderInterface::class, FakePaymentProvider::class);
    }
}
