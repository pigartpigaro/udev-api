<?php

namespace App\Http\Requests\Api\V1\AccessControl;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuRequest extends FormRequest
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
        $menu = $this->route('menu');

        return [
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:menus,id', Rule::notIn([$menu?->id])],
            'permission_id' => ['sometimes', 'nullable', 'integer', 'exists:permissions,id'],
            'key' => ['sometimes', 'required', 'string', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', 'max:100', Rule::unique('menus', 'key')->ignore($menu?->id)],
            'label' => ['sometimes', 'required', 'string', 'max:100'],
            'route_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
