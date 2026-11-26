<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\RoleHierarchyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(
        private readonly RoleHierarchyService $roleHierarchy,
    ) {
    }
    /**
     * Display a listing of users within the tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        abort_if(! $tenantId, 403, 'An active tenant context is required for this operation.');

        $query = User::with(['tenant', 'organization', 'company', 'department', 'team', 'roles'])
            ->where('tenant_id', $tenantId);
        $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        // Search filter (name, email, employee_number)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search, $likeOp) {
                $q->where('name', $likeOp, "%{$search}%")
                  ->orWhere('email', $likeOp, "%{$search}%")
                  ->orWhere('employee_number', $likeOp, "%{$search}%");
            });
        }

        // Organizational filters
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('team_id')) {
            $query->where('team_id', $request->team_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'data' => UserResource::collection($users)->response()->getData(true),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        abort_if(! $tenantId, 403, 'An active tenant context is required for this operation.');

        $data = $request->validated();

        $user = $this->roleHierarchy->withPrivilegedAccountLock(function () use ($data, $tenantId) {
            if (! empty($data['roles'])) {
                Gate::authorize('assignUserRoles', [$data['roles']]);
            }

            $payload = $data;
            $payload['password'] = Hash::make($payload['password']);
            $payload['tenant_id'] = $tenantId;

            $created = User::create($payload);

            if (! empty($payload['roles'])) {
                $created->syncRoles($payload['roles']);
                $this->logRoleSync($created, [], $created->getRoleNames()->values()->all());
            }

            return $created;
        });

        $user->load(['tenant', 'organization', 'company', 'department', 'team', 'roles']);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        abort_if(! $tenantId, 403, 'An active tenant context is required for this operation.');
        abort_if($user->tenant_id !== $tenantId, 404, 'User not found.');
        $user->load(['tenant', 'organization', 'company', 'department', 'team', 'roles']);

        return response()->json([
            'success' => true,
            'message' => 'User details retrieved successfully.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        abort_if(! $tenantId, 403, 'An active tenant context is required for this operation.');
        abort_if($user->tenant_id !== $tenantId, 404, 'User not found.');

        $data = $request->validated();

        $updatedUser = $this->roleHierarchy->withPrivilegedAccountLock(function () use ($user, $data) {
            $lockedTarget = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('updateUserAccount', [$lockedTarget, $data]);

            $payload = $data;
            if (! empty($payload['password'])) {
                $payload['password'] = Hash::make($payload['password']);
            } else {
                unset($payload['password']);
            }

            // Prevent payload injection from altering tenant ownership via user updates.
            unset($payload['tenant_id']);

            $lockedTarget->update($payload);

            if (array_key_exists('roles', $payload)) {
                $previousRoles = $lockedTarget->roles()->pluck('name')->values()->all();
                $lockedTarget->syncRoles($payload['roles'] ?? []);
                $lockedTarget->unsetRelation('roles');
                $updatedRoles = $lockedTarget->getRoleNames()->values()->all();

                $prevSorted = $previousRoles;
                $updSorted = $updatedRoles;
                sort($prevSorted);
                sort($updSorted);

                if ($prevSorted !== $updSorted) {
                    $this->logRoleSync($lockedTarget, $previousRoles, $updatedRoles);
                }
            }

            return $lockedTarget;
        });

        $updatedUser->load(['tenant', 'organization', 'company', 'department', 'team', 'roles']);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => new UserResource($updatedUser),
        ]);
    }

    /**
     * Record role synchronization explicitly because Spatie role pivot changes do not fire model updates.
     *
     * @param  array<int, string>  $previousRoles
     * @param  array<int, string>  $newRoles
     */
    private function logRoleSync(User $target, array $previousRoles, array $newRoles): void
    {
        $causer = Auth::user();
        $properties = [
            'tenant_id' => $target->tenant_id !== null ? (string) $target->tenant_id : null,
            'previous_roles' => array_values($previousRoles),
            'new_roles' => array_values($newRoles),
        ];

        if ($causer instanceof User && $causer->hasRole(RoleHierarchyService::ROLE_SUPER_USER)) {
            $properties['causer_is_super_user'] = true;

            if (
                $target->tenant_id !== null
                && $causer->tenant_id !== null
                && (string) $causer->tenant_id !== (string) $target->tenant_id
            ) {
                $properties['scope'] = 'platform_cross_tenant';
                $properties['causer_tenant_id'] = (string) $causer->tenant_id;
            }
        }

        activity('user_roles')
            ->performedOn($target)
            ->causedBy($causer)
            ->event('roles_synced')
            ->withProperties($properties)
            ->log('User roles synchronized');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        abort_if(! $tenantId, 403, 'An active tenant context is required for this operation.');
        abort_if($user->tenant_id !== $tenantId, 404, 'User not found.');

        $this->roleHierarchy->withPrivilegedAccountLock(function () use ($user) {
            $lockedTarget = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            Gate::authorize('deleteUserAccount', [$lockedTarget]);
            $lockedTarget->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }
}