<?php

use App\Console\Commands\CleanUnPaidOrders;
use App\Console\Commands\SendInvoiceWebhook;

Schedule::command(SendInvoiceWebhook::class)
    ->everyThirtySeconds()
    ->evenInMaintenanceMode()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(CleanUnPaidOrders::class)
    ->everyThirtySeconds()
    ->evenInMaintenanceMode()
    ->withoutOverlapping()
    ->onOneServer();
