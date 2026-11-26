<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\LogsTenantActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Manufacturer extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToTenant, LogsActivity, LogsTenantActivity;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'website',
        'support_phone',
        'support_email',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('manufacturer')
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function assetModels(): HasMany
    {
        return $this->hasMany(AssetModel::class);
    }
}