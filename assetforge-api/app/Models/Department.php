<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
