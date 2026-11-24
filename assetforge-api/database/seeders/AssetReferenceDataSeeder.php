<?php

namespace Database\Seeders;

use App\Models\AssetModel;
use App\Models\AssetStatus;
use App\Models\Category;
use App\Models\Company;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class AssetReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'assetforge-demo')->first();
        if (!$tenant) {
            return;
        }

        $company = Company::where('name', 'Motus Zambia')->first();

        // 1. Categories
        $laptops = Category::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Laptops'],
            ['code' => 'LAP', 'description' => 'Portable computers', 'status' => 'active']
        );
        $desktops = Category::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Desktops'],
            ['code' => 'DSK', 'description' => 'Desktop workstations', 'status' => 'active']
        );
        Category::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Monitors'],
            ['code' => 'MON', 'description' => 'Display screens', 'status' => 'active']
        );
        Category::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Printers'],
            ['code' => 'PRN', 'description' => 'Network and laser printers', 'status' => 'active']
        );

        // 2. Manufacturers
        $hp = Manufacturer::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'HP'],
            ['code' => 'HP', 'website' => 'https://www.hp.com', 'status' => 'active']
        );
        $dell = Manufacturer::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Dell'],
            ['code' => 'DELL', 'website' => 'https://www.dell.com', 'status' => 'active']
        );
        $lenovo = Manufacturer::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Lenovo'],
            ['code' => 'LEN', 'website' => 'https://www.lenovo.com', 'status' => 'active']
        );

        // 3. Asset Models
        AssetModel::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'EliteBook 840 G8'],
            [
                'manufacturer_id' => $hp->id,
                'category_id' => $laptops->id,
                'model_number' => '840-G8',
                'description' => '14-inch Business Laptop',
                'status' => 'active',
            ]
        );
        AssetModel::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Latitude 5420'],
            [
                'manufacturer_id' => $dell->id,
                'category_id' => $laptops->id,
                'model_number' => 'LAT-5420',
                'description' => '14-inch Corporate Laptop',
                'status' => 'active',
            ]
        );
        AssetModel::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'OptiPlex 7090'],
            [
                'manufacturer_id' => $dell->id,
                'category_id' => $desktops->id,
                'model_number' => 'OPT-7090',
                'description' => 'Micro Tower Workstation',
                'status' => 'active',
            ]
        );

        // 4. Locations
        Location::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Lusaka HQ - Main Building'],
            [
                'company_id' => $company?->id,
                'code' => 'LUS-HQ-MB',
                'building' => 'Main Office',
                'floor' => 'Floor 1',
                'room' => 'IT Operations',
                'address' => 'Plot 1010, Great East Road, Lusaka',
                'status' => 'active',
            ]
        );
        Location::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Kitwe Regional Branch'],
            [
                'company_id' => $company?->id,
                'code' => 'KTW-BR-01',
                'building' => 'Copperbelt Regional Center',
                'floor' => 'Ground Floor',
                'room' => 'Helpdesk Office',
                'address' => 'Independence Avenue, Kitwe',
                'status' => 'active',
            ]
        );

        // 5. Asset Statuses (Lifecycle Stages)
        AssetStatus::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Available / In Storage'],
            [
                'code' => 'AVAILABLE',
                'color' => '#16A34A', // Green
                'is_deployable' => true,
                'is_archived' => false,
                'description' => 'Ready for deployment to staff',
            ]
        );
        AssetStatus::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Assigned / In Use'],
            [
                'code' => 'ASSIGNED',
                'color' => '#2563EB', // Blue
                'is_deployable' => false,
                'is_archived' => false,
                'description' => 'Currently issued to an employee',
            ]
        );
        AssetStatus::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Under Repair / Maintenance'],
            [
                'code' => 'IN_REPAIR',
                'color' => '#D97706', // Amber
                'is_deployable' => false,
                'is_archived' => false,
                'description' => 'Undergoing diagnosis or active repair',
            ]
        );
        AssetStatus::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Retired / Decommissioned'],
            [
                'code' => 'RETIRED',
                'color' => '#DC2626', // Red
                'is_deployable' => false,
                'is_archived' => true,
                'description' => 'Disposed, recycled, or sold',
            ]
        );
    }
}