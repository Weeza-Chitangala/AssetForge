<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Multi-tenant & Organization scope
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->nullOnDelete();

            // Core Identifiers
            $table->string('asset_tag')->unique();     // e.g. AF-2026-000101
            $table->string('serial_number')->unique();  // e.g. 1H850453N7
            $table->string('name')->nullable();         // Friendly display name / hostname

            // Reference Foreign Keys
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignUuid('manufacturer_id')->nullable()->constrained('manufacturers')->nullOnDelete();
            $table->foreignUuid('asset_model_id')->nullable()->constrained('asset_models')->nullOnDelete();
            $table->foreignUuid('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignUuid('status_id')->nullable()->constrained('asset_statuses')->nullOnDelete();

            // Assignment & Ownership
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_date')->nullable();

            // Hardware Specifications (for ICT Devices)
            $table->string('operating_system')->nullable(); // Windows 11 Pro, Ubuntu 24.04
            $table->string('processor')->nullable();        // Intel Core i7-1185G7
            $table->string('ram')->nullable();              // 16GB DDR4
            $table->string('storage')->nullable();          // 512GB NVMe SSD
            $table->string('mac_address')->nullable();
            $table->string('ip_address')->nullable();

            // Financial & Procurement Details
            $table->string('supplier')->nullable();
            $table->string('order_number')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->date('warranty_expiry_date')->nullable();

            // Physical Condition & Notes
            $table->string('condition')->default('good'); // new, good, fair, poor, damaged
            $table->text('notes')->nullable();
            $table->string('image_path')->nullable();
            $table->string('qr_code_path')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Performance Indexes
            $table->index(['tenant_id', 'status_id']);
            $table->index(['tenant_id', 'assigned_to_user_id']);
            $table->index(['tenant_id', 'category_id']);
            $table->index(['tenant_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_assets');
    }
};