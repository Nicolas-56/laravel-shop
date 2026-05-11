<?php

namespace App\Models;

use App\Services\OrderStockService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'delivery_id',
    'order_status_id',
    'stock_reserved',
    'customer_name',
    'customer_phone',
    'customer_email',
    'customer_comment',
    'delivery_name',
    'delivery_price',
    'total',
])]
class Order extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PAID = 'paid';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_PENDING = self::STATUS_PENDING_PAYMENT;

    public const STATUS_AWAITING_PAYMENT = self::STATUS_PENDING_PAYMENT;

    public const STATUS_FAILED = self::STATUS_EXPIRED;

    public const STATUSES = [
        self::STATUS_PENDING_PAYMENT,
        self::STATUS_PAID,
        self::STATUS_PROCESSING,
        self::STATUS_SHIPPED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_EXPIRED,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $order): void {
            $rawStatus = $order->attributes['status'] ?? null;

            if (! filled($rawStatus) && ! filled($order->order_status_id)) {
                $order->order_status_id = self::statusId(self::STATUS_PENDING_PAYMENT);
            }

            if (filled($order->order_status_id)) {
                return;
            }

            if (filled($rawStatus)) {
                $order->order_status_id = self::statusId((string) $rawStatus);
                unset($order->attributes['status']);
            }
        });

        static::updated(function (self $order): void {
            if ($order->wasChanged('order_status_id')) {
                app(OrderStockService::class)->syncReservationForStatus($order->loadMissing('items'));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'stock_reserved' => 'boolean',
            'delivery_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getStatusAttribute(): ?string
    {
        if ($this->relationLoaded('orderStatus')) {
            return $this->orderStatus?->code;
        }

        if (! filled($this->order_status_id)) {
            return null;
        }

        return OrderStatus::query()
            ->whereKey($this->order_status_id)
            ->value('code');
    }

    public function setStatusAttribute(?string $status): void
    {
        $this->order_status_id = filled($status)
            ? self::statusId($status)
            : null;
    }

    public static function normalizeStatusCode(string $status): string
    {
        return match ($status) {
            'pending', 'awaiting_payment' => self::STATUS_PENDING_PAYMENT,
            'failed' => self::STATUS_EXPIRED,
            default => $status,
        };
    }

    public static function statusId(string $status): ?int
    {
        return OrderStatus::query()
            ->where('code', self::normalizeStatusCode($status))
            ->value('id');
    }
}
