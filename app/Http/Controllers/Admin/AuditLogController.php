<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::with('user')
            ->when($request->input('action'), fn ($q) => $q->where('action', $request->action))
            ->when($request->input('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->input('correlation_id'), fn ($q) => $q->where('correlation_id', $request->correlation_id))
            ->when($request->input('from'), fn ($q) => $q->where('created_at', '>=', $request->from))
            ->when($request->input('to'), fn ($q) => $q->where('created_at', '<=', $request->to.' 23:59:59'))
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.audit.index', compact('logs'));
    }
}
