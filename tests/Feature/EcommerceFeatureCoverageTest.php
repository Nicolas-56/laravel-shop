<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\Product;
use App\Models\User;
use App\Payments\FakePaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EcommerceFeatureCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_user_can_register_and_login(): void
    {
        $this->post(route('register'), [
            'name' => 'Ivan',
            'email' => 'ivan@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();

        $this->post(route('login'), [
            'email' => 'ivan@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('profile'));

        $this->assertAuthenticated();
    }

    public function test_non_admin_cannot_access_filament(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->actingAs($user)
            ->get('/web-panel')
            ->assertForbidden();
    }

    public function test_only_active_products_are_visible(): void
    {
        $activeProduct = $this->createProduct(name: 'Активный товар', slug: 'active-product');
        $this->createProduct(name: 'Скрытый товар', slug: 'inactive-product', isActive: false);

        $this->assertSame(
            [$activeProduct->id],
            Product::query()->active()->pluck('id')->all(),
        );
    }

    public function test_inactive_product_cannot_be_added_to_cart(): void
    {
        $product = $this->createProduct(isActive: false, stock: 5);

        $this->from('/')
            ->post(route('cart.items.store'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('product_id');

        $this->assertNull(session("cart.items.{$product->id}"));
    }

    public function test_cart_add_update_remove_works(): void
    {
        $product = $this->createProduct(stock: 5);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->assertSame(2, session("cart.items.{$product->id}"));

        $this->patch(route('cart.items.update', $product), [
            'quantity' => 4,
        ])->assertRedirect();

        $this->assertSame(4, session("cart.items.{$product->id}"));

        $this->delete(route('cart.items.destroy', $product))->assertRedirect();

        $this->assertNull(session("cart.items.{$product->id}"));
    }

    public function test_checkout_creates_order_with_correct_items(): void
    {
        $user = User::factory()->create();
        $delivery = $this->createDelivery(price: 150);
        $firstProduct = $this->createProduct(name: 'Первый товар', slug: 'first-product', price: 100, stock: 3);
        $secondProduct = $this->createProduct(name: 'Второй товар', slug: 'second-product', price: 250.50, stock: 2);

        session()->put('cart.items', [
            $firstProduct->id => 2,
            $secondProduct->id => 1,
        ]);

        $this->actingAs($user)
            ->post(route('checkout.store'), [
                'customer_name' => 'Ivan',
                'customer_phone' => '+7 999 111-22-33',
                'customer_email' => 'ivan@example.com',
                'delivery_id' => $delivery->id,
                'payment_provider' => 'fake',
            ])
            ->assertRedirect();

        $order = Order::query()->with('items')->firstOrFail();

        $this->assertSame($user->id, $order->user_id);
        $this->assertSame($delivery->id, $order->delivery_id);
        $this->assertSame('Самовывоз', $order->delivery_name);
        $this->assertSame('150.00', $order->delivery_price);
        $this->assertSame('600.50', $order->total);
        $this->assertCount(2, $order->items);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $firstProduct->id,
            'product_name' => 'Первый товар',
            'product_price' => '100.00',
            'quantity' => 2,
            'line_total' => '200.00',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $secondProduct->id,
            'product_name' => 'Второй товар',
            'product_price' => '250.50',
            'quantity' => 1,
            'line_total' => '250.50',
        ]);
    }

    public function test_fake_payment_success_marks_order_paid(): void
    {
        $payment = $this->createPendingPayment();
        $payload = $this->fakePaidPayload($payment, 'payment-event-1');

        $this->postJson(route('payments.fake.webhook'), $payload, [
            'X-Fake-Signature' => app(FakePaymentProvider::class)->signature($payload),
        ])->assertOk();

        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame(Order::STATUS_PAID, $payment->order->fresh()->status);
    }

    public function test_duplicate_webhook_is_idempotent(): void
    {
        $payment = $this->createPendingPayment();
        $payload = $this->fakePaidPayload($payment, 'payment-event-1');
        $signature = app(FakePaymentProvider::class)->signature($payload);

        $this->postJson(route('payments.fake.webhook'), $payload, [
            'X-Fake-Signature' => $signature,
        ])->assertOk();

        $this->postJson(route('payments.fake.webhook'), $payload, [
            'X-Fake-Signature' => $signature,
        ])->assertOk();

        $this->assertSame(1, PaymentEvent::query()->where('event_id', 'payment-event-1')->count());
        $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame(Order::STATUS_PAID, $payment->order->fresh()->status);
    }

    public function test_user_cannot_access_another_users_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherOrder = $this->createOrder($otherUser);

        $this->actingAs($user)
            ->get(route('orders.show', $otherOrder))
            ->assertNotFound();
    }

    private function createProduct(
        string $name = 'Товар',
        string $slug = 'product',
        bool $isActive = true,
        float $price = 100,
        int $stock = 1,
    ): Product {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'category'],
            ['name' => 'Категория', 'is_active' => true],
        );

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'price' => $price,
            'stock' => $stock,
            'is_active' => $isActive,
        ]);
    }

    private function createOrder(User $user): Order
    {
        return Order::query()->create([
            'user_id' => $user->id,
            'order_status_id' => Order::statusId(Order::STATUS_AWAITING_PAYMENT),
            'customer_name' => 'Ivan',
            'customer_phone' => '+7 999 111-22-33',
            'total' => 100,
        ]);
    }

    private function createDelivery(float $price = 0): Delivery
    {
        return Delivery::query()->create([
            'name' => 'Самовывоз',
            'price' => $price,
            'is_active' => true,
        ]);
    }

    private function createPendingPayment(): Payment
    {
        $order = $this->createOrder(User::factory()->create());

        return Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'fake',
            'provider_payment_id' => 'fake-payment-1',
            'status' => Payment::STATUS_PENDING,
            'amount' => $order->total,
            'payment_url' => URL::temporarySignedRoute('payments.fake.show', now()->addMinutes(30), ['order' => $order]),
        ]);
    }

    private function fakePaidPayload(Payment $payment, string $eventId): array
    {
        return [
            'event_id' => $eventId,
            'event_type' => 'payment.paid',
            'provider_payment_id' => $payment->provider_payment_id,
        ];
    }
}
