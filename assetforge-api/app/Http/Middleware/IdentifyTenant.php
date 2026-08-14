<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = null;

        // 1. Resolve from Authenticated User
        if ($request->user() && $request->user()->tenant_id) {
            $tenantId = $request->user()->tenant_id;
        } 
        // 2. Fallback to HTTP Header
        elseif ($request->hasHeader('X-Tenant-ID')) {
            $tenantId = $request->header('X-Tenant-ID');
        }

        if ($tenantId) {
            app()->instance('current_tenant_id', $tenantId);
        }

        return $next($request);
    }
}