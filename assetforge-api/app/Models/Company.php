<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsTenantActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Company extends Model
{
    use HasUuid, SoftDeletes, LogsActivity, LogsTenantActivity;


    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('company')
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }


    protected $casts = [
        'status' => 'string',
    ];


    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }


    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}