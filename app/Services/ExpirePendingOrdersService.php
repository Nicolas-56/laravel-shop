<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class ExpirePendingOrdersService
{
    public function handle(): int
    {
        $pendingStatusId = Order::statusId(Order::STATUS_PENDING_PAYMENT);
        $expiredStatusId = Order::statusId(Order::STATUS_EXPIRED);
        $cutoff = now()->subMinutes(30);
        $expiredOrders = 0;

        if (! $pendingStatusId || ! $expiredStatusId) {
            return 0;
        }

        Order::query()
            ->where('order_status_id', $pendingStatusId)
            ->where('created_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($pendingStatusId, $expiredStatusId, &$expiredOrders): void {
                foreach ($orders as $order) {
                    $wasExpired = DB::transaction(function () use ($order, $pendingStatusId, $expiredStatusId): bool {
                        $lockedOrder = Order::query()
                            ->with(['items', 'payments'])
                            ->lockForUpdate()
                            ->findOrFail($order->getKey());

                        if ((int) $lockedOrder->order_status_id !== (int) $pendingStatusId) {
                            return false;
                        }

                        $lockedOrder->payments()
                            ->where('status', Payment::STATUS_PENDING)
                            ->update([
                                'status' => Payment::STATUS_FAILED,
                            ]);

                        $lockedOrder->update([
                            'order_status_id' => $expiredStatusId,
                        ]);

                        return true;
                    });

                    if ($wasExpired) {
                        $expiredOrders++;
                    }
                }
            });

        return $expiredOrders;
    }
}
