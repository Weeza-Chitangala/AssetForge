<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\LogsTenantActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AssetModel extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToTenant, LogsActivity, LogsTenantActivity;

    protected $fillable = [
        'tenant_id',
        'manufacturer_id',
        'category_id',
        'name',
        'model_number',
        'description',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('asset_model')
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}