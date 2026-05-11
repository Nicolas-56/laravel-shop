<?php

use App\Services\ExpirePendingOrdersService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:expire-unpaid', function () {
    $expiredOrders = app(ExpirePendingOrdersService::class)->handle();

    $this->info("Expired {$expiredOrders} unpaid orders.");
})->purpose('Expire unpaid pending_payment orders older than 30 minutes');

Schedule::command('orders:expire-unpaid')->everyFiveMinutes();
