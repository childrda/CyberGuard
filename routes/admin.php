<?php

use App\Http\Controllers\Admin\AttackController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeaderboardController;
use App\Http\Controllers\Admin\RemediationController;
use App\Http\Controllers\Admin\ReportedMessageController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Admin\TenantSwitcherController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/tenant-switch', [TenantSwitcherController::class, 'switch'])->name('tenant.switch');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('role:superadmin,campaign_admin,analyst,viewer');

    Route::middleware('role:superadmin,campaign_admin,analyst,viewer')->group(function () {
        Route::get('/reports', [ReportedMessageController::class, 'index'])->name('reports.index');
        Route::get('/reports/{reported}', [ReportedMessageController::class, 'show'])->name('reports.show');
        Route::get('/remediation', [RemediationController::class, 'index'])->name('remediation.index');
        Route::get('/remediation/{job}', [RemediationController::class, 'show'])->name('remediation.show');
        Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
        Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/{template}', [TemplateController::class, 'show'])->name('templates.show');
        Route::get('/attacks', [AttackController::class, 'index'])->name('attacks.index');
        Route::get('/attacks/{attack}', [AttackController::class, 'show'])->name('attacks.show');
        Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    });

    Route::middleware('role:superadmin,campaign_admin,analyst')->group(function () {
        Route::post('/reports/{reported}/confirm-real', [ReportedMessageController::class, 'confirmReal'])->name('reports.confirm-real');
        Route::post('/reports/{reported}/confirm-false-positive', [ReportedMessageController::class, 'confirmFalsePositive'])->name('reports.confirm-false-positive');
        Route::post('/reports/{reported}/remove-reporter', [ReportedMessageController::class, 'removeFromReporterMailbox'])->name('reports.remove-reporter');
        Route::post('/reports/{reported}/remove-all', [ReportedMessageController::class, 'removeFromAllMailboxes'])->name('reports.remove-all');
    });

    Route::middleware('role:superadmin,campaign_admin,analyst')->group(function () {
        Route::post('/remediation/{reported}/approve', [RemediationController::class, 'approve'])->name('remediation.approve');
        Route::post('/remediation/{reported}/run', [RemediationController::class, 'run'])->name('remediation.run');
    });

    Route::middleware('role:superadmin,campaign_admin')->group(function () {
        Route::resource('campaigns', CampaignController::class)->except(['index', 'show']);
        Route::resource('templates', TemplateController::class)->except(['index', 'show']);
        Route::resource('attacks', AttackController::class)->except(['index', 'show']);
        Route::post('/campaigns/{campaign}/approve', [CampaignController::class, 'approve'])->name('campaigns.approve');
        Route::post('/campaigns/{campaign}/launch', [CampaignController::class, 'launch'])->name('campaigns.launch');
    });
});
