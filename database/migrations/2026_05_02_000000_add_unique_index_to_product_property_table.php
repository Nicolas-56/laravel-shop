<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('product_property')
            ->select('product_id', 'property_id')
            ->whereNotNull('property_id')
            ->groupBy('product_id', 'property_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function (object $duplicate): void {
                $idsToDelete = DB::table('product_property')
                    ->where('product_id', $duplicate->product_id)
                    ->where('property_id', $duplicate->property_id)
                    ->orderByDesc('id')
                    ->pluck('id')
                    ->slice(1)
                    ->all();

                if ($idsToDelete === []) {
                    return;
                }

                DB::table('product_property')
                    ->whereIn('id', $idsToDelete)
                    ->delete();
            });

        Schema::table('product_property', function (Blueprint $table) {
            $table->unique(['product_id', 'property_id'], 'product_property_product_id_property_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_property', function (Blueprint $table) {
            $table->dropUnique('product_property_product_id_property_id_unique');
        });
    }
};
