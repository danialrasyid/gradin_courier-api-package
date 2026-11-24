<?php

namespace App\Http\Requests;

use App\Enums\VehicleType;
use App\Models\Courier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Digits only with an optional leading "+", 8 to 15 digits.
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/', 'unique:couriers,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:couriers,email'],
            'level' => ['required', 'integer', 'between:'.Courier::MIN_LEVEL.','.Courier::MAX_LEVEL],
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)],
            'vehicle_plate_number' => ['nullable', 'string', 'max:15'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
