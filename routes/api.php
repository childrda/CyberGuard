<?php

use App\Http\Controllers\Api\ReportWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:120,1'])->post('/webhook/report', ReportWebhookController::class)->name('api.webhook.report');

// Placeholder: no authenticated API routes yet. Admin UI uses web routes + session.
Route::middleware(['auth:sanctum'])->group(function () {
    //
});
