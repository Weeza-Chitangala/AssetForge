<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\EquipmentAsset;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Organization;
use App\Models\RepairJob;
use App\Scopes\TenantScope;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warranty;
use App\Services\RoleHierarchyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    /**
     * Explicit allowlist mapping URL/filter aliases to auditable Eloquent model classes.
     *
     * @var array<string, class-string>
     */
    private const SUBJECT_MODEL_MAP = [
        'equipmentasset' => EquipmentAsset::class,
        'equipment_asset' => EquipmentAsset::class,
        'asset' => EquipmentAsset::class,
        'repairjob' => RepairJob::class,
        'repair_job' => RepairJob::class,
        'repair' => RepairJob::class,
        'warranty' => Warranty::class,
        'supplier' => Supplier::class,
        'category' => Category::class,
        'manufacturer' => Manufacturer::class,
        'assetmodel' => AssetModel::class,
        'asset_model' => AssetModel::class,
        'location' => Location::class,
        'department' => Department::class,
        'company' => Company::class,
        'organization' => Organization::class,
        'user' => User::class,
    ];

    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();
        $tenantId = $actor?->tenant_id;
        abort_if(! $actor || ! $tenantId, 403, 'An active tenant context is required for this operation.');

        $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        $query = Activity::with(['causer', 'subject']);
        $this->applyTenantAuditScope($query, $actor);

        $query->latest()
            ->when($request->query('log_name'), fn (Builder $q, string $logName) => $q->where('log_name', $logName))
            ->when($request->query('event'), fn (Builder $q, string $event) => $q->where('event', $event))
            ->when($request->query('subject_type'), function (Builder $q, string $type) {
                $target = $this->resolveSubjectModel($type);
                if ($target === null) {
                    return $q->whereRaw('1 = 0');
                }

                return $q->where('subject_type', $target);
            })
            ->when($request->query('causer_id'), fn (Builder $q, string $userId) => $q->where('causer_id', $userId))
            ->when($request->query('date_from'), fn (Builder $q, string $from) => $q->whereDate('created_at', '>=', $from))
            ->when($request->query('date_to'), fn (Builder $q, string $to) => $q->whereDate('created_at', '<=', $to))
            ->when($request->query('search'), function (Builder $q, string $search) use ($likeOp) {
                $q->where(function (Builder $sq) use ($search, $likeOp) {
                    $sq->where('description', $likeOp, "%{$search}%")
                        ->orWhere('event', $likeOp, "%{$search}%")
                        ->orWhere('log_name', $likeOp, "%{$search}%")
                        ->orWhere('subject_type', $likeOp, "%{$search}%");
                });
            });

        $logs = $query->paginate($request->query('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Activity audit logs retrieved successfully.',
            'data' => AuditLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function entityAudit(Request $request, string $type, string $id): JsonResponse
    {
        $actor = $request->user();
        $tenantId = $actor?->tenant_id;
        abort_if(! $actor || ! $tenantId, 403, 'An active tenant context is required for this operation.');

        $modelClass = $this->resolveSubjectModel($type);
        abort_if($modelClass === null, 404, 'Entity not found.');

        abort_if(! $this->entityBelongsToTenant($modelClass, $id, (string) $tenantId), 404, 'Entity not found.');

        $query = Activity::with(['causer']);
        $this->applyTenantAuditScope($query, $actor);

        $logs = $query
            ->where(function (Builder $q) use ($modelClass, $type) {
                $q->where('subject_type', $modelClass)
                    ->orWhere('subject_type', strtolower($type));
            })
            ->where('subject_id', (string) $id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => "Audit history for {$type} [{$id}] retrieved.",
            'data' => AuditLogResource::collection($logs),
        ]);
    }

    /**
     * Scope activity queries strictly to the actor's tenant and hide cross-tenant platform actions
     * from non-Super-User roles.
     */
    private function applyTenantAuditScope(Builder $query, User $actor): void
    {
        $tenantId = (string) $actor->tenant_id;
        $isSuperUser = $actor->hasRole(RoleHierarchyService::ROLE_SUPER_USER);

        $query->where(function (Builder $q) use ($tenantId, $isSuperUser) {
            $q->where('properties->tenant_id', $tenantId)
                ->orWhere(function (Builder $legacyQuery) use ($tenantId) {
                    $legacyQuery->whereNull('properties->tenant_id')
                        ->whereHasMorph('causer', [User::class], fn (Builder $uq) => $uq->where('tenant_id', $tenantId));
                });

            if ($isSuperUser) {
                $q->orWhere('properties->causer_tenant_id', $tenantId);
            }
        });

        if (! $isSuperUser) {
            $query->where(function (Builder $q) {
                $q->whereNull('properties->scope')
                    ->orWhere('properties->scope', '!=', 'platform_cross_tenant');
            });
        }
    }

    /**
     * Resolve a route or query subject identifier against the explicit allowlist.
     *
     * @return class-string|null
     */
    private function resolveSubjectModel(string $type): ?string
    {
        $trimmed = trim($type);
        $lower = strtolower(ltrim($trimmed, '\\'));

        if (isset(self::SUBJECT_MODEL_MAP[$lower])) {
            return self::SUBJECT_MODEL_MAP[$lower];
        }

        foreach (array_unique(array_values(self::SUBJECT_MODEL_MAP)) as $allowedClass) {
            if (strtolower($allowedClass) === $lower) {
                return $allowedClass;
            }
        }

        return null;
    }

    /**
     * Verify that the target auditable record exists within the caller's tenant boundary.
     *
     * @param class-string $modelClass
     */
    private function entityBelongsToTenant(string $modelClass, string $id, string $tenantId): bool
    {
        if ($modelClass === User::class) {
            if (! ctype_digit($id)) {
                return false;
            }
        } elseif (! Str::isUuid($id)) {
            return false;
        }

        $query = $modelClass::query()->withoutGlobalScope(TenantScope::class);

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        if ($modelClass === Company::class) {
            return $query->whereKey($id)
                ->whereHas('organization', fn (Builder $oq) => $oq->withoutGlobalScopes()->withTrashed()->where('tenant_id', $tenantId))
                ->exists();
        }

        if ($modelClass === Department::class) {
            return $query->whereKey($id)
                ->whereHas('company', fn (Builder $cq) => $cq->withTrashed()->whereHas(
                    'organization',
                    fn (Builder $oq) => $oq->withoutGlobalScopes()->withTrashed()->where('tenant_id', $tenantId)
                ))
                ->exists();
        }

        return $query->whereKey($id)
            ->where('tenant_id', $tenantId)
            ->exists();
    }
}
