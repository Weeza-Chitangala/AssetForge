<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('repair_job_id')->constrained('repair_jobs')->cascadeOnDelete();
            
            $table->string('status_from')->nullable();
            $table->string('status_to');
            $table->text('comments');
            
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'repair_job_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_updates');
    }
};