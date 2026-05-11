<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CommerceOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $paidRevenue = Order::query()
            ->whereHas('orderStatus', fn ($query) => $query->where('code', Order::STATUS_PAID))
            ->sum('total');

        return [
            Stat::make('Заказы', Order::query()->count()),
            Stat::make('Оплаченные платежи', Payment::query()->where('status', Payment::STATUS_PAID)->count()),
            Stat::make('Выручка', number_format((float) $paidRevenue, 2, '.', ' ').' руб.'),
            Stat::make('Товары', Product::query()->count()),
        ];
    }
}
