<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantSwitcherController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $request->validate(['tenant_id' => ['required', 'integer', 'exists:tenants,id']]);
        $tenant = Tenant::findOrFail($request->tenant_id);
        if (! $tenant->active) {
            return redirect()->back()->with('error', 'Tenant is not active.');
        }
        $user = auth()->user();
        if ($user->tenant_id !== null && $user->tenant_id != $tenant->id && ! $user->hasRole('superadmin')) {
            return redirect()->back()->with('error', 'You cannot switch to that tenant.');
        }
        app()->instance('current_tenant_id', $tenant->id);
        session(['current_tenant_id' => $tenant->id]);
        return redirect()->back()->with('success', 'Switched to '.$tenant->name);
    }
}
