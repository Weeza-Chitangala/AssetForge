<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Team;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::create([
            'name'   => 'AssetForge Demo Tenant',
            'slug'   => 'assetforge-demo',
            'email'  => 'demo@assetforge.local',
            'phone'  => '+260000000000',
            'website'=> 'https://assetforge.local',
            'status' => 'active',
        ]);

        $organization = Organization::create([
            'tenant_id' => $tenant->id,
            'name' => 'Motus Africa',
            'code' => 'MAF',
            'status' => 'active',
        ]);

        $company = Company::create([
            'organization_id' => $organization->id,
            'name' => 'Motus Zambia',
            'code' => 'MZA',
            'status' => 'active',
        ]);

        $departments = [
            'IT',
            'Finance',
            'Human Resources',
        ];

        foreach ($departments as $departmentName) {

            $department = Department::create([
                'company_id' => $company->id,
                'name' => $departmentName,
                'code' => strtoupper(substr($departmentName, 0, 3)),
                'status' => 'active',
            ]);

            if ($departmentName === 'IT') {

                foreach ([
                    'Infrastructure',
                    'Development',
                    'Helpdesk',
                ] as $teamName) {

                    Team::create([
                        'department_id' => $department->id,
                        'name' => $teamName,
                        'code' => strtoupper(substr($teamName, 0, 3)),
                        'status' => 'active',
                    ]);
                }
            }
        }
    }
}