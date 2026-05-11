<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class OrderStockService
{
    public function reserve(Order $order, ?Collection $products = null): void
    {
        if ($order->stock_reserved || ! Schema::hasColumn('products', 'stock')) {
            return;
        }

        $products ??= $this->loadProductsForOrder($order, lock: true);

        foreach ($order->items as $item) {
            /** @var Product|null $product */
            $product = $products->get($item->product_id);

            if (! $product || (int) $product->stock < (int) $item->quantity) {
                throw ValidationException::withMessages([
                    'cart' => 'В корзине есть товар в недостаточном количестве.',
                ]);
            }
        }

        foreach ($order->items as $item) {
            /** @var Product $product */
            $product = $products->get($item->product_id);

            $product->decrement('stock', (int) $item->quantity);
        }

        $order->updateQuietly([
            'stock_reserved' => true,
        ]);
    }

    public function restore(Order $order, ?Collection $products = null): void
    {
        if (! $order->stock_reserved || ! Schema::hasColumn('products', 'stock')) {
            return;
        }

        $products ??= $this->loadProductsForOrder($order, lock: true);

        foreach ($order->items as $item) {
            /** @var Product|null $product */
            $product = $products->get($item->product_id);

            if (! $product) {
                continue;
            }

            $product->increment('stock', (int) $item->quantity);
        }

        $order->updateQuietly([
            'stock_reserved' => false,
        ]);
    }

    public function syncReservationForStatus(Order $order): void
    {
        if (in_array($order->status, [Order::STATUS_CANCELLED, Order::STATUS_EXPIRED], true)) {
            DB::transaction(function () use ($order): void {
                $lockedOrder = Order::query()
                    ->with('items')
                    ->lockForUpdate()
                    ->findOrFail($order->getKey());

                $this->restore($lockedOrder);
            });

            return;
        }
    }

    private function loadProductsForOrder(Order $order, bool $lock = false): Collection
    {
        $query = Product::query()->whereKey($order->items->pluck('product_id')->all());

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()->keyBy('id');
    }
}
