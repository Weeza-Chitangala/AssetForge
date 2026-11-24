<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected array $rows) {}

    public function collection(): Enumerable
    {
        return new Collection($this->rows);
    }

    public function headings(): array
    {
        return [
            'Asset Tag',
            'Serial Number',
            'Name',
            'Category',
            'Manufacturer',
            'Model',
            'Status',
            'Location',
            'Assigned To',
            'Purchase Date',
            'Purchase Cost (USD)',
            'Warranty Expiry',
            'Condition',
        ];
    }

    public function map($row): array
    {
        return [
            $row['asset_tag'],
            $row['serial_number'],
            $row['name'],
            $row['category'],
            $row['manufacturer'],
            $row['model'],
            $row['status'],
            $row['location'],
            $row['assigned_to'],
            $row['purchase_date'],
            $row['purchase_cost'],
            $row['warranty_expiry_date'],
            $row['condition'],
        ];
    }
}
