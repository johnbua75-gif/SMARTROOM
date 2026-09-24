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

        foreach (self::IT_KEYWORDS as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
