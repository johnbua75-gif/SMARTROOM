<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HalfHourTime implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/(?:^|[T ])\d{2}:(?:00|30)(?::00)?(?:\.\d+)?(?:Z|[+-]\d{2}:?\d{2})?$/D', $value) !== 1) {
            $fail('The :attribute must be on a 30-minute increment (:00 or :30).');
        }
    }
}
