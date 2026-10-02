<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartFromCardAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'card_number' => 'required|string',
            'schedule_id' => 'sometimes|integer|exists:schedules,id',
        ];
    }
}
