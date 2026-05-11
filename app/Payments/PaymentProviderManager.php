<?php

namespace App\Payments;

use InvalidArgumentException;

class PaymentProviderManager
{
    public function default(): string
    {
        return 'fake';
    }

    /**
     * @return array<string, class-string<PaymentProviderInterface>>
     */
    public function providers(): array
    {
        return [
            'fake' => FakePaymentProvider::class,
            'yookassa' => YooKassaPaymentProvider::class,
        ];
    }

    public function has(string $provider): bool
    {
        return array_key_exists($provider, $this->providers());
    }

    /**
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_keys($this->providers());
    }

    public function get(string $provider): PaymentProviderInterface
    {
        $providerClass = $this->providers()[$provider] ?? null;

        if (! $providerClass) {
            throw new InvalidArgumentException("Unsupported payment provider [{$provider}].");
        }

        return app($providerClass);
    }
}
