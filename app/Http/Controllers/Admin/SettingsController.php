<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $tenant = \App\Models\Tenant::current();
        $tenants = Tenant::where('active', true)->orderBy('name')->get();
        return view('admin.settings.index', compact('tenant', 'tenants'));
    }
}
