<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            AssetReferenceDataSeeder::class,
            FaultTypeSeeder::class,
        ]);
    }
}
