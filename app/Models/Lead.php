<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'phone',
    'email',
    'service_id',
    'message',
    'status',
])]
class Lead extends Model
{
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
