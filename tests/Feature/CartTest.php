<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_active_product_to_session_cart(): void
    {
        $product = $this->createProduct(stock: 5);

        $response = $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $this->assertSame(2, session("cart.items.{$product->id}"));
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_guest_can_add_product_to_cart_with_json_response(): void
    {
        $product = $this->createProduct(stock: 5);

        $response = $this->postJson(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Товар добавлен в корзину.',
                'cart_count' => 2,
                'item_quantity' => 2,
            ]);
        $this->assertSame(2, session("cart.items.{$product->id}"));
    }

    public function test_adding_same_product_increments_quantity(): void
    {
        $product = $this->createProduct(stock: 5);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->assertSame(5, session("cart.items.{$product->id}"));
    }

    public function test_cart_rejects_inactive_product(): void
    {
        $product = $this->createProduct(isActive: false, stock: 5);

        $response = $this->from('/')->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors('product_id');
        $this->assertNull(session("cart.items.{$product->id}"));
    }

    public function test_cart_rejects_quantity_above_stock(): void
    {
        $product = $this->createProduct(stock: 1);

        $response = $this->from('/')->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertNull(session("cart.items.{$product->id}"));
    }

    public function test_json_cart_request_rejects_quantity_above_stock(): void
    {
        $product = $this->createProduct(stock: 1);
        session()->put("cart.items.{$product->id}", 1);

        $response = $this->postJson(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
        $this->assertSame(1, session("cart.items.{$product->id}"));
    }

    public function test_guest_can_update_cart_item_quantity(): void
    {
        $product = $this->createProduct(stock: 5);
        session()->put("cart.items.{$product->id}", 1);

        $response = $this->patch(route('cart.items.update', $product), [
            'quantity' => 4,
        ]);

        $response->assertRedirect();
        $this->assertSame(4, session("cart.items.{$product->id}"));
    }

    public function test_guest_can_update_cart_item_quantity_with_json_response(): void
    {
        $product = $this->createProduct(stock: 5);
        session()->put("cart.items.{$product->id}", 1);

        $response = $this->patchJson(route('cart.items.update', $product), [
            'quantity' => 4,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Корзина обновлена.',
                'cart_count' => 4,
                'item_quantity' => 4,
            ]);
        $this->assertSame(4, session("cart.items.{$product->id}"));
    }

    public function test_guest_can_remove_cart_item(): void
    {
        $product = $this->createProduct(stock: 5);
        session()->put("cart.items.{$product->id}", 2);

        $response = $this->delete(route('cart.items.destroy', $product));

        $response->assertRedirect();
        $this->assertNull(session("cart.items.{$product->id}"));
    }

    public function test_guest_can_remove_cart_item_with_json_response(): void
    {
        $product = $this->createProduct(stock: 5);
        session()->put("cart.items.{$product->id}", 2);

        $response = $this->deleteJson(route('cart.items.destroy', $product));

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Товар удалён из корзины.',
                'cart_count' => 0,
                'item_quantity' => 0,
            ]);
        $this->assertNull(session("cart.items.{$product->id}"));
    }

    private function createProduct(bool $isActive = true, int $stock = 0): Product
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'category'],
            ['name' => 'Категория', 'is_active' => true],
        );

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => fake()->unique()->word(),
            'slug' => fake()->unique()->slug(),
            'is_active' => $isActive,
            'stock' => $stock,
        ]);
    }
}
