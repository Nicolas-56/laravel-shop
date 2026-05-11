<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_checkout_creates_order_from_session_cart(): void
    {
        $user = User::factory()->create();
        $delivery = $this->createDelivery(price: 350);
        $product = $this->createProduct(price: 1500.50, stock: 5);
        session()->put("cart.items.{$product->id}", 2);

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'customer_name' => 'Ivan',
            'customer_phone' => '+7 999 111-22-33',
            'customer_email' => 'ivan@example.com',
            'customer_comment' => 'Call me',
            'delivery_id' => $delivery->id,
            'payment_provider' => 'fake',
        ]);

        $order = Order::query()->with(['items', 'payments'])->firstOrFail();
        $payment = $order->payments->first();

        $response->assertRedirect($payment->payment_url);
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->status);
        $this->assertSame('Ivan', $order->customer_name);
        $this->assertSame('+7 999 111-22-33', $order->customer_phone);
        $this->assertSame('ivan@example.com', $order->customer_email);
        $this->assertSame($delivery->id, $order->delivery_id);
        $this->assertSame('Курьер', $order->delivery_name);
        $this->assertSame('350.00', $order->delivery_price);
        $this->assertSame('3351.00', $order->total);
        $this->assertTrue($order->stock_reserved);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertNull(session("cart.items.{$product->id}"));
        $this->assertSame('fake', $payment->provider);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertSame('3351.00', $payment->amount);

        $item = $order->items->first();
        $this->assertSame($product->id, $item->product_id);
        $this->assertSame($product->name, $item->product_name);
        $this->assertSame('1500.50', $item->product_price);
        $this->assertSame(2, $item->quantity);
        $this->assertSame('3001.00', $item->line_total);
    }

    public function test_checkout_freezes_product_price(): void
    {
        $delivery = $this->createDelivery();
        $product = $this->createProduct(price: 100, stock: 2);
        session()->put("cart.items.{$product->id}", 1);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Ivan',
            'customer_phone' => '+7 999 111-22-33',
            'delivery_id' => $delivery->id,
            'payment_provider' => 'fake',
        ]);

        $product->update(['price' => 200]);

        $this->assertSame('100.00', Order::query()->firstOrFail()->items()->firstOrFail()->product_price);
    }

    public function test_checkout_rejects_inactive_product(): void
    {
        $delivery = $this->createDelivery();
        $product = $this->createProduct(isActive: false, price: 100, stock: 2);
        session()->put("cart.items.{$product->id}", 1);

        $response = $this->from('/')->post(route('checkout.store'), [
            'customer_name' => 'Ivan',
            'customer_phone' => '+7 999 111-22-33',
            'delivery_id' => $delivery->id,
            'payment_provider' => 'fake',
        ]);

        $response->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_quantity_above_stock(): void
    {
        $delivery = $this->createDelivery();
        $product = $this->createProduct(price: 100, stock: 1);
        session()->put("cart.items.{$product->id}", 2);

        $response = $this->from('/')->post(route('checkout.store'), [
            'customer_name' => 'Ivan',
            'customer_phone' => '+7 999 111-22-33',
            'delivery_id' => $delivery->id,
            'payment_provider' => 'fake',
        ]);

        $response->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_unsupported_payment_provider(): void
    {
        $delivery = $this->createDelivery();
        $product = $this->createProduct(price: 100, stock: 3);
        session()->put("cart.items.{$product->id}", 1);

        $response = $this->from('/')->post(route('checkout.store'), [
            'customer_name' => 'Ivan',
            'customer_phone' => '+7 999 111-22-33',
            'delivery_id' => $delivery->id,
            'payment_provider' => 'unknown',
        ]);

        $response->assertSessionHasErrors('payment_provider');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_checkout_rejects_inactive_delivery(): void
    {
        $delivery = $this->createDelivery(isActive: false);
        $product = $this->createProduct(price: 100, stock: 3);
        session()->put("cart.items.{$product->id}", 1);

        $response = $this->from('/')->post(route('checkout.store'), [
            'customer_name' => 'Ivan',
            'customer_phone' => '+7 999 111-22-33',
            'delivery_id' => $delivery->id,
            'payment_provider' => 'fake',
        ]);

        $response->assertSessionHasErrors('delivery_id');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_user_sees_only_own_orders(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownOrder = Order::query()->create([
            'user_id' => $user->id,
            'order_status_id' => Order::statusId(Order::STATUS_PENDING),
            'customer_name' => 'Own',
            'customer_phone' => '+1',
            'total' => 10,
        ]);

        $otherOrder = Order::query()->create([
            'user_id' => $otherUser->id,
            'order_status_id' => Order::statusId(Order::STATUS_PENDING),
            'customer_name' => 'Other',
            'customer_phone' => '+2',
            'total' => 20,
        ]);

        $response = $this->actingAs($user)->get(route('orders.index'));

        $response->assertOk();
        $response->assertSee("Заказ #{$ownOrder->id}");
        $response->assertDontSee("Заказ #{$otherOrder->id}");

        $this->actingAs($user)
            ->get(route('orders.show', $otherOrder))
            ->assertNotFound();
    }

    public function test_cancelled_order_restores_reserved_stock(): void
    {
        $product = $this->createProduct(price: 100, stock: 3);
        session()->put("cart.items.{$product->id}", 2);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Ivan',
            'customer_phone' => '+7 999 111-22-33',
            'delivery_id' => $this->createDelivery()->id,
            'payment_provider' => 'fake',
        ]);

        $order = Order::query()->firstOrFail();

        $this->assertSame(1, $product->fresh()->stock);

        $order->update([
            'order_status_id' => Order::statusId(Order::STATUS_CANCELLED),
        ]);

        $this->assertSame(3, $product->fresh()->stock);
        $this->assertFalse($order->fresh()->stock_reserved);
    }

    public function test_cleanup_expires_old_unpaid_orders_and_restores_stock(): void
    {
        Carbon::setTestNow(now());

        $user = User::factory()->create();
        $product = $this->createProduct(price: 100, stock: 3);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_status_id' => Order::statusId(Order::STATUS_PENDING_PAYMENT),
            'stock_reserved' => true,
            'customer_name' => 'Ivan',
            'customer_phone' => '+7 999 111-22-33',
            'total' => 200,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => 100,
            'quantity' => 2,
            'line_total' => 200,
        ]);
        $order->payments()->create([
            'provider' => 'fake',
            'provider_payment_id' => 'cleanup-payment-1',
            'status' => Payment::STATUS_PENDING,
            'amount' => 200,
        ]);

        $product->decrement('stock', 2);
        Order::query()->whereKey($order->id)->update([
            'created_at' => now()->subMinutes(31),
            'updated_at' => now()->subMinutes(31),
        ]);

        $this->artisan('orders:expire-unpaid')
            ->expectsOutput('Expired 1 unpaid orders.')
            ->assertExitCode(0);

        $this->assertSame(Order::STATUS_EXPIRED, $order->fresh()->status);
        $this->assertFalse($order->fresh()->stock_reserved);
        $this->assertSame(Payment::STATUS_FAILED, $order->payments()->firstOrFail()->status);
        $this->assertSame(3, $product->fresh()->stock);

        Carbon::setTestNow();
    }

    public function test_cleanup_does_not_restore_stock_twice(): void
    {
        Carbon::setTestNow(now());

        $user = User::factory()->create();
        $product = $this->createProduct(price: 100, stock: 3);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_status_id' => Order::statusId(Order::STATUS_PENDING_PAYMENT),
            'stock_reserved' => true,
            'customer_name' => 'Ivan',
            'customer_phone' => '+7 999 111-22-33',
            'total' => 200,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => 100,
            'quantity' => 2,
            'line_total' => 200,
        ]);

        $product->decrement('stock', 2);
        Order::query()->whereKey($order->id)->update([
            'created_at' => now()->subMinutes(31),
            'updated_at' => now()->subMinutes(31),
        ]);

        $this->artisan('orders:expire-unpaid')->assertExitCode(0);
        $this->artisan('orders:expire-unpaid')
            ->expectsOutput('Expired 0 unpaid orders.')
            ->assertExitCode(0);

        $this->assertSame(Order::STATUS_EXPIRED, $order->fresh()->status);
        $this->assertFalse($order->fresh()->stock_reserved);
        $this->assertSame(3, $product->fresh()->stock);

        Carbon::setTestNow();
    }

    public function test_restored_order_is_not_reserved_again_after_later_status_change(): void
    {
        $product = $this->createProduct(price: 100, stock: 3);
        session()->put("cart.items.{$product->id}", 2);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Ivan',
            'customer_phone' => '+7 999 111-22-33',
            'delivery_id' => $this->createDelivery()->id,
            'payment_provider' => 'fake',
        ]);

        $order = Order::query()->firstOrFail();

        $order->update([
            'order_status_id' => Order::statusId(Order::STATUS_EXPIRED),
        ]);

        $order->update([
            'order_status_id' => Order::statusId(Order::STATUS_PAID),
        ]);

        $this->assertFalse($order->fresh()->stock_reserved);
        $this->assertSame(3, $product->fresh()->stock);
    }

    private function createProduct(bool $isActive = true, float $price = 100, int $stock = 1): Product
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'category'],
            ['name' => 'Категория', 'is_active' => true],
        );

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => fake()->unique()->word(),
            'slug' => fake()->unique()->slug(),
            'price' => $price,
            'stock' => $stock,
            'is_active' => $isActive,
        ]);
    }

    private function createDelivery(bool $isActive = true, float $price = 0): Delivery
    {
        return Delivery::query()->create([
            'name' => 'Курьер',
            'price' => $price,
            'is_active' => $isActive,
        ]);
    }
}
