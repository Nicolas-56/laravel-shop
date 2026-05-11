<?php

namespace App\Payments;

use App\Models\Order;

interface PaymentProviderInterface
{
    public function name(): string;

    public function createPayment(Order $order): PaymentProviderResult;

    public function validateWebhookSignature(array $payload, ?string $signature): bool;

    public function normalizeWebhookPayload(array $payload): PaymentWebhookPayload;
}
