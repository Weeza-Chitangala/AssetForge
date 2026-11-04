<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Activity::with(['causer', 'subject'])
            ->latest()
            ->when($request->query('event'), fn($q, $event) => $q->where('event', $event))
            ->when($request->query('subject_type'), function ($q, $type) {
                $target = str_contains($type, '\\') ? $type : "App\\Models\\" . ucfirst($type);
                return $q->where('subject_type', $target);
            })
            ->when($request->query('causer_id'), fn($q, $userId) => $q->where('causer_id', $userId));

        $logs = $query->paginate($request->query('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Activity audit logs retrieved successfully.',
            'data'    => AuditLogResource::collection($logs),
            'meta'    => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }

    public function entityAudit(string $type, string $id): JsonResponse
    {
        $modelClass = str_contains($type, '\\') ? $type : "App\\Models\\" . ucfirst($type);

        $logs = Activity::with(['causer'])
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
            'data'    => AuditLogResource::collection($logs),
        ]);
    }
}