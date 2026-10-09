<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CheckAccessReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->attributes->get('door_api') === true) {
            return $this->filled('access_card_id') || $this->filled('rfid_uid');
        }

        if ($this->attributes->has('device')) {
            return $this->filled('access_card_id') || $this->filled('rfid_uid');
        }

        $user = $this->user();
        if (! $user) {
            return false;
        }

        $requestedUserId = filter_var($this->input('user_id'), FILTER_VALIDATE_INT);

        return $requestedUserId !== false
            && (int) $requestedUserId === (int) $user->getAuthIdentifier();
    }

    public function rules(): array
    {
        $isDeviceRequest = $this->attributes->has('device');
        $isDoorRequest = $this->attributes->get('door_api') === true || $isDeviceRequest;

        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'classroom_id' => [$isDeviceRequest ? 'nullable' : 'required', 'integer', 'exists:classrooms,id'],
            'access_card_id' => [$isDoorRequest ? 'required_without:rfid_uid' : 'sometimes', 'nullable', 'integer', 'exists:access_cards,id'],
            'rfid_uid' => [$isDoorRequest ? 'required_without:access_card_id' : 'sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
