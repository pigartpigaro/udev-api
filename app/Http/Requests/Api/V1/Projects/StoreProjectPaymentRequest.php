<?php

namespace App\Http\Requests\Api\V1\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Tenancy\WorkspaceContext;

class StoreProjectPaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', Rule::exists('project_invoices', 'id')->where('workspace_id', app(WorkspaceContext::class)->id())->where('status', 'issued')],
            'payment_type' => ['required', Rule::in(['down_payment', 'installment', 'final'])],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999.99'],
            'received_at' => ['required', 'date'],
            'method' => ['required', Rule::in(['cash', 'bank_transfer', 'other'])],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
