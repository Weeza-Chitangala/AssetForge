<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairJob extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'repair_jobs';

    protected $fillable = [
        'tenant_id',
        'equipment_asset_id',
        'job_number',
        'fault_type_id',
        'fault_description',
        'technician_id',
        'repair_center',
        'vendor_name',
        'repair_status',
        'cost',
        'start_date',
        'completion_date',
        'diagnostic_notes',
        'resolution_summary',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'start_date' => 'date',
            'completion_date' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(EquipmentAsset::class, 'equipment_asset_id');
    }

    public function faultType(): BelongsTo
    {
        return $this->belongsTo(FaultType::class, 'fault_type_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(RepairUpdate::class)->latest();
    }
}