<?php

use App\Http\Controllers\TrackingController;
use App\Http\Controllers\TrainingViewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Public tracking routes (rate limited in bootstrap or middleware)
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/t/{token}', [TrackingController::class, 'click'])->name('tracking.click');
    Route::get('/t/{token}/open', [TrackingController::class, 'open'])->name('tracking.open');
    Route::post('/t/{token}/submit', [TrackingController::class, 'submit'])->name('tracking.submit');
    Route::get('/t/{token}/capture', [TrainingViewController::class, 'capture'])->name('training.capture');
});

Route::get('/training/{token}', [TrainingViewController::class, 'show'])->name('training.show');
Route::get('/training/thanks', [TrainingViewController::class, 'thanks'])->name('training.thanks');
Route::get('/training/unknown', function () {
    return view('training.show', [
        'content' => '<h1>Training</h1><p>This was a simulated exercise. Learn to report suspicious emails.</p>',
        'showBanner' => true,
        'token' => 'unknown',
    ]);
})->name('training.unknown');

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
