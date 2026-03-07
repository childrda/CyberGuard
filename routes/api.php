<?php

use App\Http\Controllers\Api\ReportWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:120,1'])->post('/webhook/report', ReportWebhookController::class)->name('api.webhook.report');

Route::middleware(['auth:sanctum'])->group(function () {
    // Future: API for internal admin tools
});
