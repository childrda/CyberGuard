<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use Illuminate\View\View;

class SystemLogController extends Controller
{
    public function index(): View
    {
        $logs = SystemLog::with('tenant')
            ->when(! auth()->user()?->isPlatformAdmin(), function ($q) {
                $q->where('tenant_id', \App\Models\Tenant::currentId());
            })
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.system-log.index', compact('logs'));
    }
}
