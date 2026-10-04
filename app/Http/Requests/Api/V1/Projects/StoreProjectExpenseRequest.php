<?php

namespace App\Http\Requests\Api\V1\Projects;

use App\Models\Projects\ProjectExpense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Tenancy\WorkspaceContext;

class StoreProjectExpenseRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->where('workspace_id', app(WorkspaceContext::class)->id())],
            'category' => ['required', Rule::in(ProjectExpense::CATEGORIES)],
            'paid_to' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999.99'],
            'spent_at' => ['required', 'date'],
            'method' => ['required', Rule::in(['cash', 'bank_transfer', 'other'])],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
