<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassroomRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'building' => ['required', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:0'],
            'current_occupancy' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'in:available,occupied,reserved,maintenance,unavailable'],
            'unavailable_reason' => ['nullable', 'string', 'max:255', 'required_if:status,maintenance,unavailable'],
            'rfid_status' => ['nullable', 'string', 'max:50'],
            'temperature' => ['nullable', 'numeric'],
            'last_accessed_at' => ['nullable', 'date'],
        ];
    }
}
