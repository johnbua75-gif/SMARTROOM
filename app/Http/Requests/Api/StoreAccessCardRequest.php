<?php

namespace App\Http\Requests\Api;

use App\Models\AccessCard;
use App\Models\User;
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

        $inputUid = (string) $this->input('rfid_uid');
        if (! AccessCard::isValidRfidUid($inputUid)) {
            return;
        }

        $normalizedUid = AccessCard::normalizeRfidUid($inputUid);
        $rfidUid = implode(':', str_split($normalizedUid, 2));

        $this->merge(['rfid_uid' => $rfidUid]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! User::query()->eligibleRfidCardholders()->whereKey($value)->exists()) {
                        $fail('The selected cardholder must be an active faculty member.');
                    }
                },
            ],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'card_number' => ['required', 'string', 'max:255', 'unique:access_cards,card_number'],
            'rfid_uid' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! AccessCard::isValidRfidUid((string) $value)) {
                        $fail('The RFID UID must contain 4, 7, or 10 hexadecimal bytes.');
                    }
                },
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (AccessCard::query()->whereNormalizedRfidUid((string) $value)->exists()) {
                        $fail('The RFID UID has already been taken.');
                    }
                },
            ],
            'status' => ['nullable', 'string', 'max:50'],
            'expires_at' => ['nullable', 'date'],
            'last_accessed_at' => ['nullable', 'date'],
            'access_count' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
