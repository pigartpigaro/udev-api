<?php

namespace App\Http\Requests\Api\V1\Projects;

use App\Tenancy\WorkspaceContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamLoanRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $workspaceId = app(WorkspaceContext::class)->id();
        return [
            'borrower_id' => ['required', 'integer', Rule::exists('workspace_user', 'user_id')->where('workspace_id', $workspaceId)],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999.99'],
            'issued_at' => ['required', 'date'], 'due_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'purpose' => ['required', 'string', 'max:180'], 'method' => ['required', Rule::in(['cash', 'bank_transfer', 'other'])],
            'reference' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
