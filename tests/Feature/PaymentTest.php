<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Payments\FakePaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_payment_moves_order_to_awaiting_payment(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user);

        $response = $this->actingAs($user)->post(route('orders.payments.store', $order));

        $payment = Payment::query()->firstOrFail();

        $response->assertRedirect($payment->payment_url);
        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->fresh()->status);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertSame('fake', $payment->provider);
        $this->assertSame('100.00', $payment->amount);
    }

    public function test_fake_payment_page_renders_for_signed_link(): void
    {
        $this->withoutVite();

        $payment = $this->createPayment();

        $this->get($payment->payment_url)
            ->assertOk()
            ->assertSee('Оплата заказа #'.$payment->order_id)
            ->assertSee('Успех')
            ->assertSee('Ошибка оплаты')
            ->assertSee('Отмена');
    }

    public function test_fake_payment_success_marks_order_paid(): void
    {
        $user = User::factory()->create();
        $payment = $this->createPayment($user);

        $response = $this->actingAs($user)->get($this->fakeActionUrl('payments.fake.success', $payment->order));

        $response->assertRedirect(route('orders.show', $payment->order));
        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame(Order::STATUS_PAID, $payment->order->fresh()->status);
    }

    public function test_fake_payment_fail_marks_payment_failed_and_restores_stock(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, Order::STATUS_AWAITING_PAYMENT, stockReserved: true);
        $product = \App\Models\Product::query()->create([
            'category_id' => \App\Models\Category::query()->firstOrCreate(
                ['slug' => 'category'],
                ['name' => 'Категория', 'is_active' => true],
            )->id,
            'name' => 'Reserved',
            'slug' => 'reserved-fail-page',
            'is_active' => true,
            'stock' => 1,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => 100,
            'quantity' => 2,
            'line_total' => 200,
        ]);

        $payment = $this->createPayment($user, $order);

        $this->actingAs($user)
            ->get($this->fakeActionUrl('payments.fake.fail', $order))
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
        $this->assertSame(Order::STATUS_EXPIRED, $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertFalse($order->fresh()->stock_reserved);
    }

    public function test_fake_payment_cancel_marks_order_cancelled_and_restores_stock(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, Order::STATUS_AWAITING_PAYMENT, stockReserved: true);
        $product = \App\Models\Product::query()->create([
            'category_id' => \App\Models\Category::query()->firstOrCreate(
                ['slug' => 'category'],
                ['name' => 'Категория', 'is_active' => true],
            )->id,
            'name' => 'Reserved',
            'slug' => 'reserved-cancel-page',
            'is_active' => true,
            'stock' => 1,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => 100,
            'quantity' => 2,
            'line_total' => 200,
        ]);

        $payment = $this->createPayment($user, $order);

        $this->actingAs($user)
            ->get($this->fakeActionUrl('payments.fake.cancel', $order))
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertFalse($order->fresh()->stock_reserved);
    }

    public function test_fake_payment_outcome_is_not_processed_twice(): void
    {
        $user = User::factory()->create();
        $payment = $this->createPayment($user);

        $this->actingAs($user)->get($this->fakeActionUrl('payments.fake.success', $payment->order));
        $this->actingAs($user)->get($this->fakeActionUrl('payments.fake.fail', $payment->order));

        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame(Order::STATUS_PAID, $payment->order->fresh()->status);
    }

    public function test_yookassa_payment_provider_creates_payment_link(): void
    {
        config()->set('services.yookassa.shop_id', 'shop-id');
        config()->set('services.yookassa.secret_key', 'secret-key');

        Http::fake([
            'https://api.yookassa.ru/v3/payments' => Http::response([
                'id' => 'yoo-payment-1',
                'status' => 'pending',
                'paid' => false,
                'confirmation' => [
                    'confirmation_url' => 'https://yookassa.test/pay/yoo-payment-1',
                ],
                'test' => true,
            ]),
        ]);

        $user = User::factory()->create();
        $order = $this->createOrder($user);

        $response = $this->actingAs($user)->post(route('orders.payments.store', $order), [
            'payment_provider' => 'yookassa',
        ]);

        $payment = Payment::query()->firstOrFail();

        $response->assertRedirect('https://yookassa.test/pay/yoo-payment-1');
        $this->assertSame('yookassa', $payment->provider);
        $this->assertSame('yoo-payment-1', $payment->provider_payment_id);
        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->fresh()->status);

        Http::assertSent(fn ($request): bool => $request->hasHeader('Idempotence-Key', 'order-'.$order->id)
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('shop-id:secret-key'))
            && $request['amount']['value'] === '100.00'
            && $request['confirmation']['type'] === 'redirect'
            && $request['metadata']['order_id'] === (string) $order->id);
    }

    public function test_payment_cannot_be_created_for_final_order(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, Order::STATUS_CANCELLED);

        $this->actingAs($user)
            ->from(route('orders.show', $order))
            ->post(route('orders.payments.store', $order))
            ->assertSessionHasErrors('order');

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_provider_payment_ids_are_unique_per_provider(): void
    {
        $fakeOrder = $this->createOrder(User::factory()->create(), Order::STATUS_AWAITING_PAYMENT);
        $yookassaOrder = $this->createOrder(User::factory()->create(), Order::STATUS_AWAITING_PAYMENT);

        Payment::query()->create([
            'order_id' => $fakeOrder->id,
            'provider' => 'fake',
            'provider_payment_id' => 'shared-payment-id',
            'status' => Payment::STATUS_PENDING,
            'amount' => $fakeOrder->total,
        ]);

        Payment::query()->create([
            'order_id' => $yookassaOrder->id,
            'provider' => 'yookassa',
            'provider_payment_id' => 'shared-payment-id',
            'status' => Payment::STATUS_PENDING,
            'amount' => $yookassaOrder->total,
        ]);

        $this->assertSame(2, Payment::query()->where('provider_payment_id', 'shared-payment-id')->count());
    }

    public function test_fake_paid_webhook_marks_payment_and_order_paid(): void
    {
        $payment = $this->createPayment();
        $payload = $this->paidPayload($payment, 'event-1');

        $response = $this->postJson(route('payments.fake.webhook'), $payload, [
            'X-Fake-Signature' => app(FakePaymentProvider::class)->signature($payload),
        ]);

        $response->assertOk();
        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame(Order::STATUS_PAID, $payment->order->fresh()->status);
        $this->assertDatabaseHas('payment_events', [
            'event_id' => 'event-1',
            'payment_id' => $payment->id,
            'event_type' => 'payment.paid',
        ]);
    }

    public function test_fake_webhook_is_idempotent(): void
    {
        $payment = $this->createPayment();
        $payload = $this->paidPayload($payment, 'event-1');
        $signature = app(FakePaymentProvider::class)->signature($payload);

        $this->postJson(route('payments.fake.webhook'), $payload, [
            'X-Fake-Signature' => $signature,
        ])->assertOk();

        $this->postJson(route('payments.fake.webhook'), $payload, [
            'X-Fake-Signature' => $signature,
        ])->assertOk();

        $this->assertSame(1, PaymentEvent::query()->where('event_id', 'event-1')->count());
        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame(Order::STATUS_PAID, $payment->order->fresh()->status);
    }

    public function test_conflicting_fake_webhook_does_not_override_processed_payment(): void
    {
        $payment = $this->createPayment();
        $paidPayload = $this->paidPayload($payment, 'event-paid');
        $failedPayload = [
            'event_id' => 'event-failed-after-paid',
            'event_type' => 'payment.failed',
            'provider_payment_id' => $payment->provider_payment_id,
        ];

        $this->postJson(route('payments.fake.webhook'), $paidPayload, [
            'X-Fake-Signature' => app(FakePaymentProvider::class)->signature($paidPayload),
        ])->assertOk();

        $this->postJson(route('payments.fake.webhook'), $failedPayload, [
            'X-Fake-Signature' => app(FakePaymentProvider::class)->signature($failedPayload),
        ])->assertOk();

        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame(Order::STATUS_PAID, $payment->order->fresh()->status);
    }

    public function test_conflicting_success_webhook_does_not_reopen_failed_payment_or_reserve_stock(): void
    {
        $order = $this->createOrder(User::factory()->create(), Order::STATUS_AWAITING_PAYMENT, stockReserved: true);
        $product = \App\Models\Product::query()->create([
            'category_id' => \App\Models\Category::query()->firstOrCreate(
                ['slug' => 'category'],
                ['name' => 'Категория', 'is_active' => true],
            )->id,
            'name' => 'Reserved conflict',
            'slug' => 'reserved-conflict',
            'is_active' => true,
            'stock' => 1,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => 100,
            'quantity' => 2,
            'line_total' => 200,
        ]);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'fake',
            'provider_payment_id' => 'fake-payment-conflict',
            'status' => Payment::STATUS_PENDING,
            'amount' => $order->total,
        ]);
        $failedPayload = [
            'event_id' => 'event-failed-conflict',
            'event_type' => 'payment.failed',
            'provider_payment_id' => $payment->provider_payment_id,
        ];
        $paidPayload = $this->paidPayload($payment, 'event-paid-after-failed');

        $this->postJson(route('payments.fake.webhook'), $failedPayload, [
            'X-Fake-Signature' => app(FakePaymentProvider::class)->signature($failedPayload),
        ])->assertOk();

        $this->postJson(route('payments.fake.webhook'), $paidPayload, [
            'X-Fake-Signature' => app(FakePaymentProvider::class)->signature($paidPayload),
        ])->assertOk();

        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
        $this->assertSame(Order::STATUS_EXPIRED, $order->fresh()->status);
        $this->assertFalse($order->fresh()->stock_reserved);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_fake_webhook_accepts_external_post_without_csrf_token(): void
    {
        $this->withMiddleware();

        $payment = $this->createPayment();
        $payload = $this->paidPayload($payment, 'event-1');

        $this->postJson(route('payments.fake.webhook'), $payload, [
            'X-Fake-Signature' => app(FakePaymentProvider::class)->signature($payload),
        ])->assertOk();

        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
    }

    public function test_fake_webhook_rejects_invalid_signature(): void
    {
        $payment = $this->createPayment();
        $payload = $this->paidPayload($payment, 'event-1');

        $response = $this->postJson(route('payments.fake.webhook'), $payload, [
            'X-Fake-Signature' => 'invalid',
        ]);

        $response->assertUnprocessable();
        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $payment->order->fresh()->status);
        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_yookassa_succeeded_webhook_marks_payment_and_order_paid(): void
    {
        config()->set('services.yookassa.webhook_secret', 'webhook-secret');

        $order = $this->createOrder(User::factory()->create(), Order::STATUS_AWAITING_PAYMENT);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'yookassa',
            'provider_payment_id' => 'yoo-payment-1',
            'status' => Payment::STATUS_PENDING,
            'amount' => $order->total,
            'payment_url' => 'https://yookassa.test/pay/yoo-payment-1',
        ]);

        $payload = [
            'type' => 'notification',
            'event' => 'payment.succeeded',
            'object' => [
                'id' => 'yoo-payment-1',
                'status' => 'succeeded',
                'paid' => true,
            ],
        ];

        $this->postJson(route('payments.yookassa.webhook'), $payload, [
            'X-Yookassa-Webhook-Secret' => 'webhook-secret',
        ])->assertOk();

        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame(Order::STATUS_PAID, $order->fresh()->status);
        $this->assertDatabaseHas('payment_events', [
            'event_id' => 'yookassa:yoo-payment-1:payment.succeeded',
            'provider' => 'yookassa',
            'event_type' => 'payment.succeeded',
        ]);
    }

    public function test_yookassa_webhook_rejects_invalid_auth(): void
    {
        config()->set('services.yookassa.webhook_secret', 'webhook-secret');

        $order = $this->createOrder(User::factory()->create(), Order::STATUS_AWAITING_PAYMENT);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'yookassa',
            'provider_payment_id' => 'yoo-payment-1',
            'status' => Payment::STATUS_PENDING,
            'amount' => $order->total,
        ]);

        $this->postJson(route('payments.yookassa.webhook'), [
            'type' => 'notification',
            'event' => 'payment.succeeded',
            'object' => [
                'id' => 'yoo-payment-1',
                'status' => 'succeeded',
            ],
        ], [
            'X-Yookassa-Webhook-Secret' => 'wrong-secret',
        ])->assertUnprocessable();

        $this->assertSame(Payment::STATUS_PENDING, $payment->fresh()->status);
        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->fresh()->status);
        $this->assertDatabaseMissing('payment_events', [
            'provider' => 'yookassa',
        ]);
    }

    public function test_failed_payment_webhook_restores_reserved_stock(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user, Order::STATUS_AWAITING_PAYMENT, stockReserved: true);
        $product = \App\Models\Product::query()->create([
            'category_id' => \App\Models\Category::query()->firstOrCreate(
                ['slug' => 'category'],
                ['name' => 'Категория', 'is_active' => true],
            )->id,
            'name' => 'Reserved',
            'slug' => 'reserved',
            'is_active' => true,
            'stock' => 1,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => 100,
            'quantity' => 2,
            'line_total' => 200,
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'fake',
            'provider_payment_id' => 'fake-payment-failed',
            'status' => Payment::STATUS_PENDING,
            'amount' => $order->total,
        ]);

        $payload = [
            'event_id' => 'event-failed-1',
            'event_type' => 'payment.failed',
            'provider_payment_id' => $payment->provider_payment_id,
        ];

        $this->postJson(route('payments.fake.webhook'), $payload, [
            'X-Fake-Signature' => app(FakePaymentProvider::class)->signature($payload),
        ])->assertOk();

        $this->assertSame(3, $product->fresh()->stock);
        $this->assertFalse($order->fresh()->stock_reserved);
        $this->assertSame(Order::STATUS_EXPIRED, $order->fresh()->status);
    }

    private function createPayment(?User $user = null, ?Order $order = null): Payment
    {
        $order ??= $this->createOrder($user ?? User::factory()->create(), Order::STATUS_AWAITING_PAYMENT);

        return Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'fake',
            'provider_payment_id' => 'fake-payment-1',
            'status' => Payment::STATUS_PENDING,
            'amount' => $order->total,
            'payment_url' => $this->fakeActionUrl('payments.fake.show', $order),
        ]);
    }

    private function createOrder(User $user, string $status = Order::STATUS_PENDING, bool $stockReserved = false): Order
    {
        return Order::query()->create([
            'user_id' => $user->id,
            'order_status_id' => Order::statusId($status),
            'stock_reserved' => $stockReserved,
            'customer_name' => 'Ivan',
            'customer_phone' => '+7 999 111-22-33',
            'total' => 100,
        ]);
    }

    private function paidPayload(Payment $payment, string $eventId): array
    {
        return [
            'event_id' => $eventId,
            'event_type' => 'payment.paid',
            'provider_payment_id' => $payment->provider_payment_id,
        ];
    }

    private function fakeActionUrl(string $routeName, Order $order): string
    {
        return URL::temporarySignedRoute($routeName, now()->addMinutes(30), ['order' => $order]);
    }
}
