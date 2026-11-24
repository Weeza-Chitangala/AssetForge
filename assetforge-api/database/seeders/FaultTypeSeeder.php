<?php

namespace Database\Seeders;

use App\Models\FaultType;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class FaultTypeSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'assetforge-demo')->first();
        if (!$tenant) {
            return;
        }

        $faults = [
            ['name' => 'Physical / Screen Damage', 'code' => 'SCR-DMG', 'severity' => 'high', 'description' => 'Cracked LCD, shattered screen, or broken casing'],
            ['name' => 'Battery / Power Failure', 'code' => 'PWR-FLT', 'severity' => 'medium', 'description' => 'Battery not charging, swollen battery, or dead charger port'],
            ['name' => 'Operating System Crash', 'code' => 'OS-ERR', 'severity' => 'medium', 'description' => 'Bootloop, blue screen of death (BSOD), or corruption'],
            ['name' => 'Hardware Component Failure', 'code' => 'HW-FLT', 'severity' => 'high', 'description' => 'Faulty RAM, dead SSD/HDD, or damaged motherboard'],
            ['name' => 'Overheating / Fan Failure', 'code' => 'THM-FLT', 'severity' => 'medium', 'description' => 'Thermal throttling, dust blockage, or dead cooling fan'],
            ['name' => 'Liquid Ingress', 'code' => 'LIQ-DMG', 'severity' => 'critical', 'description' => 'Coffee/water spill causing short circuits'],
        ];

        foreach ($faults as $fault) {
            FaultType::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $fault['name']],
                array_merge($fault, ['status' => 'active'])
            );
        }
    }
}