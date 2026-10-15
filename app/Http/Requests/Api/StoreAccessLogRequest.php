<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccessLogRequest extends FormRequest
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
            'access_card_id' => ['sometimes', 'nullable', 'integer', 'exists:access_cards,id'],
            'classroom_id' => [$this->attributes->has('device') ? 'nullable' : 'required', 'integer', 'exists:classrooms,id'],
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'direction' => ['required', 'in:entry,exit'],
            'result' => ['required', 'in:granted,denied'],
            'reason' => ['nullable', 'string', 'max:255'],
            'accessed_at' => [
                'sometimes',
                'nullable',
                'date',
                'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/',
            ],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
