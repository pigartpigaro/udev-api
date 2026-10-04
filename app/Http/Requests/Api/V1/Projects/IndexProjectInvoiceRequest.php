<?php

namespace App\Http\Requests\Api\V1\Projects;

use Illuminate\Foundation\Http\FormRequest;

class IndexProjectInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', 'in:draft,issued,cancelled'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100']];
    }
}
