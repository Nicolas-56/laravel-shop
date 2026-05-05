<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'parent_id',
    'name',
    'slug',
    'is_active',
    'sort',
    'meta_title',
    'meta_description',
    'seo_text',
])]
class Category extends Model
{
    protected static function booted(): void
    {
        static::saving(function (Category $category): void {
            if (! $category->parent_id) {
                return;
            }

            if ($category->parent_id === $category->getKey() || $category->hasDescendant((int) $category->parent_id)) {
                throw new \LogicException('Category parent cannot be the category itself or its descendant.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected function fullSlug(): Attribute
    {
        return Attribute::get(function (): string {
            $segments = [$this->slug];
            $parent = $this->parent;

            while ($parent) {
                array_unshift($segments, $parent->slug);
                $parent = $parent->parent;
            }

            return implode('/', $segments);
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort')->orderBy('name');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'category_property')
            ->withPivot(['is_required', 'sort'])
            ->withTimestamps();
    }

    public function hasDescendant(int $categoryId): bool
    {
        $childrenByParent = self::query()
            ->whereNotNull('parent_id')
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id');

        $stack = $childrenByParent
            ->get($this->getKey(), collect())
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        while ($stack !== []) {
            $currentId = array_pop($stack);

            if ($currentId === $categoryId) {
                return true;
            }

            foreach ($childrenByParent->get($currentId, collect()) as $child) {
                $stack[] = (int) $child->id;
            }
        }

        return false;
    }
}
