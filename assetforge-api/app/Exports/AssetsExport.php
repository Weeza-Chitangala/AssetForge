<?php

namespace App\Exports;

use App\Models\EquipmentAsset;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetsExport implements FromCollection, WithHeadings, WithMapping
{
    protected string $tenantId;

    public function __construct(string $tenantId)
    {
        $this->tenantId = $tenantId;
    }

    public function collection(): Enumerable
    {
        return EquipmentAsset::with(['category', 'manufacturer', 'assetModel', 'status', 'assignedTo', 'location'])
            ->where('tenant_id', $this->tenantId)
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'Asset Tag',
            'Serial Number',
            'Host / Device Name',
            'Category',
            'Manufacturer',
            'Model',
            'Status',
            'Assigned User',
            'Location',
            'Purchase Cost',
            'Purchase Date',
            'Warranty Expiry',
            'Processor',
            'RAM',
            'Storage',
            'Condition',
            'Created At',
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->asset_tag,
            $asset->serial_number,
            $asset->name,
            $asset->category?->name,
            $asset->manufacturer?->name,
            $asset->assetModel?->name ?? ($asset->model?->name ?? 'N/A'),
            $asset->status?->name,
            $asset->assignedTo?->name ?? 'Unassigned',
            $asset->location?->name ?? 'N/A',
            $asset->purchase_cost,
            $asset->purchase_date?->format('Y-m-d'),
            $asset->warranty_expiry_date?->format('Y-m-d'),
            $asset->processor,
            $asset->ram,
            $asset->storage,
            $asset->condition,
            $asset->created_at?->format('Y-m-d H:i'),
        ];
    }
}