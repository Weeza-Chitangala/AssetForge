<?php

namespace App\Http\Requests\Repair;

use Illuminate\Foundation\Http\FormRequest;

class AddRepairProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:pending,diagnosing,in_repair,parts_on_order,completed,unrepairable'],
            'comments' => ['required', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'resolution_summary' => ['nullable', 'string'],
        ];
    }
}