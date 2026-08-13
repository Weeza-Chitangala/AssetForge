<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasUuid, SoftDeletes;


    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'status',
    ];


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