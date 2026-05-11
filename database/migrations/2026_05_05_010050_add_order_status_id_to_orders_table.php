<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('order_status_id')
                ->nullable()
                ->after('status')
                ->constrained('order_statuses')
                ->nullOnDelete();
        });

        $statuses = DB::table('order_statuses')
            ->pluck('id', 'code');

        $statusMap = [
            'pending' => 'pending_payment',
            'awaiting_payment' => 'pending_payment',
            'paid' => 'paid',
            'failed' => 'expired',
            'cancelled' => 'cancelled',
            'expired' => 'expired',
            'processing' => 'processing',
            'shipped' => 'shipped',
            'completed' => 'completed',
            'pending_payment' => 'pending_payment',
        ];

        foreach ($statusMap as $legacyStatus => $statusCode) {
            $statusId = $statuses[$statusCode] ?? null;

            if ($statusId === null) {
                continue;
            }

            DB::table('orders')
                ->where('status', $legacyStatus)
                ->update([
                    'status' => $statusCode,
                    'order_status_id' => $statusId,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_status_id');
        });
    }
};
