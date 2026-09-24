<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class MapFixedScheduleRequest extends FormRequest
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
            'date' => ['nullable', 'date'],
            'week_start' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $date = $this->input('date');
            $weekStart = $this->input('week_start');

            if ($date && $weekStart) {
                $validator->errors()->add('date', 'Use either date or week_start, not both.');
            }
        });
    }
}
