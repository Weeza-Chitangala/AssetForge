<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Multi-tenancy & Asset
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('equipment_asset_id')->constrained('equipment_assets')->cascadeOnDelete();
            
            // Ticket Identifier & Fault
            $table->string('job_number')->unique(); // e.g. REP-2026-0001
            $table->foreignUuid('fault_type_id')->nullable()->constrained('fault_types')->nullOnDelete();
            $table->text('fault_description');
            
            // Technician Assignment & Location
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('repair_center')->default('internal'); // internal, external_vendor, warranty_oem
            $table->string('vendor_name')->nullable();
            
            // Status & Cost
            $table->string('repair_status')->default('pending'); // pending, diagnosing, in_repair, parts_on_order, completed, unrepairable
            $table->decimal('cost', 12, 2)->default(0.00);
            
            // Dates
            $table->date('start_date')->nullable();
            $table->date('completion_date')->nullable();
            
            // Notes
            $table->text('diagnostic_notes')->nullable();
            $table->text('resolution_summary')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'equipment_asset_id']);
            $table->index(['tenant_id', 'repair_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_jobs');
    }
};