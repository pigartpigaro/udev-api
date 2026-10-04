<?php

namespace App\Http\Requests\Api\V1\AccessControl;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:menus,id'],
            'permission_id' => ['nullable', 'integer', 'exists:permissions,id'],
            'key' => ['required', 'string', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', 'max:100', 'unique:menus,key'],
            'label' => ['required', 'string', 'max:100'],
            'route_name' => ['nullable', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
