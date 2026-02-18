<?php

namespace App\Http\Requests;

use App\Enums\PlatformRole;
use App\Models\WeeklyActivity;
use Illuminate\Foundation\Http\FormRequest;

class StoreWeeklyActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole([PlatformRole::ADMIN, PlatformRole::COMMUNICATION]);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'guests' => 'nullable|string|max:1000',
            'activity_date' => 'nullable|date',
            'activity_time' => 'nullable|date_format:H:i',
            'location' => 'nullable|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(WeeklyActivity::ACTIVITY_TYPES)),
            'is_highlighted' => 'boolean',
        ];
    }
}

