<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

#[Fillable([
    'category_id',
    'name',
    'slug',
    'article',
    'short_description',
    'description',
    'price',
    'stock',
    'is_active',
    'sort',
    'meta_title',
    'meta_description',
])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function productImages(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort')->orderBy('id');
    }

    public function mainImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)
            ->ofMany([
                'is_main' => 'max',
                'sort' => 'min',
                'id' => 'min',
            ]);
    }

    public function productProperties(): HasMany
    {
        return $this->hasMany(ProductProperty::class)->orderBy('sort')->orderBy('id');
    }

    public function standardProductProperties(): HasMany
    {
        return $this->hasMany(ProductProperty::class)
            ->whereNotNull('property_id')
            ->orderBy('sort')
            ->orderBy('id');
    }

    public function customProductProperties(): HasMany
    {
        return $this->hasMany(ProductProperty::class)
            ->whereNull('property_id')
            ->orderBy('sort')
            ->orderBy('id');
    }

    public function inquiries(): BelongsToMany
    {
        return $this->belongsToMany(Inquiry::class, 'inquiry_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function syncCategoryProperties(): void
    {
        if (! $this->category_id) {
            return;
        }

        $categoryProperties = $this->categoryPropertiesForSync();

        $this->standardProductProperties()
            ->when(
                $categoryProperties->isNotEmpty(),
                fn ($query) => $query->whereNotIn('property_id', $categoryProperties->keys()->all()),
            )
            ->delete();

        $existingPropertyIds = $this->standardProductProperties()
            ->pluck('property_id')
            ->map(fn (mixed $propertyId): int => (int) $propertyId)
            ->all();

        $missingPropertyIds = array_diff($categoryProperties->keys()->all(), $existingPropertyIds);

        if ($missingPropertyIds === []) {
            return;
        }

        $now = now();

        ProductProperty::query()->insert(
            array_map(fn (int $propertyId): array => [
                'product_id' => $this->getKey(),
                'property_id' => $propertyId,
                'sort' => (int) $categoryProperties->get($propertyId, 0),
                'created_at' => $now,
                'updated_at' => $now,
            ], $missingPropertyIds)
        );
    }

    public function syncCategoryPropertyValues(array $items): void
    {
        $this->syncCategoryProperties();

        $categoryProperties = $this->categoryPropertiesForSync();

        if ($categoryProperties->isEmpty()) {
            return;
        }

        $existingProperties = $this->standardProductProperties()
            ->get(['property_id', 'value', 'sort'])
            ->keyBy(fn (ProductProperty $item): int => (int) $item->property_id);

        $payload = collect($items)
            ->filter(fn (mixed $item): bool => is_array($item) && filled($item['property_id'] ?? null))
            ->keyBy(fn (array $item): int => (int) $item['property_id']);

        $now = now();

        ProductProperty::query()->upsert(
            $categoryProperties
                ->map(function (int $defaultSort, int $propertyId) use ($existingProperties, $payload, $now): array {
                    $current = $existingProperties->get($propertyId);
                    $incoming = $payload->get($propertyId, []);

                    return [
                        'product_id' => $this->getKey(),
                        'property_id' => $propertyId,
                        'value' => $incoming['value'] ?? $current?->value,
                        'sort' => (int) ($incoming['sort'] ?? $current?->sort ?? $defaultSort),
                        'created_at' => $current?->created_at ?? $now,
                        'updated_at' => $now,
                    ];
                })
                ->values()
                ->all(),
            ['product_id', 'property_id'],
            ['value', 'sort', 'updated_at']
        );
    }

    private function categoryPropertiesForSync(): Collection
    {
        return $this->category()
            ->firstOrFail()
            ->properties()
            ->withPivot('sort')
            ->get(['properties.id'])
            ->mapWithKeys(fn (Property $property): array => [
                (int) $property->getKey() => (int) ($property->pivot->sort ?? 0),
            ]);
    }
}
