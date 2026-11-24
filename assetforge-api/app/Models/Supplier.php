<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Supplier extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'contact_person',
        'email',
        'phone',
        'website',
        'address',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('supplier')
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
