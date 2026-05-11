<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_active_scope_returns_only_active_products(): void
    {
        $category = $this->createCategory();

        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Активный товар',
            'slug' => 'active-product',
            'is_active' => true,
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Неактивный товар',
            'slug' => 'inactive-product',
            'is_active' => false,
        ]);

        $this->assertSame(['Активный товар'], Product::query()->active()->pluck('name')->all());
    }

    public function test_product_in_stock_scope_returns_only_products_with_stock(): void
    {
        $category = $this->createCategory();

        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'В наличии',
            'slug' => 'in-stock-product',
            'stock' => 3,
            'is_active' => true,
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Нет в наличии',
            'slug' => 'out-of-stock-product',
            'stock' => 0,
            'is_active' => true,
        ]);

        $this->assertSame(['В наличии'], Product::query()->inStock()->pluck('name')->all());
    }

    public function test_category_in_stock_scope_returns_categories_with_active_in_stock_products(): void
    {
        $availableCategory = $this->createCategory('В наличии', 'available-category');
        $emptyCategory = $this->createCategory('Пустая', 'empty-category');

        Product::query()->create([
            'category_id' => $availableCategory->id,
            'name' => 'В наличии',
            'slug' => 'available-product',
            'stock' => 1,
            'is_active' => true,
        ]);

        Product::query()->create([
            'category_id' => $emptyCategory->id,
            'name' => 'Неактивный товар',
            'slug' => 'inactive-product',
            'stock' => 1,
            'is_active' => false,
        ]);

        $this->assertSame(['В наличии'], Category::query()->inStock()->pluck('name')->all());
    }

    private function createCategory(string $name = 'Категория', string $slug = 'category'): Category
    {
        return Category::query()->create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
        ]);
    }
}
