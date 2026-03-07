<?php

namespace App\Providers;

use App\Models\ReportedMessage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $pendingReportsCount = 0;
            if (auth()->check() && \App\Models\Tenant::currentId() !== null) {
                $pendingReportsCount = ReportedMessage::whereIn('analyst_status', [null, 'pending'])
                    ->count();
            }
            $view->with('pendingReportsCount', $pendingReportsCount);
        });
    }
}
