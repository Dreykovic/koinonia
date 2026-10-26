<?php

namespace App\Http\Requests;

use App\Enums\PlatformRole;
use App\Models\MenuWeek;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Renommage d'une semaine ou mise en reserve / retour dans le cycle.
 */
class UpdateMenuWeekRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole(PlatformRole::ADMIN);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:' . MenuWeek::NAME_MAX],
            'in_cycle' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la semaine ne peut pas etre vide.',
            'name.max' => 'Le nom ne peut pas depasser ' . MenuWeek::NAME_MAX . ' caracteres.',
        ];
    }
}
