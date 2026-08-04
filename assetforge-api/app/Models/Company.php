<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasUuid, SoftDeletes;


    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'status',
    ];


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