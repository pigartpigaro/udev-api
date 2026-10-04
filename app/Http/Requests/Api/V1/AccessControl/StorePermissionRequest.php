<?php

namespace App\Http\Requests\Api\V1\AccessControl;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', 'max:150', 'unique:permissions,key'],
            'label' => ['required', 'string', 'max:150'],
        ];
    }
}
