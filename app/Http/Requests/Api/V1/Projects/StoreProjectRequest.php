<?php

namespace App\Http\Requests\Api\V1\Projects;

use App\Models\Projects\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Tenancy\WorkspaceContext;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('workspace_id', app(WorkspaceContext::class)->id())->whereNull('deleted_at')],
            'project_type_id' => ['required', 'integer', Rule::exists('project_types', 'id')->where('workspace_id', app(WorkspaceContext::class)->id())->where('is_active', true)],
            'start_date' => ['nullable', 'date'],
            'target_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'required', Rule::in(Project::STATUSES)],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
