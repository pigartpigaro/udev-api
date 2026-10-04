<?php

namespace App\Http\Requests\Api\V1\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;
use App\Tenancy\WorkspaceContext;

class ProjectFinancialReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->where('workspace_id', app(WorkspaceContext::class)->id())],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('date_from') || $validator->errors()->has('date_to')) return;
            $from = $this->input('date_from');
            $to = $this->input('date_to');
            if ($from && $to && $to < $from) $validator->errors()->add('date_to', 'Tanggal akhir tidak boleh sebelum tanggal awal.');
        });
    }
}
