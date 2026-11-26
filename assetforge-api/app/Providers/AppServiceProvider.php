<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Models\User;
use App\Observers\TenantObserver;
use App\Observers\UserObserver;
use App\Services\RoleHierarchyService;
use Illuminate\Auth\Access\Response;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Tenant::observe(TenantObserver::class);
        // Account lifecycle safeguards cannot be bypassed even by Super Users.
        $protectedAccountAbilities = [
            'assignUserRoles',
            'updateUserAccount',
            'deleteUserAccount',
        ];

        // Implicitly grant platform 'Super User' all standard permissions while keeping account safeguards active.
        Gate::before(function ($user, $ability) use ($protectedAccountAbilities) {
            if (in_array($ability, $protectedAccountAbilities, true)) {
                return null;
            }

            return $user->hasRole(RoleHierarchyService::ROLE_SUPER_USER, RoleHierarchyService::GUARD)
                ? true
                : null;
        });

        Gate::define('assignUserRoles', function (User $actor, array $roles): Response {
            return app(RoleHierarchyService::class)->canAssignRoles($actor, $roles)
                ? Response::allow()
                : Response::deny('You are not authorised to assign one or more of the requested roles.');
        });

        Gate::define('updateUserAccount', function (User $actor, User $target, array $payload = []): Response {
            $denial = app(RoleHierarchyService::class)->authorizeUserUpdate($actor, $target, $payload);

            return $denial === null ? Response::allow() : Response::deny($denial);
        });

        Gate::define('deleteUserAccount', function (User $actor, User $target): Response {
            $denial = app(RoleHierarchyService::class)->authorizeUserDeletion($actor, $target);

            return $denial === null ? Response::allow() : Response::deny($denial);
        });

        // DoS-safe pre-auth throttling scoped per IP; distributed credential stuffing across IPs
        // is mitigated in AuthService::login() while preserving access from known trusted IPs.
        RateLimiter::for('login', function (Request $request): array {
            $email = strtolower(trim((string) $request->input('email', '')));
            $ip = (string) ($request->ip() ?? 'unknown');

            return [
                Limit::perMinute(5)->by('login-email-ip:' . $email . '|' . $ip),
                Limit::perMinute(20)->by('login-ip:' . $ip),
            ];
        });
    }
}
