<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('color', 30)->default('gray');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });

        $now = now();

        DB::table('order_statuses')->insert([
            ['code' => 'pending_payment', 'name' => 'Ожидает оплаты', 'color' => 'warning', 'sort_order' => 10, 'is_final' => false, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'paid', 'name' => 'Оплачен', 'color' => 'success', 'sort_order' => 20, 'is_final' => false, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'processing', 'name' => 'В обработке', 'color' => 'info', 'sort_order' => 30, 'is_final' => false, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'shipped', 'name' => 'Отгружен', 'color' => 'primary', 'sort_order' => 40, 'is_final' => false, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'completed', 'name' => 'Завершён', 'color' => 'success', 'sort_order' => 50, 'is_final' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'cancelled', 'name' => 'Отменён', 'color' => 'secondary', 'sort_order' => 60, 'is_final' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'expired', 'name' => 'Истёк', 'color' => 'danger', 'sort_order' => 70, 'is_final' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('order_statuses');
    }
};
