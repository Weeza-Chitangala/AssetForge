<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'assetforge-demo')->first() ?? Tenant::first();

        $admin = User::firstOrCreate(
            ['email' => 'admin@assetforge.com'],
            [
                'name' => 'System Administrator',
                'password' => bcrypt('Admin@12345'),
                'tenant_id' => $tenant?->id,
                'status' => 'active',
            ]
        );

        if (!$admin->hasRole('Super Administrator')) {
            $admin->assignRole('Super Administrator');
        }
    }
}
