<?php

namespace App\Models;

use App\Support\PublicImageVariants;
use App\Support\PublicPageCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'image',
    'short_description',
    'description',
    'meta_title',
    'meta_description',
    'sort',
    'is_published',
])]
class WorkCategory extends Model
{
    protected $table = 'category_work';

    protected static function booted(): void
    {
        $invalidateCache = static function (): void {
            PublicPageCache::invalidateWorks();
        };

        static::saved(function (self $category) use ($invalidateCache): void {
            $previousImage = $category->getPrevious()['image'] ?? null;

            if ($previousImage !== null && $previousImage !== $category->image) {
                PublicImageVariants::deleteStoredImage($previousImage);
            }
            $invalidateCache();
        });

        static::deleted(function (self $category) use ($invalidateCache): void {
            PublicImageVariants::deleteStoredImage($category->image);
            $invalidateCache();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->resolveRouteBindingQuery($this->newQuery(), $value, $field)
            ->published()
            ->first();
    }

    public function resolveRouteBindingQuery($query, $value, $field = null): Builder
    {
        $field ??= $this->getRouteKeyName();

        return $query->where($field, $value);
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function works(): HasMany
    {
        return $this->hasMany(Work::class, 'category_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('name');
    }
}
