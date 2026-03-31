<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemLogController extends Controller
{
    public function index(Request $request): View
    {
        $allowedPerPage = [10, 20, 40, 100];
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $logs = SystemLog::with('tenant')
            ->when(! auth()->user()?->isPlatformAdmin(), function ($q) {
                $q->where('tenant_id', \App\Models\Tenant::currentId());
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->appends($request->query());

        return view('admin.system-log.index', compact('logs', 'perPage', 'allowedPerPage'));
    }
}
