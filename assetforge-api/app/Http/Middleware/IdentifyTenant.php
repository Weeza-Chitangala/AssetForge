<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        // Always resolve the authenticated user (including Bearer token before route auth middleware runs)
        $user = $request->user() ?? $request->user('sanctum');

        if ($user && $user->tenant_id) {
            // Never allow X-Tenant-ID header to override an authenticated user's tenant
            app()->instance('current_tenant_id', $user->tenant_id);
        } else {
            app()->forgetInstance('current_tenant_id');
        }

        return $next($request);
    }
}
