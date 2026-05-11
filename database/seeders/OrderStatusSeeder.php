<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['code' => 'pending_payment', 'name' => 'Ожидает оплаты', 'color' => 'warning', 'sort_order' => 10, 'is_final' => false],
            ['code' => 'paid', 'name' => 'Оплачен', 'color' => 'success', 'sort_order' => 20, 'is_final' => false],
            ['code' => 'processing', 'name' => 'В обработке', 'color' => 'info', 'sort_order' => 30, 'is_final' => false],
            ['code' => 'shipped', 'name' => 'Отгружен', 'color' => 'primary', 'sort_order' => 40, 'is_final' => false],
            ['code' => 'completed', 'name' => 'Завершён', 'color' => 'success', 'sort_order' => 50, 'is_final' => true],
            ['code' => 'cancelled', 'name' => 'Отменён', 'color' => 'secondary', 'sort_order' => 60, 'is_final' => true],
            ['code' => 'expired', 'name' => 'Истёк', 'color' => 'danger', 'sort_order' => 70, 'is_final' => true],
        ];

        foreach ($statuses as $status) {
            OrderStatus::query()->updateOrCreate(
                ['code' => $status['code']],
                $status,
            );
        }
    }
}
