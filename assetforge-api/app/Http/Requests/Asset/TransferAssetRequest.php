<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class TransferAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => ['nullable', 'uuid', 'exists:locations,id'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'company_id' => ['nullable', 'uuid', 'exists:companies,id'],
            'transfer_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}