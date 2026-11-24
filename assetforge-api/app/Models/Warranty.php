<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warranty extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToTenant;

    protected $table = 'warranties';

    protected $fillable = [
        'tenant_id',
        'equipment_asset_id',
        'provider_name',
        'policy_number',
        'warranty_type',
        'status',
        'start_date',
        'end_date',
        'service_level',
        'support_phone',
        'support_email',
        'support_url',
        'terms_and_conditions',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(EquipmentAsset::class, 'equipment_asset_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Compute real-time status and days remaining.
     */
    public function getComputedStatusAttribute(): string
    {
        $today = Carbon::today();
        $endDate = Carbon::parse($this->end_date);

        if ($today->gt($endDate)) {
            return 'expired';
        }

        if ($today->diffInDays($endDate) <= 30) {
            return 'expiring_soon';
        }

        return 'active';
    }

    public function getDaysRemainingAttribute(): int
    {
        $today = Carbon::today();
        $endDate = Carbon::parse($this->end_date);

        return $today->gt($endDate) ? 0 : (int) $today->diffInDays($endDate);
    }
}