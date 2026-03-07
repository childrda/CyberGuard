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

        if ($tenantId && Tenant::find($tenantId)) {
            app()->instance('current_tenant_id', $tenantId);
        } else {
            app()->instance('current_tenant_id', null);
        }

        return $next($request);
    }
}
