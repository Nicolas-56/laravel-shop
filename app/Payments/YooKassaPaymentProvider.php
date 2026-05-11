<?php

namespace App\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class YooKassaPaymentProvider implements PaymentProviderInterface
{
    public function name(): string
    {
        return 'yookassa';
    }

    public function createPayment(Order $order): PaymentProviderResult
    {
        $response = Http::withBasicAuth($this->shopId(), $this->secretKey())
            ->withHeaders([
                'Idempotence-Key' => 'order-'.$order->getKey(),
            ])
            ->asJson()
            ->post('https://api.yookassa.ru/v3/payments', [
                'amount' => [
                    'value' => number_format((float) $order->total, 2, '.', ''),
                    'currency' => 'RUB',
                ],
                'capture' => true,
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => route('orders.show', $order),
                ],
                'description' => 'Заказ #'.$order->getKey(),
                'metadata' => [
                    'order_id' => (string) $order->getKey(),
                ],
            ]);

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'payment_provider' => 'Не удалось создать платёж YooKassa.',
            ]);
        }

        $payload = $response->json();
        $providerPaymentId = (string) ($payload['id'] ?? '');
        $paymentUrl = (string) data_get($payload, 'confirmation.confirmation_url', '');

        if ($providerPaymentId === '' || $paymentUrl === '') {
            throw ValidationException::withMessages([
                'payment_provider' => 'YooKassa вернула некорректный ответ.',
            ]);
        }

        return new PaymentProviderResult(
            providerPaymentId: $providerPaymentId,
            paymentUrl: $paymentUrl,
            meta: [
                'status' => $payload['status'] ?? null,
                'paid' => $payload['paid'] ?? null,
                'test' => $payload['test'] ?? null,
            ],
        );
    }

    public function validateWebhookSignature(array $payload, ?string $signature): bool
    {
        $secret = $this->webhookSecret();

        return $secret !== '' && is_string($signature) && hash_equals($secret, $signature);
    }

    public function normalizeWebhookPayload(array $payload): PaymentWebhookPayload
    {
        $event = (string) ($payload['event'] ?? '');
        $providerPaymentId = (string) data_get($payload, 'object.id', '');
        $externalStatus = (string) data_get($payload, 'object.status', '');

        return new PaymentWebhookPayload(
            eventId: 'yookassa:'.$providerPaymentId.':'.$event,
            eventType: $event,
            providerPaymentId: $providerPaymentId,
            paymentStatus: $this->mapPaymentStatus($externalStatus),
            orderStatus: $this->mapOrderStatus($externalStatus),
        );
    }

    private function mapPaymentStatus(string $status): string
    {
        return match ($status) {
            'succeeded' => Payment::STATUS_PAID,
            'canceled' => Payment::STATUS_FAILED,
            default => Payment::STATUS_PENDING,
        };
    }

    private function mapOrderStatus(string $status): string
    {
        return match ($status) {
            'succeeded' => Order::STATUS_PAID,
            'canceled' => Order::STATUS_EXPIRED,
            default => Order::STATUS_PENDING_PAYMENT,
        };
    }

    private function shopId(): string
    {
        return (string) config('services.yookassa.shop_id');
    }

    private function secretKey(): string
    {
        return (string) config('services.yookassa.secret_key');
    }

    private function webhookSecret(): string
    {
        return (string) config('services.yookassa.webhook_secret');
    }
}
