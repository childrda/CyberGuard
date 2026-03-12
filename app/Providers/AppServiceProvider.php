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

            // Warn when Report Phish webhook is likely unreachable (e.g. dev server not public)
            $webhookLikelyUnreachable = false;
            if (config('phishing.gmail_report_addon_enabled')) {
                $url = config('phishing.public_url') ?? config('app.url');
                $host = parse_url($url, PHP_URL_HOST) ?? '';
                $isPrivate = $host === '' || in_array($host, ['localhost', '127.0.0.1'], true)
                    || (str_starts_with($host, '127.') || str_starts_with($host, '10.') || str_starts_with($host, '192.168.')
                    || preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $host));
                $webhookLikelyUnreachable = $isPrivate;
            }
            $view->with('webhookLikelyUnreachable', $webhookLikelyUnreachable);
        });
    }
}
