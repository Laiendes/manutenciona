<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceRequestStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceRequestStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::enum(MaintenanceRequestStatusEnum::class),
            ],
        ];
    }
}
