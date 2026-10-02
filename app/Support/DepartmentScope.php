<?php

namespace App\Support;

final class DepartmentScope
{
    /**
     * @var array<int, string>
     */
    private const IT_KEYWORDS = ['it', 'cit', 'cite', 'information technology'];

    public static function isItDepartment(?string $department): bool
    {
        $normalized = strtolower(trim((string) $department));
        if ($normalized === '') {
            return false;
        }

        $tokens = preg_split('/[^a-z0-9]+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        if (array_intersect($tokens, ['it', 'cit', 'cite', 'ict', 'bsit']) !== []) {
            return true;
        }

        return str_contains($normalized, 'information technology')
            || str_contains($normalized, 'computer science');
    }
}
