<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentAsset extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'equipment_assets';

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'company_id',
        'department_id',
        'asset_tag',
        'serial_number',
        'name',
        'category_id',
        'manufacturer_id',
        'asset_model_id',
        'location_id',
        'status_id',
        'assigned_to_user_id',
        'assigned_date',
        'operating_system',
        'processor',
        'ram',
        'storage',
        'mac_address',
        'ip_address',
        'supplier',
        'order_number',
        'purchase_date',
        'purchase_cost',
        'warranty_expiry_date',
        'condition',
        'notes',
        'image_path',
        'qr_code_path',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'warranty_expiry_date' => 'date',
            'assigned_date' => 'datetime',
            'purchase_cost' => 'decimal:2',
        ];
    }

    // Organizational Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Reference Data Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function assetModel(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(AssetStatus::class, 'status_id');
    }

    // User Assignment & Audit Relationships
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    
    // Asset Lifecycle & Audit Trail Relationships
    public function movements(): HasMany
    {
        return $this->hasMany(AssetMovement::class)->latest('movement_date');
    }

    // Warranty Relationships
    public function warranties(): HasMany
    {
        return $this->hasMany(Warranty::class)->latest('end_date');
    }

    public function activeWarranty(): HasOne
    {
        return $this->hasOne(Warranty::class)->latestOfMany('end_date');
    }

    // Repair Job Relationships
    public function repairJobs(): HasMany
    {
        return $this->hasMany(RepairJob::class)->latest();
    }
}