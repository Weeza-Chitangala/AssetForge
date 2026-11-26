<?php

namespace App\Http\Middleware;

use App\Services\RoleHierarchyService;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureActiveSession
 *
 * Verifies on every authenticated API request that the user account and
 * (for tenant-scoped users) its parent tenant remain active, revoking the
 * active token immediately if either was deactivated after token issuance.
 */
class EnsureActiveSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->status !== 'active') {
            $this->revokeCurrentToken($user);

            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive or suspended.',
            ], 401);
        }

        $isSuperUser = app(RoleHierarchyService::class)->isSuperUser($user);
        if (! $isSuperUser) {
            $tenant = $user->tenant;
            if (! $tenant || $tenant->status !== 'active') {
                $this->revokeCurrentToken($user);

                return response()->json([
                    'success' => false,
                    'message' => 'Your organisation tenant is currently inactive or suspended.',
                ], 401);
            }
        }

        return $next($request);
    }

    protected function revokeCurrentToken(mixed $user): void
    {
        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
