<?php

namespace App\Http\Requests\Api;

use App\Models\AccessCard;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccessCardRequest extends FormRequest
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
        $accessCardId = $this->route('access_card')?->id ?? $this->route('accessCard')?->id;

        return [
            'user_id' => [
                'sometimes',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! User::query()->eligibleRfidCardholders()->whereKey($value)->exists()) {
                        $fail('The selected cardholder must be an active faculty member.');
                    }
                },
            ],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'card_number' => ['sometimes', 'string', 'max:255', 'unique:access_cards,card_number,'.$accessCardId],
            'rfid_uid' => [
                'sometimes',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! AccessCard::isValidRfidUid((string) $value)) {
                        $fail('The RFID UID must contain 4, 7, or 10 hexadecimal bytes.');
                    }
                },
                function (string $attribute, mixed $value, \Closure $fail) use ($accessCardId): void {
                    $matchingCardExists = AccessCard::query()
                        ->whereNormalizedRfidUid((string) $value)
                        ->where('id', '!=', $accessCardId)
                        ->exists();

                    if ($matchingCardExists) {
                        $fail('The RFID UID has already been taken.');
                    }
                },
            ],
            'status' => ['sometimes', 'string', 'max:50'],
            'expires_at' => ['nullable', 'date'],
            'last_accessed_at' => ['nullable', 'date'],
            'access_count' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
