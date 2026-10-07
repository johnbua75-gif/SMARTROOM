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
            'access_card_id' => ['nullable', 'integer', 'exists:access_cards,id'],
            'classroom_id' => [$this->attributes->has('device') ? 'nullable' : 'required', 'integer', 'exists:classrooms,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'direction' => ['required', 'string', 'max:50'],
            'result' => ['required', 'string', 'max:50'],
            'reason' => ['nullable', 'string', 'max:255'],
            'accessed_at' => ['required', 'date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
