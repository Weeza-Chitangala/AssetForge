<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * AuthService
 *
 * Handles credential verification, DoS-resistant distributed brute-force protection,
 * account and tenant status enforcement, Sanctum token issuance/revocation, and
 * authentication audit trails.
 */
class AuthService
{
    private const DISTRIBUTED_FAILURE_LIMIT = 10;
    private const DISTRIBUTED_DECAY_SECONDS = 300;
    private const KNOWN_IP_TTL_DAYS = 30;

    public function __construct(
        private readonly RoleHierarchyService $roleHierarchy,
    ) {
    }

    public function login(string $email, string $password): array
    {
        $normalizedEmail = strtolower(trim($email));
        $ip = (string) (request()->ip() ?? 'unknown');
        $distributedKey = 'login-distributed:' . sha1($normalizedEmail);

        $user = User::with('tenant')
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        $isKnownIp = $user !== null && Cache::has($this->knownIpCacheKey($user->id, $ip));

        // Block distributed credential stuffing across unknown IPs without locking out known legitimate client IPs.
        if (! $isKnownIp && RateLimiter::tooManyAttempts($distributedKey, self::DISTRIBUTED_FAILURE_LIMIT)) {
            $this->logFailedLogin($normalizedEmail, 'distributed_rate_limited', $user);

            throw new TooManyRequestsHttpException(
                RateLimiter::availableIn($distributedKey),
                'Too many failed login attempts from unrecognised locations. Please try again later.'
            );
        }

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($distributedKey, self::DISTRIBUTED_DECAY_SECONDS);
            $this->logFailedLogin($normalizedEmail, 'invalid_credentials', $user);

            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        if ($user->status !== 'active') {
            $this->logFailedLogin($normalizedEmail, 'user_' . ($user->status ?: 'inactive'), $user);

            throw new AuthorizationException('Your account is inactive or suspended. Please contact your Administrator.');
        }

        // Platform Super Users remain able to access the platform even if their assigned tenant is suspended.
        if (! $this->roleHierarchy->isSuperUser($user) && (! $user->tenant || $user->tenant->status !== 'active')) {
            $this->logFailedLogin($normalizedEmail, 'tenant_' . ($user->tenant?->status ?: 'inactive'), $user);

            throw new AuthorizationException('Your organisation tenant is currently inactive or suspended. Please contact support.');
        }

        Cache::put($this->knownIpCacheKey($user->id, $ip), true, now()->addDays(self::KNOWN_IP_TTL_DAYS));

        $token = $user->createToken('assetforge-token')->plainTextToken;

        activity('auth')
            ->causedBy($user)
            ->event('login')
            ->withProperties([
                'tenant_id' => $user->tenant_id,
                'ip' => $ip,
                'user_agent' => request()->userAgent(),
            ])
            ->log('User logged in');

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    protected function knownIpCacheKey(int $userId, string $ip): string
    {
        return "login-known-ip:{$userId}:" . sha1($ip);
    }

    public function logout(User $user): void
    {
        activity('auth')
            ->causedBy($user)
            ->event('logout')
            ->withProperties([
                'tenant_id' => $user->tenant_id,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('User logged out');

        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /**
     * Revoke all active Sanctum tokens belonging to the authenticated user.
     */
    public function logoutAll(User $user): int
    {
        $revokedCount = $user->tokens()->count();
        $user->tokens()->delete();

        activity('auth')
            ->causedBy($user)
            ->event('logout_all')
            ->withProperties([
                'tenant_id' => $user->tenant_id,
                'revoked_tokens' => $revokedCount,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('User revoked all active sessions');

        return $revokedCount;
    }

    /**
     * Revoke all active Sanctum tokens for a target user due to security/lifecycle events.
     */
    public function revokeUserTokens(User $user, string $reason, ?User $causer = null): int
    {
        $revokedCount = $user->tokens()->count();
        if ($revokedCount < 1) {
            return 0;
        }

        $user->tokens()->delete();

        $effectiveCauser = $causer ?? $user;
        $properties = [
            'tenant_id' => $user->tenant_id !== null ? (string) $user->tenant_id : null,
            'reason' => $reason,
            'revoked_tokens' => $revokedCount,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        if ($effectiveCauser instanceof User && $this->roleHierarchy->isSuperUser($effectiveCauser)) {
            $properties['causer_is_super_user'] = true;

            if (
                $user->tenant_id !== null
                && $effectiveCauser->tenant_id !== null
                && (string) $effectiveCauser->tenant_id !== (string) $user->tenant_id
            ) {
                $properties['scope'] = 'platform_cross_tenant';
                $properties['causer_tenant_id'] = (string) $effectiveCauser->tenant_id;
            }
        }

        activity('auth')
            ->causedBy($effectiveCauser)
            ->performedOn($user)
            ->event('tokens_revoked')
            ->withProperties($properties)
            ->log('User access tokens revoked');

        return $revokedCount;
    }

    protected function logFailedLogin(string $email, string $reason, ?User $user = null): void
    {
        $logger = activity('auth')
            ->event('login_failed')
            ->withProperties([
                'tenant_id' => $user?->tenant_id,
                'email' => $email,
                'reason' => $reason,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

        if ($user) {
            $logger->causedBy($user);
        }

        $logger->log('Failed login attempt');
    }
}