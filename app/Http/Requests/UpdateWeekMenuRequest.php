<?php

namespace App\Http\Requests;

use App\Enums\PlatformRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWeekMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole(PlatformRole::ADMIN);
    }

    public function rules(): array
    {
        return [
            'dish_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'nullable|integer|min:0',
            'available' => 'sometimes|boolean',
        ];
    }
}
