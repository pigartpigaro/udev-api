<?php

namespace App\Http\Requests\Api\V1\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Tenancy\WorkspaceContext;

class StoreProjectInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')->where('workspace_id', app(WorkspaceContext::class)->id())->whereNotIn('status', ['cancelled'])],
            'billing_cycle' => ['required', Rule::in(['monthly', 'one_time'])],
            'billing_period' => ['required_if:billing_cycle,monthly', 'nullable', 'date_format:Y-m'],
            'supersedes_invoice_id' => ['nullable', 'integer', Rule::exists('project_invoices', 'id')->where('workspace_id', app(WorkspaceContext::class)->id())],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999.99'],
            'issued_at' => ['required', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
