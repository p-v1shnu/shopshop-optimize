<?php

use App\Http\Controllers\CloudflareController;
use App\Http\Controllers\HALController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {

        Route::post('/webhooks/bcel', [WebhookController::class, 'apiUpdateBcel'])->name('api.webhook.updateBcel');
        Route::post('/webhooks/jdb', [WebhookController::class, 'apiUpdateJdb'])->name('api.webhook.updateJdb');

        Route::get('/webhooks/hal', [HALController::class, 'webhookGet'])->name('hal.webhook.get');
        Route::post('/webhooks/hal', [HALController::class, 'webhookPost'])->name('hal.webhook.post');

        Route::post('/flush-cache', [CloudflareController::class, 'flushCache'])->name('api.flush-cache');

    });
}
