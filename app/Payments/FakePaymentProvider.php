<?php

namespace App\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\URL;

class FakePaymentProvider implements PaymentProviderInterface
{
    public function name(): string
    {
        return 'fake';
    }

    public function createPayment(Order $order): PaymentProviderResult
    {
        $providerPaymentId = 'fake_'.$order->getKey().'_'.str()->uuid()->toString();
        $expiresAt = now()->addMinutes(30);

        return new PaymentProviderResult(
            providerPaymentId: $providerPaymentId,
            paymentUrl: URL::temporarySignedRoute('payments.fake.show', $expiresAt, ['order' => $order]),
            meta: ['order_id' => $order->getKey()],
            expiresAt: $expiresAt->toIso8601String(),
        );
    }

    public function validateWebhookSignature(array $payload, ?string $signature): bool
    {
        if (! is_string($signature) || $signature === '') {
            return false;
        }

        return hash_equals($this->signature($payload), $signature);
    }

    public function signature(array $payload): string
    {
        ksort($payload);

        return hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $this->secret());
    }

    private function secret(): string
    {
        return (string) config('services.fake_payment.secret', 'fake-payment-secret');
    }

    public function normalizeWebhookPayload(array $payload): PaymentWebhookPayload
    {
        return new PaymentWebhookPayload(
            eventId: (string) ($payload['event_id'] ?? ''),
            eventType: (string) ($payload['event_type'] ?? ''),
            providerPaymentId: (string) ($payload['provider_payment_id'] ?? ''),
            paymentStatus: match ($payload['event_type'] ?? null) {
                'payment.paid' => Payment::STATUS_PAID,
                'payment.failed' => Payment::STATUS_FAILED,
                default => Payment::STATUS_PENDING,
            },
            orderStatus: match ($payload['event_type'] ?? null) {
                'payment.paid' => Order::STATUS_PAID,
                'payment.failed' => Order::STATUS_EXPIRED,
                default => Order::STATUS_PENDING_PAYMENT,
            },
        );
    }
}
