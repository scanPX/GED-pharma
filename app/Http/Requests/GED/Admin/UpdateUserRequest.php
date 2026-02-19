<?php

namespace App\Http\Requests\GED\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->route('user'))],
            'password' => ['nullable', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            'role' => ['sometimes', 'string', 'exists:ged_roles,name'],
            'department_id' => ['nullable', 'integer', 'exists:departements,id'],
            'fonction_id' => ['nullable', 'integer', 'exists:fonctions,id'],
            'title' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'must_change_password' => ['boolean'],
        ];
    }
}
