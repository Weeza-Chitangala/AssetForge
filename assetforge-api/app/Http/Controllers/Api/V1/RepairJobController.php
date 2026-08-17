<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Repair\AddRepairProgressRequest;
use App\Http\Requests\Repair\StoreRepairJobRequest;
use App\Http\Requests\Repair\UpdateRepairJobRequest;
use App\Http\Resources\RepairJobResource;
use App\Models\AssetMovement;
use App\Models\AssetStatus;
use App\Models\EquipmentAsset;
use App\Models\RepairJob;
use App\Models\RepairUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepairJobController extends Controller
{
    protected array $relations = ['asset', 'faultType', 'technician', 'updates.author'];

    public function index(Request $request): JsonResponse
    {
        $query = RepairJob::with($this->relations);

        if ($request->filled('repair_status')) {
            $query->where('repair_status', $request->repair_status);
        }
        if ($request->filled('equipment_asset_id')) {
            $query->where('equipment_asset_id', $request->equipment_asset_id);
        }
        if ($request->filled('technician_id')) {
            $query->where('technician_id', $request->technician_id);
        }

        $jobs = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Repair tickets retrieved.',
            'data' => RepairJobResource::collection($jobs)->response()->getData(true),
        ]);
    }

    public function store(StoreRepairJobRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $repairJob = DB::transaction(function () use ($data, $user) {
            // 1. Generate sequential job number
            $count = RepairJob::where('tenant_id', $user->tenant_id)->count() + 1;
            $jobNumber = 'REP-' . date('Y') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            $data['tenant_id'] = $user->tenant_id;
            $data['job_number'] = $jobNumber;
            $data['created_by'] = $user->id;
            $data['start_date'] = $data['start_date'] ?? now();

            $job = RepairJob::create($data);

            // 2. Initial progress entry
            RepairUpdate::create([
                'tenant_id' => $user->tenant_id,
                'repair_job_id' => $job->id,
                'status_from' => null,
                'status_to' => 'pending',
                'comments' => 'Repair ticket logged: ' . $job->fault_description,
                'user_id' => $user->id,
            ]);

            // 3. Auto transition asset status to IN_REPAIR
            $inRepairStatus = AssetStatus::where('code', 'IN_REPAIR')->first();
            $asset = EquipmentAsset::find($job->equipment_asset_id);
            if ($asset && $inRepairStatus) {
                // Record movement audit
                AssetMovement::create([
                    'tenant_id' => $user->tenant_id,
                    'equipment_asset_id' => $asset->id,
                    'action_type' => 'repair_opened',
                    'from_status_id' => $asset->status_id,
                    'to_status_id' => $inRepairStatus->id,
                    'notes' => 'Sent for repair: ' . $job->job_number,
                    'performed_by' => $user->id,
                ]);

                $asset->update([
                    'status_id' => $inRepairStatus->id,
                    'updated_by' => $user->id,
                ]);
            }

            return $job;
        });

        $repairJob->load($this->relations);

        return response()->json([
            'success' => true,
            'message' => 'Repair ticket created successfully.',
            'data' => new RepairJobResource($repairJob),
        ], 201);
    }

    public function show(RepairJob $repairJob): JsonResponse
    {
        $repairJob->load($this->relations);

        return response()->json([
            'success' => true,
            'message' => 'Repair job retrieved.',
            'data' => new RepairJobResource($repairJob),
        ]);
    }

    public function update(UpdateRepairJobRequest $request, RepairJob $repairJob): JsonResponse
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $repairJob->update($data);
        $repairJob->load($this->relations);

        return response()->json([
            'success' => true,
            'message' => 'Repair ticket updated.',
            'data' => new RepairJobResource($repairJob),
        ]);
    }

    /**
     * Technician adds progress notes and transitions repair state.
     */
    public function addProgress(AddRepairProgressRequest $request, RepairJob $repairJob): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        DB::transaction(function () use ($repairJob, $data, $user) {
            $previousStatus = $repairJob->repair_status;

            // 1. Create update timeline entry
            RepairUpdate::create([
                'tenant_id' => $user->tenant_id,
                'repair_job_id' => $repairJob->id,
                'status_from' => $previousStatus,
                'status_to' => $data['status'],
                'comments' => $data['comments'],
                'user_id' => $user->id,
            ]);

            // 2. Update repair job state
            $updatePayload = [
                'repair_status' => $data['status'],
                'updated_by' => $user->id,
            ];

            if (isset($data['cost'])) {
                $updatePayload['cost'] = $data['cost'];
            }
            if (isset($data['resolution_summary'])) {
                $updatePayload['resolution_summary'] = $data['resolution_summary'];
            }
            if ($data['status'] === 'completed') {
                $updatePayload['completion_date'] = now();
            }

            $repairJob->update($updatePayload);

            // 3. If repair is completed, automatically restore asset to AVAILABLE in inventory
            if ($data['status'] === 'completed') {
                $availableStatus = AssetStatus::where('code', 'AVAILABLE')->first();
                $asset = $repairJob->asset;

                if ($asset && $availableStatus) {
                    AssetMovement::create([
                        'tenant_id' => $user->tenant_id,
                        'equipment_asset_id' => $asset->id,
                        'action_type' => 'repair_completed',
                        'from_status_id' => $asset->status_id,
                        'to_status_id' => $availableStatus->id,
                        'notes' => 'Repair completed (' . $repairJob->job_number . '). Returned to stock.',
                        'performed_by' => $user->id,
                    ]);

                    $asset->update([
                        'status_id' => $availableStatus->id,
                        'assigned_to_user_id' => null, // Available for re-assignment
                        'updated_by' => $user->id,
                    ]);
                }
            }
        });

        $repairJob->load($this->relations);

        return response()->json([
            'success' => true,
            'message' => 'Repair progress recorded successfully.',
            'data' => new RepairJobResource($repairJob),
        ]);
    }
}