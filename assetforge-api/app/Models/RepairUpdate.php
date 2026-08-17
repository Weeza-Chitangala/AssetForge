<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairUpdate extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $table = 'repair_updates';

    protected $fillable = [
        'tenant_id',
        'repair_job_id',
        'status_from',
        'status_to',
        'comments',
        'user_id',
    ];

    public function repairJob(): BelongsTo
    {
        return $this->belongsTo(RepairJob::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}