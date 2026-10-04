<?php

namespace App\Http\Requests\Api\V1\Projects;

use App\Models\Projects\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Tenancy\WorkspaceContext;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'customer_id' => ['sometimes', 'required', 'integer', Rule::exists('customers', 'id')->where('workspace_id', app(WorkspaceContext::class)->id())->whereNull('deleted_at')],
            'project_type_id' => ['sometimes', 'required', 'integer', Rule::exists('project_types', 'id')->where('workspace_id', app(WorkspaceContext::class)->id())->where('is_active', true)],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'target_end_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'required', Rule::in(Project::STATUSES)],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('start_date') || $validator->errors()->has('target_end_date')) {
                return;
            }

            $project = $this->route('project');
            $startDate = $this->input('start_date', $project?->start_date?->toDateString());
            $targetEndDate = $this->input('target_end_date', $project?->target_end_date?->toDateString());

            if ($startDate && $targetEndDate && $targetEndDate < $startDate) {
                $validator->errors()->add('target_end_date', 'Target selesai tidak boleh sebelum tanggal mulai.');
            }
        }];
    }
}
