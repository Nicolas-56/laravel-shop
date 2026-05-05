<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_builds_full_slug_from_parents(): void
    {
        $parent = Category::query()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $child = Category::query()->create([
            'parent_id' => $parent->id,
            'name' => 'Laptops',
            'slug' => 'laptops',
        ]);

        $grandchild = Category::query()->create([
            'parent_id' => $child->id,
            'name' => 'Gaming',
            'slug' => 'gaming',
        ]);

        $this->assertSame('electronics', $parent->full_slug);
        $this->assertSame('electronics/laptops', $child->full_slug);
        $this->assertSame('electronics/laptops/gaming', $grandchild->full_slug);
    }

    public function test_category_cannot_use_descendant_as_parent(): void
    {
        $parent = Category::query()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $child = Category::query()->create([
            'parent_id' => $parent->id,
            'name' => 'Laptops',
            'slug' => 'laptops',
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Category parent cannot be the category itself or its descendant.');

        $parent->update([
            'parent_id' => $child->id,
        ]);
    }

    public function test_category_descendant_validation_uses_single_categories_select(): void
    {
        $parent = Category::query()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $child = Category::query()->create([
            'parent_id' => $parent->id,
            'name' => 'Laptops',
            'slug' => 'laptops',
        ]);

        Category::query()->create([
            'parent_id' => $child->id,
            'name' => 'Gaming',
            'slug' => 'gaming',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $parent->update([
                'parent_id' => $child->id,
            ]);
        } catch (\LogicException) {
        }

        $categoriesSelects = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(function (string $query): bool {
                $query = strtolower($query);

                return str_contains($query, 'select')
                    && str_contains($query, 'from "categories"');
            });

        $this->assertCount(1, $categoriesSelects);
    }
}
