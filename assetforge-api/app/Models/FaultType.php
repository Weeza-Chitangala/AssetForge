<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FaultType extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'fault_types';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'severity',
        'description',
        'status',
    ];

    public function repairJobs(): HasMany
    {
        return $this->hasMany(RepairJob::class);
    }
}