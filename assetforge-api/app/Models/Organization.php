<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use App\Traits\LogsTenantActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Organization extends Model
{
    use HasUuid, SoftDeletes, BelongsToTenant, LogsActivity, LogsTenantActivity;


    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('organization')
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }


    protected $casts = [
        'status' => 'string',
    ];


    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }


    public function companies()
    {
        return $this->hasMany(Company::class);
    }
}