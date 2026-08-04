<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasUuid, SoftDeletes;


    protected $fillable = [
        'company_id',
        'name',
        'code',
        'status',
    ];


    protected $casts = [
        'status' => 'string',
    ];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }


    public function teams()
    {
        return $this->hasMany(Team::class);
    }
}