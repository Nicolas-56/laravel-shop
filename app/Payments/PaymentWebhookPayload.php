<?php

namespace App\Payments;

class PaymentWebhookPayload
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly string $providerPaymentId,
        public readonly string $paymentStatus,
        public readonly string $orderStatus,
    ) {}
}
