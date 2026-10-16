<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceRequestPriorityEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'priority' => ['sometimes', Rule::enum(MaintenanceRequestPriorityEnum::class)],
        ];
    }
}
