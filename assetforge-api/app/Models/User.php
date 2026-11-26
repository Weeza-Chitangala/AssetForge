<?php

namespace App\Models;

use App\Traits\LogsTenantActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasRoles;
    use LogsActivity;
    use LogsTenantActivity;

    // Set Spatie default guard for this model to API
    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'organization_id',
        'company_id',
        'department_id',
        'team_id',
        'employee_number',
        'job_title',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Restrict automatic user audit logging to non-secret identity and assignment attributes.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user')
            ->logOnly([
                'name',
                'email',
                'tenant_id',
                'organization_id',
                'company_id',
                'department_id',
                'team_id',
                'employee_number',
                'job_title',
                'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships 

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

}