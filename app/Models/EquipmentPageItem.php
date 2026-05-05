<?php

namespace App\Models;

use App\Support\PublicImageVariants;
use App\Support\PublicPageCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'image',
    'title',
    'description',
    'sort',
    'is_published',
])]
class EquipmentPageItem extends Model
{
    protected static function booted(): void
    {
        $invalidateCache = static function (): void {
            PublicPageCache::invalidateEquipment();
        };

        static::saved(function (self $item) use ($invalidateCache): void {
            $previousImage = $item->getPrevious()['image'] ?? null;

            if ($previousImage !== null && $previousImage !== $item->image) {
                PublicImageVariants::deleteStoredImage($previousImage);
            }
            $invalidateCache();
        });

        static::deleted(function (self $item) use ($invalidateCache): void {
            PublicImageVariants::deleteStoredImage($item->image);
            $invalidateCache();
        });
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
