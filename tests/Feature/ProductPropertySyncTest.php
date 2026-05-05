<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductPropertySyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_sync_category_property_values_upserts_standard_properties(): void
    {
        $category = Category::query()->create([
            'name' => 'Категория',
            'slug' => 'category',
        ]);

        $width = Property::query()->create([
            'name' => 'Ширина',
            'slug' => 'width',
            'type' => 'text',
            'is_active' => true,
        ]);

        $height = Property::query()->create([
            'name' => 'Высота',
            'slug' => 'height',
            'type' => 'text',
            'is_active' => true,
        ]);

        $category->properties()->attach($width->id, ['sort' => 10]);
        $category->properties()->attach($height->id, ['sort' => 20]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Товар',
            'slug' => 'product',
            'is_active' => true,
        ]);

        $product->syncCategoryPropertyValues([
            ['property_id' => $width->id, 'value' => '1200', 'sort' => 1],
            ['property_id' => $height->id, 'value' => '900', 'sort' => 2],
        ]);

        $product->syncCategoryPropertyValues([
            ['property_id' => $width->id, 'value' => '1400', 'sort' => 3],
            ['property_id' => $height->id, 'value' => '950', 'sort' => 4],
        ]);

        $this->assertDatabaseCount('product_property', 2);
        $this->assertDatabaseHas('product_property', [
            'product_id' => $product->id,
            'property_id' => $width->id,
            'value' => '1400',
            'sort' => 3,
        ]);
        $this->assertDatabaseHas('product_property', [
            'product_id' => $product->id,
            'property_id' => $height->id,
            'value' => '950',
            'sort' => 4,
        ]);
    }

    public function test_product_sync_category_properties_removes_stale_standard_properties_after_category_change(): void
    {
        $sourceCategory = Category::query()->create([
            'name' => 'Источник',
            'slug' => 'source',
        ]);

        $targetCategory = Category::query()->create([
            'name' => 'Назначение',
            'slug' => 'target',
        ]);

        $width = Property::query()->create([
            'name' => 'Ширина',
            'slug' => 'width',
            'type' => 'text',
            'is_active' => true,
        ]);

        $length = Property::query()->create([
            'name' => 'Длина',
            'slug' => 'length',
            'type' => 'text',
            'is_active' => true,
        ]);

        $sourceCategory->properties()->attach($width->id, ['sort' => 10]);
        $targetCategory->properties()->attach($length->id, ['sort' => 20]);

        $product = Product::query()->create([
            'category_id' => $sourceCategory->id,
            'name' => 'Товар',
            'slug' => 'product',
            'is_active' => true,
        ]);

        $product->syncCategoryPropertyValues([
            ['property_id' => $width->id, 'value' => '1200', 'sort' => 1],
        ]);

        $product->update([
            'category_id' => $targetCategory->id,
        ]);

        $product->syncCategoryProperties();

        $this->assertDatabaseMissing('product_property', [
            'product_id' => $product->id,
            'property_id' => $width->id,
        ]);
        $this->assertDatabaseHas('product_property', [
            'product_id' => $product->id,
            'property_id' => $length->id,
            'sort' => 20,
        ]);
    }

    public function test_product_sync_category_property_values_avoids_per_property_write_queries(): void
    {
        $category = Category::query()->create([
            'name' => 'Категория',
            'slug' => 'category',
        ]);

        $properties = collect([
            ['name' => 'Ширина', 'slug' => 'width'],
            ['name' => 'Высота', 'slug' => 'height'],
            ['name' => 'Толщина', 'slug' => 'thickness'],
        ])->map(function (array $data, int $index) use ($category) {
            $property = Property::query()->create([
                ...$data,
                'type' => 'text',
                'is_active' => true,
            ]);

            $category->properties()->attach($property->id, ['sort' => ($index + 1) * 10]);

            return $property;
        });

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Товар',
            'slug' => 'product',
            'is_active' => true,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $product->syncCategoryPropertyValues($properties->map(
            fn (Property $property, int $index): array => [
                'property_id' => $property->id,
                'value' => 'value-' . $property->id,
                'sort' => $index + 1,
            ]
        )->all());

        $productPropertyWrites = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(function (string $query): bool {
                $query = strtolower($query);

                return (str_contains($query, 'insert') || str_contains($query, 'update'))
                    && str_contains($query, 'product_property');
            });

        $this->assertLessThanOrEqual(2, $productPropertyWrites->count());
    }
}
