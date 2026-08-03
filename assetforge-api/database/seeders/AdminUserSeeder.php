<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            [
                'email' => 'admin@assetforge.com',
            ],
            [
                'name' => 'System Administrator',
                'password' => bcrypt('Admin@12345'),
            ]
        );

        $admin->assignRole('Super Administrator');
    }
}