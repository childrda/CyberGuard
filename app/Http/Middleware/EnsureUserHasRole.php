<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Check that the authenticated user has at least one of the given roles.
     * Roles: superadmin, campaign_admin, analyst, viewer
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            abort(401);
        }

        $user = $request->user();
        if (! $user->hasAnyRole($roles)) {
            abort(403, 'Insufficient permissions.');
        }

        return $next($request);
    }
}
