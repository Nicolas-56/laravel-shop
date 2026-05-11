<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Payments\PaymentProviderManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly PaymentProviderManager $providers,
    ) {}

    public function create(Order $order, ?string $providerName = null): Payment
    {
        $providerName ??= $this->providers->default();

        if (in_array($order->status, [Order::STATUS_PAID, Order::STATUS_CANCELLED, Order::STATUS_EXPIRED], true)) {
            throw ValidationException::withMessages([
                'order' => 'Для этого заказа нельзя создать платёж.',
            ]);
        }

        if (! $this->providers->has($providerName)) {
            throw ValidationException::withMessages([
                'payment_provider' => 'Выбранный способ оплаты недоступен.',
            ]);
        }

        return DB::transaction(function () use ($order, $providerName): Payment {
            $existingPayment = $order->payments()
                ->where('provider', $providerName)
                ->where('status', Payment::STATUS_PENDING)
                ->latest()
                ->first();

            if ($existingPayment) {
                return $existingPayment;
            }

            $providerPayment = $this->providers->get($providerName)->createPayment($order);

            $payment = $order->payments()->create([
                'provider' => $providerName,
                'provider_payment_id' => $providerPayment->providerPaymentId,
                'status' => Payment::STATUS_PENDING,
                'amount' => $order->total,
                'payment_url' => $providerPayment->paymentUrl,
                'expires_at' => $providerPayment->expiresAt,
                'meta' => $providerPayment->meta,
            ]);

            $order->update([
                'order_status_id' => Order::statusId(Order::STATUS_PENDING_PAYMENT),
            ]);

            return $payment;
        });
    }

    public function handleWebhook(array $payload, ?string $signature, ?string $providerName = null): Payment
    {
        $providerName ??= $this->providers->default();
        $provider = $this->providers->get($providerName);

        if (! $provider->validateWebhookSignature($payload, $signature)) {
            throw ValidationException::withMessages([
                'signature' => 'Invalid payment webhook signature.',
            ]);
        }

        $webhook = $provider->normalizeWebhookPayload($payload);

        foreach (['eventId', 'eventType', 'providerPaymentId'] as $field) {
            if (! filled($webhook->{$field})) {
                throw ValidationException::withMessages([$field => 'Invalid payment webhook payload.']);
            }
        }

        return DB::transaction(function () use ($payload, $providerName, $webhook): Payment {
            $payment = Payment::query()
                ->where('provider', $providerName)
                ->where('provider_payment_id', $webhook->providerPaymentId)
                ->lockForUpdate()
                ->firstOrFail();

            $event = PaymentEvent::query()->firstOrCreate(
                [
                    'provider' => $providerName,
                    'event_id' => $webhook->eventId,
                ],
                [
                    'payment_id' => $payment->getKey(),
                    'event_type' => $webhook->eventType,
                    'payload' => $payload,
                ],
            );

            if (! $event->wasRecentlyCreated) {
                return $payment->fresh(['order']);
            }

            if ($webhook->paymentStatus === Payment::STATUS_PAID) {
                $this->handleSuccess($payment);
            } elseif ($webhook->paymentStatus === Payment::STATUS_FAILED) {
                $this->handleFailure($payment, $webhook->orderStatus);
            } elseif ($webhook->orderStatus === Order::STATUS_CANCELLED) {
                $this->handleCancel($payment, $webhook->orderStatus);
            }

            return $payment->fresh(['order']);
        });
    }

    public function handleSuccess(Payment $payment): void
    {
        if ($payment->status !== Payment::STATUS_PENDING) {
            return;
        }

        $payment->update([
            'status' => Payment::STATUS_PAID,
            'paid_at' => $payment->paid_at ?? now(),
        ]);

        $payment->order->update([
            'order_status_id' => Order::statusId(Order::STATUS_PAID),
        ]);
    }

    public function handleFailure(Payment $payment, string $orderStatus = Order::STATUS_EXPIRED): void
    {
        if ($payment->status !== Payment::STATUS_PENDING) {
            return;
        }

        $payment->update([
            'status' => Payment::STATUS_FAILED,
        ]);

        $payment->order->update([
            'order_status_id' => Order::statusId($orderStatus),
        ]);
    }

    public function handleCancel(Payment $payment, string $orderStatus = Order::STATUS_EXPIRED): void
    {
        if ($payment->status !== Payment::STATUS_PENDING) {
            return;
        }

        $payment->update([
            'status' => Payment::STATUS_FAILED,
        ]);

        $payment->order->update([
            'order_status_id' => Order::statusId($orderStatus),
        ]);
    }

    public function markFakePaymentSuccessful(Order $order): Payment
    {
        return $this->processFakePayment($order, function (Payment $payment): void {
            $this->handleSuccess($payment);
        });
    }

    public function markFakePaymentFailed(Order $order): Payment
    {
        return $this->processFakePayment($order, function (Payment $payment): void {
            $this->handleFailure($payment, Order::STATUS_EXPIRED);
        });
    }

    public function markFakePaymentCancelled(Order $order): Payment
    {
        return $this->processFakePayment($order, function (Payment $payment): void {
            $this->handleCancel($payment, Order::STATUS_CANCELLED);
        });
    }

    private function processFakePayment(Order $order, callable $handler): Payment
    {
        return DB::transaction(function () use ($order, $handler): Payment {
            $payment = Payment::query()
                ->where('order_id', $order->getKey())
                ->where('provider', 'fake')
                ->latest()
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                throw (new ModelNotFoundException())->setModel(Payment::class);
            }

            if ($payment->status !== Payment::STATUS_PENDING) {
                return $payment->fresh(['order.orderStatus']);
            }

            $handler($payment);

            return $payment->fresh(['order.orderStatus']);
        });
    }
}
