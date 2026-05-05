<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'type',
    'unit',
    'options',
    'is_active',
    'is_filter',
    'sort',
])]
class Property extends Model
{
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_active' => 'boolean',
            'is_filter' => 'boolean',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_property')
            ->withPivot(['is_required', 'sort'])
            ->withTimestamps();
    }

    public function productProperties(): HasMany
    {
        return $this->hasMany(ProductProperty::class);
    }
}
