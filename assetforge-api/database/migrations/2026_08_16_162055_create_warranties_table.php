<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranties', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Multi-tenancy
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            
            // Relational target asset
            $table->foreignUuid('equipment_asset_id')->constrained('equipment_assets')->cascadeOnDelete();

            // Warranty details
            $table->string('provider_name');             // e.g. HP Care Pack, Dell ProSupport
            $table->string('policy_number')->nullable(); // Contract / Pack ID
            $table->string('warranty_type')->default('standard'); // standard, extended, accidental_damage, onsite
            $table->string('status')->default('active'); // active, expiring_soon, expired, void
            
            // Dates
            $table->date('start_date');
            $table->date('end_date');

            // Support SLA & Contact
            $table->string('service_level')->nullable(); // e.g. 24/7 4hr Response, Next Business Day Onsite
            $table->string('support_phone')->nullable();
            $table->string('support_email')->nullable();
            $table->string('support_url')->nullable();
            $table->text('terms_and_conditions')->nullable();

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'equipment_asset_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranties');
    }
};