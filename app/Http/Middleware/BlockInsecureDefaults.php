<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockInsecureDefaults
{
    private const INSECURE_DOMAIN = 'example.com';

    private const INSECURE_EMAILS = [
        'platform_admin@example.com',
        'admin@example.com',
        'viewer@example.com',
    ];

    /**
     * In non-local environments, block admin access if default tenant or default users still exist.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            return $next($request);
        }

        $hasInsecureTenant = Tenant::where('domain', self::INSECURE_DOMAIN)->exists();
        $hasInsecureUser = User::whereIn('email', self::INSECURE_EMAILS)->exists();

        if ($hasInsecureTenant || $hasInsecureUser) {
            abort(503, 'Insecure defaults detected (e.g. default tenant or example.com users). Run: php artisan cyberguard:install to create a proper admin, then remove default tenant/users from the database if they were created by an old seeder.');
        }

        return $next($request);
    }
}
