<?php

namespace App\Http\Requests;

use App\Enums\PlatformRole;
use App\Models\MenuWeek;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Creation d'une semaine du cycle, vide ou copiee depuis une autre.
 */
class StoreMenuWeekRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole(PlatformRole::ADMIN);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:' . MenuWeek::NAME_MAX],
            'source_id' => ['nullable', 'integer', 'exists:menu_weeks,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Donnez un nom a la semaine.',
            'name.max' => 'Le nom ne peut pas depasser ' . MenuWeek::NAME_MAX . ' caracteres.',
            'source_id.exists' => 'La semaine a copier n\'existe plus.',
        ];
    }
}
