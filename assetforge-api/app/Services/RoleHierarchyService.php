<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * RoleHierarchyService
 *
 * Central policy authority defining AssetForge's canonical RBAC roles,
 * legacy role rename mappings, and privilege hierarchy boundaries across
 * Super User (platform), Administrator (tenant), and operational roles.
 */
class RoleHierarchyService
{
    public const GUARD = 'api';

    /**
     * Dedicated PostgreSQL transaction-scoped advisory lock key serializing privileged
     * Super User membership/status changes to prevent AB-BA row-lock deadlocks and phantom inserts.
     */
    private const SUPER_USER_ADVISORY_LOCK_KEY = 41009911001;

    public const ROLE_SUPER_USER = 'Super User';
    public const ROLE_ADMINISTRATOR = 'Administrator';
    public const ROLE_ASSET_MANAGER = 'Asset Manager';
    public const ROLE_TECHNICIAN = 'Technician';
    public const ROLE_AUDITOR = 'Auditor';

    /**
     * In-place rename mapping from legacy Phase 0 role names to Phase 1B canonical roles.
     *
     * @var array<string, string>
     */
    public const LEGACY_ROLE_MAP = [
        'Super Administrator' => self::ROLE_SUPER_USER,
        'IT Administrator' => self::ROLE_ADMINISTRATOR,
    ];

    /**
     * Non-administrative operational roles that tenant Administrators may assign.
     *
     * @var array<int, string>
     */
    public const OPERATIONAL_ROLES = [
        self::ROLE_ASSET_MANAGER,
        self::ROLE_TECHNICIAN,
        self::ROLE_AUDITOR,
    ];

    /**
     * Complete canonical role list supported on the API guard.
     *
     * @var array<int, string>
     */
    public const ALL_ROLES = [
        self::ROLE_SUPER_USER,
        self::ROLE_ADMINISTRATOR,
        self::ROLE_ASSET_MANAGER,
        self::ROLE_TECHNICIAN,
        self::ROLE_AUDITOR,
    ];

    public function isSuperUser(User $user): bool
    {
        return $user->hasRole(self::ROLE_SUPER_USER, self::GUARD);
    }

    public function isAdministrator(User $user): bool
    {
        return $user->hasRole(self::ROLE_ADMINISTRATOR, self::GUARD);
    }

    public function isPrivileged(User $user): bool
    {
        return $this->isSuperUser($user) || $this->isAdministrator($user);
    }

    /**
     * Roles the given actor is permitted to assign to users.
     *
     * @return array<int, string>
     */
    public function assignableRolesFor(User $actor): array
    {
        if ($this->isSuperUser($actor)) {
            return self::ALL_ROLES;
        }

        if ($this->isAdministrator($actor)) {
            return self::OPERATIONAL_ROLES;
        }

        return [];
    }

    /**
     * Determine whether the actor can assign every role in the provided list.
     *
     * @param  array<int, string>  $roles
     */
    public function canAssignRoles(User $actor, array $roles): bool
    {
        $allowed = $this->assignableRolesFor($actor);

        if ($allowed === []) {
            return false;
        }

        foreach ($roles as $role) {
            if (! is_string($role) || ! in_array($role, $allowed, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the actor may view-manage, update, or delete the target account.
     */
    public function canManageTarget(User $actor, User $target): bool
    {
        if (! $this->isSuperUser($actor) && $actor->tenant_id !== $target->tenant_id) {
            return false;
        }

        if ($this->isSuperUser($actor)) {
            return true;
        }

        if ($this->isAdministrator($actor)) {
            return ! $this->isSuperUser($target) && ! $this->isAdministrator($target);
        }

        return false;
    }

    /**
     * Execute a privileged user-management mutation inside a transaction guarded by a
     * PostgreSQL transaction-scoped advisory lock to prevent concurrent deadlocks or
     * race conditions on final active Super User safeguards.
     *
     * @template TReturn
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function withPrivilegedAccountLock(callable $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::select('SELECT pg_advisory_xact_lock(CAST(? AS bigint))', [self::SUPER_USER_ADVISORY_LOCK_KEY]);
            }

            return $callback();
        });
    }

    /**
     * Count active Super User accounts, optionally excluding a specific user ID.
     *
     * When executed inside a database transaction with $lockForUpdate = true, rows
     * are locked in deterministic ascending ID order so concurrent requests cannot
     * both pass the guard and remove the final active Super User.
     */
    public function activeSuperUserCount(?int $excludeUserId = null, bool $lockForUpdate = false): int
    {
        if ($lockForUpdate) {
            $lockedIds = User::query()
                ->where('status', 'active')
                ->role(self::ROLE_SUPER_USER, self::GUARD)
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');

            if ($excludeUserId !== null) {
                $lockedIds = $lockedIds->reject(fn ($id) => (int) $id === $excludeUserId);
            }

            return $lockedIds->count();
        }

        return User::query()
            ->where('status', 'active')
            ->when($excludeUserId !== null, fn ($q) => $q->where('id', '!=', $excludeUserId))
            ->role(self::ROLE_SUPER_USER, self::GUARD)
            ->count();
    }

    /**
     * Validate whether the actor is permitted to update the target account with the given payload.
     *
     * @param  array<string, mixed>  $payload
     * @return string|null  Null when allowed, otherwise an authorization failure message.
     */
    public function authorizeUserUpdate(User $actor, User $target, array $payload): ?string
    {
        if (! $this->canManageTarget($actor, $target)) {
            return 'You are not authorised to modify this user account.';
        }

        if (array_key_exists('roles', $payload) && ! $this->canAssignRoles($actor, $payload['roles'] ?? [])) {
            return 'You are not authorised to assign one or more of the requested roles.';
        }

        if ($this->isSuperUser($target)) {
            $removesSuperUser = array_key_exists('roles', $payload)
                && ! in_array(self::ROLE_SUPER_USER, $payload['roles'] ?? [], true);
            $deactivatesTarget = array_key_exists('status', $payload)
                && $payload['status'] !== 'active';

            if ($actor->id === $target->id && ($removesSuperUser || $deactivatesTarget)) {
                return 'You cannot remove your own Super User access or deactivate your own account.';
            }

            if (($removesSuperUser || $deactivatesTarget) && $this->activeSuperUserCount($target->id, lockForUpdate: true) < 1) {
                return 'Cannot demote or deactivate the last remaining active Super User account.';
            }
        }

        return null;
    }

    /**
     * Validate whether the actor is permitted to delete the target account.
     *
     * @return string|null  Null when allowed, otherwise an authorization failure message.
     */
    public function authorizeUserDeletion(User $actor, User $target): ?string
    {
        if ($actor->id === $target->id) {
            return 'You cannot delete your own account.';
        }

        if (! $this->canManageTarget($actor, $target)) {
            return 'You are not authorised to delete this user account.';
        }

        if ($this->isSuperUser($target) && $this->activeSuperUserCount($target->id, lockForUpdate: true) < 1) {
            return 'Cannot delete the last remaining active Super User account.';
        }

        return null;
    }
}
