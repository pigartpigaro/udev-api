<?php

namespace App\Http\Requests\Api\V1\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'billing_period' => ['sometimes', 'nullable', 'required_if:billing_cycle,monthly', 'date_format:Y-m'],
            'billing_cycle' => ['sometimes', 'required', Rule::in(['monthly', 'one_time'])],
            'amount' => ['sometimes', 'required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999.99'],
            'issued_at' => ['sometimes', 'required', 'date'],
            'due_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:issued_at'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
