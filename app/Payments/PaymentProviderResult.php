<?php

namespace App\Payments;

class PaymentProviderResult
{
    public function __construct(
        public readonly string $providerPaymentId,
        public readonly string $paymentUrl,
        public readonly array $meta = [],
        public readonly ?string $expiresAt = null,
    ) {}
}
