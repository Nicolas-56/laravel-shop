<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMainImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_main_image_prefers_explicit_main_image(): void
    {
        $product = $this->createProduct();

        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => 'products/secondary.jpg',
            'sort' => 2,
            'is_main' => false,
        ]);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => 'products/main.jpg',
            'sort' => 10,
            'is_main' => true,
        ]);

        $this->assertSame('products/main.jpg', $product->fresh()->mainImage?->path);
    }

    public function test_product_main_image_falls_back_to_first_sorted_image(): void
    {
        $product = $this->createProduct();

        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => 'products/second.jpg',
            'sort' => 5,
            'is_main' => false,
        ]);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => 'products/first.jpg',
            'sort' => 1,
            'is_main' => false,
        ]);

        $this->assertSame('products/first.jpg', $product->fresh()->mainImage?->path);
    }

    protected function createProduct(): Product
    {
        $category = Category::query()->create([
            'name' => 'Категория',
            'slug' => 'category',
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Товар',
            'slug' => 'product',
            'is_active' => true,
        ]);
    }
}
