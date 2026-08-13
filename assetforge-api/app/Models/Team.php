<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasUuid, SoftDeletes;


    protected $fillable = [
        'department_id',
        'name',
        'code',
        'status',
    ];


    protected $casts = [
        'status' => 'string',
    ];


    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}