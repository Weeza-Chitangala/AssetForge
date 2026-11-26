<?php

namespace App\Http\Requests\Reference;

use App\Models\Category;
use App\Models\Manufacturer;
use App\Rules\TenantRecordExists;
use Illuminate\Foundation\Http\FormRequest;

class AssetModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'manufacturer_id' => ['required', 'uuid', new TenantRecordExists(Manufacturer::class, $tenantId)],
            'category_id' => ['nullable', 'uuid', new TenantRecordExists(Category::class, $tenantId)],
            'model_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }
}