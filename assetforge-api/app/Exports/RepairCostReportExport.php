<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RepairCostReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected array $rows) {}

    public function collection(): Enumerable
    {
        return new Collection($this->rows);
    }

    public function headings(): array
    {
        return [
            'Job Number',
            'Asset Tag',
            'Asset Name',
            'Category',
            'Location',
            'Fault Type',
            'Repair Center',
            'Vendor',
            'Status',
            'Technician',
            'Start Date',
            'Completion Date',
            'Cost (USD)',
        ];
    }

    public function map($row): array
    {
        return [
            $row['job_number'],
            $row['asset_tag'],
            $row['asset_name'],
            $row['category'],
            $row['location'],
            $row['fault_type'],
            $row['repair_center'],
            $row['vendor_name'],
            $row['repair_status'],
            $row['technician'],
            $row['start_date'],
            $row['completion_date'],
            $row['cost'],
        ];
    }
}
