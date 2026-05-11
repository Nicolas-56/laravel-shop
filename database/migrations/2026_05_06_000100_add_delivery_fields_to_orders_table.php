<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('delivery_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('delivery_name')->nullable()->after('customer_comment');
            $table->decimal('delivery_price', 10, 2)->default(0)->after('delivery_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_id');
            $table->dropColumn(['delivery_name', 'delivery_price']);
        });
    }
};
