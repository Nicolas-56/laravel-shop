<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'phone',
    'email',
    'comment',
    'status',
])]
class Inquiry extends Model
{
    public function inquiryItems(): HasMany
    {
        return $this->hasMany(InquiryItem::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'inquiry_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
