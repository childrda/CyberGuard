<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = session('current_tenant_id');

        if (! $tenantId && auth()->check() && auth()->user()->tenant_id) {
            $tenantId = auth()->user()->tenant_id;
            session(['current_tenant_id' => $tenantId]);
        }

        $tenant = $tenantId ? Tenant::find($tenantId) : null;
        if (auth()->check()) {
            $user = auth()->user();
            $allowed = $tenant && (
                $user->isPlatformAdmin()
                || $user->tenant_id === $tenant->id
            );
            if (! $allowed) {
                $tenantId = $user->tenant_id;
                session(['current_tenant_id' => $tenantId]);
                $tenant = $tenantId ? Tenant::find($tenantId) : null;
            }
        }

        if ($tenant && $tenant->active) {
            app()->instance('current_tenant_id', $tenant->id);
            $request->attributes->set('current_tenant_id', $tenant->id);
        } else {
            app()->instance('current_tenant_id', null);
            $request->attributes->set('current_tenant_id', null);
        }

        return $next($request);
    }
}
