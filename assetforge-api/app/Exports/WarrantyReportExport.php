<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WarrantyReportExport implements FromCollection, WithHeadings, WithMapping
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
            'Asset Name',
            'Category',
            'Location',
            'Provider',
            'Policy Number',
            'Warranty Type',
            'Start Date',
            'End Date',
            'Status',
            'Days Remaining',
        ];
    }

    public function map($row): array
    {
        return [
            $row['asset_tag'],
            $row['serial_number'],
            $row['asset_name'],
            $row['category'],
            $row['location'],
            $row['provider_name'],
            $row['policy_number'],
            $row['warranty_type'],
            $row['start_date'],
            $row['end_date'],
            $row['status'],
            $row['days_remaining'],
        ];
    }
}
