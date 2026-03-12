<?php

use App\Http\Controllers\Admin\AttackAssetController;
use App\Http\Controllers\Admin\AttackController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeaderboardController;
use App\Http\Controllers\Admin\ScorePeriodController;
use App\Http\Controllers\Admin\RemediationController;
use App\Http\Controllers\Admin\ReportedMessageController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SystemLogController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\TenantSwitcherController;
use App\Http\Controllers\Admin\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'tenant', 'no.insecure.defaults'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/tenant-switch', [TenantSwitcherController::class, 'switch'])->name('tenant.switch');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('role:superadmin,campaign_admin,analyst,viewer');

    Route::middleware('role:superadmin,campaign_admin,analyst,viewer')->group(function () {
        Route::get('/reports', [ReportedMessageController::class, 'index'])->name('reports.index');
        Route::get('/reports/{reported}', [ReportedMessageController::class, 'show'])->name('reports.show');
        Route::get('/remediation', [RemediationController::class, 'index'])->name('remediation.index');
        Route::get('/remediation/{job}', [RemediationController::class, 'show'])->name('remediation.show');
        Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create')->middleware('role:superadmin,campaign_admin');
        Route::get('/campaigns/{campaign}/edit', [CampaignController::class, 'edit'])->name('campaigns.edit')->middleware('role:superadmin,campaign_admin');
        Route::get('/campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
        Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/{template}', [TemplateController::class, 'show'])->name('templates.show');
        Route::get('/attacks', [AttackController::class, 'index'])->name('attacks.index');
        Route::get('/attacks/{attack}', [AttackController::class, 'show'])->name('attacks.show');
        Route::get('/attacks/{attack}/preview', [AttackController::class, 'preview'])->name('attacks.preview');
        Route::get('/attacks/{attack}/validate', [AttackController::class, 'validateContent'])->name('attacks.validate');
        Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');
        Route::get('/score-periods', [ScorePeriodController::class, 'index'])->name('score-periods.index');
        Route::get('/score-periods/create', [ScorePeriodController::class, 'create'])->name('score-periods.create');
        Route::post('/score-periods', [ScorePeriodController::class, 'store'])->name('score-periods.store');
        Route::post('/score-periods/{period}/set-current', [ScorePeriodController::class, 'setCurrent'])->name('score-periods.set-current');
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('/system-log', [SystemLogController::class, 'index'])->name('system-log.index');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
        Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
        Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
        Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::post('/tenants/{tenant}/users', [TenantController::class, 'addUser'])->name('tenants.add-user');
        Route::get('/workspace/groups', [WorkspaceController::class, 'groups'])->name('workspace.groups')->middleware('role:superadmin,campaign_admin');
        Route::get('/workspace/ous', [WorkspaceController::class, 'ous'])->name('workspace.ous')->middleware('role:superadmin,campaign_admin');
        Route::post('/workspace/resolve', [WorkspaceController::class, 'resolve'])->name('workspace.resolve')->middleware('role:superadmin,campaign_admin');
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
        Route::resource('campaigns', CampaignController::class)->except(['index', 'show', 'create', 'edit']);
        Route::resource('templates', TemplateController::class)->except(['index', 'show']);
        Route::resource('attacks', AttackController::class)->except(['index', 'show']);
        Route::post('/attacks/{attack}/send-test', [AttackController::class, 'sendTest'])->name('attacks.send-test');
        Route::get('/attack-assets', [AttackAssetController::class, 'index'])->name('attack-assets.index');
        Route::post('/attack-assets', [AttackAssetController::class, 'store'])->name('attack-assets.store');
        Route::delete('/attack-assets/{asset}', [AttackAssetController::class, 'destroy'])->name('attack-assets.destroy');
        Route::post('/campaigns/{campaign}/approve', [CampaignController::class, 'approve'])->name('campaigns.approve');
        Route::post('/campaigns/{campaign}/launch', [CampaignController::class, 'launch'])->name('campaigns.launch');
        Route::post('/campaigns/{campaign}/cancel', [CampaignController::class, 'cancel'])->name('campaigns.cancel');
        Route::post('/campaigns/{campaign}/retry-failed', [CampaignController::class, 'retryFailed'])->name('campaigns.retry-failed');
    });
});
