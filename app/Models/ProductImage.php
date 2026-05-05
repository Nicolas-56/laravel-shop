<?php

namespace App\Models;

use App\Support\PublicImageVariants;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'path',
    'alt',
    'sort',
    'is_main',
])]
class ProductImage extends Model
{
    protected static function booted(): void
    {
        static::saved(function (self $productImage): void {
            $previousPath = $productImage->getPrevious()['path'] ?? null;

            if ($previousPath !== null && $previousPath !== $productImage->path) {
                PublicImageVariants::deleteStoredImage($previousPath);
            }
        });

        static::deleted(function (self $productImage): void {
            PublicImageVariants::deleteStoredImage($productImage->path);
        });
    }

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
