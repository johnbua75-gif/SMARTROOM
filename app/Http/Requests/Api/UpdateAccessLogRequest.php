<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccessLogRequest extends FormRequest
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
            'access_card_id' => ['sometimes', 'integer', 'exists:access_cards,id'],
            'classroom_id' => ['sometimes', 'integer', 'exists:classrooms,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'direction' => ['sometimes', 'string', 'max:50'],
            'result' => ['sometimes', 'string', 'max:50'],
            'reason' => ['nullable', 'string', 'max:255'],
            'accessed_at' => ['sometimes', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
