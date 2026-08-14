<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Multi-tenancy
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            
            // Target Asset
            $table->foreignUuid('equipment_asset_id')->constrained('equipment_assets')->cascadeOnDelete();
            
            // Action Type: 'checkout', 'checkin', 'transfer', 'status_change', 'repair'
            $table->string('action_type', 50);

            // Previous State
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignUuid('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignUuid('from_status_id')->nullable()->constrained('asset_statuses')->nullOnDelete();

            // New / Current State
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignUuid('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignUuid('to_status_id')->nullable()->constrained('asset_statuses')->nullOnDelete();

            // Notes & Metadata
            $table->dateTime('movement_date')->useCurrent();
            $table->string('condition')->nullable(); // new, good, fair, poor, damaged
            $table->text('notes')->nullable();

            // Audit
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'equipment_asset_id']);
            $table->index(['tenant_id', 'action_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_movements');
    }
};