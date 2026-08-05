<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->uuid('tenant_id')->after('id');
            $table->uuid('organization_id')->nullable()->after('tenant_id');
            $table->uuid('company_id')->nullable()->after('organization_id');
            $table->uuid('department_id')->nullable()->after('company_id');
            $table->uuid('team_id')->nullable()->after('department_id');

            $table->string('employee_number')->nullable()->after('email');
            $table->string('job_title')->nullable()->after('employee_number');

            $table->string('status')->default('active')->after('job_title');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->nullOnDelete();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();

            $table->foreign('team_id')
                ->references('id')
                ->on('teams')
                ->nullOnDelete();

            $table->index('tenant_id');
            $table->index('organization_id');
            $table->index('company_id');
            $table->index('department_id');
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['team_id']);

            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['organization_id']);
            $table->dropIndex(['company_id']);
            $table->dropIndex(['department_id']);
            $table->dropIndex(['team_id']);

            $table->dropColumn([
                'tenant_id',
                'organization_id',
                'company_id',
                'department_id',
                'team_id',
                'employee_number',
                'job_title',
                'status',
            ]);
        });
    }
};