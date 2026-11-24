<?php

namespace App\Http\Requests;

use App\Enums\VehicleType;
use App\Models\Courier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Same rules as creating, but every field is optional (PUT and PATCH both work),
 * and the courier being updated is ignored by the unique checks.
 */
class UpdateCourierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Courier $courier */
        $courier = $this->route('courier');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => [
                'sometimes', 'required', 'string', 'regex:/^\+?[0-9]{8,15}$/',
                Rule::unique('couriers', 'phone')->ignore($courier),
            ],
            'email' => [
                'sometimes', 'nullable', 'email', 'max:255',
                Rule::unique('couriers', 'email')->ignore($courier),
            ],
            'level' => ['sometimes', 'required', 'integer', 'between:'.Courier::MIN_LEVEL.','.Courier::MAX_LEVEL],
            'vehicle_type' => ['sometimes', 'required', Rule::enum(VehicleType::class)],
            'vehicle_plate_number' => ['sometimes', 'nullable', 'string', 'max:15'],
            'address' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
