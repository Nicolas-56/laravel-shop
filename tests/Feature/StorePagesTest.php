<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_public_store_pages_render(): void
    {
        $product = $this->createProduct();

        $this->get(route('store.home'))
            ->assertRedirect(route('catalog.index'));

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('Каталог оборудования и комплектующих')
            ->assertSee('metal-panel', false)
            ->assertSee('href="'.route('catalog.index').'"', false)
            ->assertSee(route('products.show', $product->slug), false)
            ->assertSee('data-cart-quantity-control', false)
            ->assertSee('data-cart-feedback', false);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee($product->name);
    }

    public function test_catalog_category_uses_clean_slug_url(): void
    {
        $product = $this->createProduct();

        $this->get(route('catalog.category', $product->category->slug))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee(route('catalog.category', $product->category->slug), false)
            ->assertSee('<link rel="canonical" href="'.route('catalog.category', $product->category->slug).'">', false)
            ->assertSee('Каталог')
            ->assertSee($product->category->name);
    }

    public function test_catalog_product_card_renders_session_cart_quantity(): void
    {
        $product = $this->createProduct(stock: 4);
        session()->put("cart.items.{$product->id}", 2);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('data-cart-quantity-control', false)
            ->assertSee('data-product-id="'.$product->id.'"', false)
            ->assertSee('value="2"', false);
    }

    public function test_category_breadcrumbs_use_hierarchy_without_nested_urls(): void
    {
        $parent = Category::query()->create([
            'name' => 'Электроника',
            'slug' => 'electronics',
            'is_active' => true,
        ]);
        $category = Category::query()->create([
            'parent_id' => $parent->id,
            'name' => 'Ноутбуки',
            'slug' => 'laptops',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Ultrabook X1',
            'slug' => 'ultrabook-x1',
            'article' => 'UX1',
            'price' => 100,
            'stock' => 1,
            'is_active' => true,
        ]);

        $this->get(route('catalog.category', $category->slug))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee(route('catalog.category', $parent->slug), false)
            ->assertSee(route('catalog.category', $category->slug), false)
            ->assertDontSee('/catalog/electronics/laptops', false);

        $this->get(route('catalog.index', ['category' => $category->full_slug]))
            ->assertRedirect(route('catalog.category', $category->slug));
    }

    public function test_product_uses_clean_slug_url_with_breadcrumbs_and_canonical(): void
    {
        $product = $this->createProduct();

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('products.show', $product->slug).'">', false)
            ->assertSee(route('catalog.category', $product->category->slug), false)
            ->assertSee($product->category->name)
            ->assertSee($product->name);
    }

    public function test_legacy_catalog_query_redirects_to_clean_category_url(): void
    {
        $product = $this->createProduct();

        $this->get(route('catalog.index', ['category' => $product->category->slug, 'q' => 'UX1']))
            ->assertRedirect(route('catalog.category', ['category' => $product->category->slug, 'q' => 'UX1']));
    }

    public function test_legacy_product_url_redirects_to_clean_product_url(): void
    {
        $product = $this->createProduct();

        $this->get(route('products.legacy.show', $product->slug))
            ->assertRedirect(route('products.show', $product->slug));
    }

    public function test_invalid_store_slugs_return_not_found(): void
    {
        $this->get('/catalog/missing-category')->assertNotFound();
        $this->get('/product/missing-product')->assertNotFound();
    }

    public function test_cart_and_checkout_pages_render_session_cart(): void
    {
        $product = $this->createProduct(price: 1500, stock: 4);
        session()->put("cart.items.{$product->id}", 2);

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('3 000.00')
            ->assertSee('data-cart-quantity-control', false)
            ->assertSee('data-cart-quantity-decrease', false)
            ->assertSee('data-cart-quantity-increase', false)
            ->assertSee('value="2"', false)
            ->assertSee('readonly', false)
            ->assertSee('в корзине');

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Оформление заказа')
            ->assertSee($product->name)
            ->assertSee('data-phone-mask', false)
            ->assertSee('placeholder="+7 (___) ___-__-__"', false)
            ->assertSee('data-checkout-delivery', false)
            ->assertSee('data-checkout-summary', false)
            ->assertSee('data-checkout-grand-total', false)
            ->assertSee('3 000.00');
    }

    public function test_user_order_pages_render_store_layout(): void
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_status_id' => Order::statusId(Order::STATUS_PENDING),
            'customer_name' => 'Ivan',
            'customer_phone' => '+7 999 111-22-33',
            'total' => 1000,
        ]);
        $order->items()->create([
            'product_name' => 'USB-C Dock',
            'product_price' => 1000,
            'quantity' => 1,
            'line_total' => 1000,
        ]);

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee("Заказ #{$order->id}");

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('USB-C Dock');
    }

    private function createProduct(float $price = 100, int $stock = 1): Product
    {
        $category = Category::query()->create([
            'name' => 'Ноутбуки',
            'slug' => 'laptops',
            'is_active' => true,
            'sort' => 0,
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Ultrabook X1',
            'slug' => 'ultrabook-x1',
            'article' => 'UX1',
            'price' => $price,
            'stock' => $stock,
            'is_active' => true,
            'sort' => 0,
        ]);
    }
}
