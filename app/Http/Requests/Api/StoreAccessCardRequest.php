<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccessCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('rfid_uid')) {
            return;
        }

        $rfidUid = trim((string) $this->input('rfid_uid'));
        $rfidUid = preg_replace('/^rfid[-_\s]?/i', '', $rfidUid) ?? $rfidUid;

        $this->merge(['rfid_uid' => $rfidUid]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'card_number' => ['required', 'string', 'max:255', 'unique:access_cards,card_number'],
            'rfid_uid' => ['required', 'string', 'max:255', 'unique:access_cards,rfid_uid'],
            'status' => ['nullable', 'string', 'max:50'],
            'expires_at' => ['nullable', 'date'],
            'last_accessed_at' => ['nullable', 'date'],
            'access_count' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
