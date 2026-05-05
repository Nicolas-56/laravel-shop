<?php

namespace App\Models;

use App\Support\PublicImageVariants;
use App\Support\PublicPageCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title',
    'category_id',
    'image',
    'alt',
    'sort',
    'is_published',
])]
class Work extends Model
{
    protected static function booted(): void
    {
        $invalidateCache = static function (): void {
            PublicPageCache::invalidateWorks();
        };

        static::saved(function (self $work) use ($invalidateCache): void {
            $previousImage = $work->getPrevious()['image'] ?? null;

            if ($previousImage !== null && $previousImage !== $work->image) {
                PublicImageVariants::deleteStoredImage($previousImage);
            }
            $invalidateCache();
        });

        static::deleted(function (self $work) use ($invalidateCache): void {
            PublicImageVariants::deleteStoredImage($work->image);
            $invalidateCache();
        });
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkCategory::class, 'category_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('title');
    }
}
