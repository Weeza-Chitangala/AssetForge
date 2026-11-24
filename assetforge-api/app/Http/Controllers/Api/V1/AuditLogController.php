<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        $query = Activity::with(['causer', 'subject'])
            ->where(function ($q) use ($tenantId) {
                $q->whereHasMorph('causer', [User::class], fn ($uq) => $uq->where('tenant_id', $tenantId))
                    ->orWhere('properties->tenant_id', $tenantId);
            })
            ->latest()
            ->when($request->query('log_name'), fn ($q, $logName) => $q->where('log_name', $logName))
            ->when($request->query('event'), fn ($q, $event) => $q->where('event', $event))
            ->when($request->query('subject_type'), function ($q, $type) {
                $target = str_contains($type, '\\') ? $type : 'App\\Models\\' . ucfirst($type);

                return $q->where('subject_type', $target);
            })
            ->when($request->query('causer_id'), fn ($q, $userId) => $q->where('causer_id', $userId))
            ->when($request->query('date_from'), fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($request->query('date_to'), fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->when($request->query('search'), function ($q, $search) use ($likeOp) {
                $q->where(function ($sq) use ($search, $likeOp) {
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
        $tenantId = $request->user()?->tenant_id;
        $modelClass = str_contains($type, '\\') ? $type : 'App\\Models\\' . ucfirst($type);

        $logs = Activity::with(['causer'])
            ->where(function ($q) use ($tenantId) {
                $q->whereHasMorph('causer', [User::class], fn ($uq) => $uq->where('tenant_id', $tenantId))
                    ->orWhere('properties->tenant_id', $tenantId);
            })
            ->where(function ($q) use ($modelClass, $type) {
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
}
