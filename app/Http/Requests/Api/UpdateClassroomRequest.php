<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'building' => ['sometimes', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:255'],
            'capacity' => ['sometimes', 'integer', 'min:0'],
            'current_occupancy' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', 'in:available,occupied,reserved,maintenance,unavailable'],
            'unavailable_reason' => ['nullable', 'string', 'max:255', 'required_if:status,maintenance,unavailable'],
            'rfid_status' => ['sometimes', 'string', 'max:50'],
            'temperature' => ['nullable', 'numeric'],
            'last_accessed_at' => ['nullable', 'date'],
        ];
    }
}
