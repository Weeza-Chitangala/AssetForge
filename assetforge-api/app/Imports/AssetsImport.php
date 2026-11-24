<?php

namespace App\Imports;

use App\Models\AssetModel;
use App\Models\AssetStatus;
use App\Models\Category;
use App\Models\EquipmentAsset;
use App\Models\Location;
use App\Models\Manufacturer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AssetsImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected string $tenantId;
    protected int $userId;

    public function __construct(string $tenantId, int $userId)
    {
        $this->tenantId = $tenantId;
        $this->userId   = $userId;
    }

    public function collection(Collection $rows): void
    {
        $defaultStatus = AssetStatus::where('code', 'AVAILABLE')->first();

        foreach ($rows as $row) {
            $categoryName     = trim($row['category']);
            $manufacturerName = trim($row['manufacturer']);
            $modelName        = trim($row['model']);

            // 1. Resolve or create Category
            $category = Category::firstOrCreate(
                [
                    'tenant_id' => $this->tenantId,
                    'name'      => $categoryName,
                ],
                [
                    'code'   => strtoupper(substr($categoryName, 0, 3)),
                    'status' => 'active',
                ]
            );

            // 2. Resolve or create Manufacturer
            $manufacturer = Manufacturer::firstOrCreate(
                [
                    'tenant_id' => $this->tenantId,
                    'name'      => $manufacturerName,
                ],
                [
                    'code'   => strtoupper(substr($manufacturerName, 0, 3)),
                    'status' => 'active',
                ]
            );

            // 3. Resolve or create Model
            $model = AssetModel::firstOrCreate(
                [
                    'tenant_id'       => $this->tenantId,
                    'name'            => $modelName,
                    'manufacturer_id' => $manufacturer->id,
                ],
                [
                    'category_id'  => $category->id,
                    'model_number' => strtoupper(substr($modelName, 0, 8)),
                    'status'       => 'active',
                ]
            );

            // 4. Resolve Location if provided
            $locationId = null;
            if (!empty($row['location'])) {
                $loc = Location::where('tenant_id', $this->tenantId)
                    ->where('name', 'ILIKE', trim($row['location']))
                    ->first();
                $locationId = $loc?->id;
            }

            // 5. Generate Asset Tag if empty
            $assetTag = !empty($row['asset_tag']) ? trim($row['asset_tag']) : null;
            if (!$assetTag) {
                $count    = EquipmentAsset::where('tenant_id', $this->tenantId)->count() + 1;
                $assetTag = sprintf('AF-%s-%06d', now()->year, $count);
            }

            // 6. Persist Asset with correct foreign key asset_model_id
            EquipmentAsset::updateOrCreate(
                [
                    'tenant_id'     => $this->tenantId,
                    'serial_number' => trim($row['serial_number']),
                ],
                [
                    'asset_tag'            => $assetTag,
                    'name'                 => $row['name'] ?? $modelName,
                    'category_id'          => $category->id,
                    'manufacturer_id'      => $manufacturer->id,
                    'asset_model_id'       => $model->id,
                    'location_id'          => $locationId,
                    'status_id'            => $defaultStatus?->id,
                    'purchase_cost'        => !empty($row['purchase_cost']) ? (float) $row['purchase_cost'] : 0.00,
                    'purchase_date'        => !empty($row['purchase_date']) ? $row['purchase_date'] : now()->toDateString(),
                    'warranty_expiry_date' => $row['warranty_expiry_date'] ?? null,
                    'processor'            => $row['processor'] ?? null,
                    'ram'                  => $row['ram'] ?? null,
                    'storage'              => $row['storage'] ?? null,
                    'operating_system'     => $row['operating_system'] ?? null,
                    'condition'            => $row['condition'] ?? 'good',
                    'notes'                => $row['notes'] ?? 'Imported via CSV/Excel onboarding',
                    'created_by'           => $this->userId,
                ]
            );
        }
    }

    public function rules(): array
    {
        return [
            '*.serial_number' => ['required'],
            '*.category'      => ['required'],
            '*.manufacturer'  => ['required'],
            '*.model'         => ['required'],
        ];
    }
}