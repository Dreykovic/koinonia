<?php

namespace App\Http\Requests;

use App\Enums\PlatformRole;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Enregistrement groupe d'une semaine complete du cycle de menus.
 */
class UpdateWeekMenusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole(PlatformRole::ADMIN);
    }

    public function rules(): array
    {
        return [
            'menus' => ['required', 'array'],
            'menus.*.dish_name' => ['required', 'string', 'max:150'],
            'menus.*.available' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'menus.*.dish_name.required' => 'Chaque plat doit avoir un nom.',
            'menus.*.dish_name.max' => 'Le nom d\'un plat ne peut pas depasser 150 caracteres.',
        ];
    }
}
