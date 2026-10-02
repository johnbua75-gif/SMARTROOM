<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CheckAccessReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($this->filled('access_card_id') || $this->filled('rfid_uid')) {
            return true;
        }

        $requestedUserId = filter_var($this->input('user_id'), FILTER_VALIDATE_INT);

        return $requestedUserId === false
            || (int) $requestedUserId === (int) $user->getAuthIdentifier();
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'access_card_id' => ['nullable', 'integer', 'exists:access_cards,id'],
            'rfid_uid' => ['nullable', 'string', 'max:255'],
        ];
    }
}
