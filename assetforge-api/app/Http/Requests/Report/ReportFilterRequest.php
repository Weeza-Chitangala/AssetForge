<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'category_id' => ['nullable', 'uuid'],
            'location_id' => ['nullable', 'uuid'],
            'status_id' => ['nullable', 'uuid'],
            'status' => ['nullable', 'string', 'in:active,expiring_soon,expired'],
            'repair_status' => ['nullable', 'string', 'in:pending,diagnosing,in_repair,parts_on_order,completed,unrepairable'],
            'repair_center' => ['nullable', 'string', 'in:internal,external_vendor,warranty_oem'],
            'format' => ['nullable', 'string', 'in:xlsx,csv,pdf'],
        ];
    }
}
