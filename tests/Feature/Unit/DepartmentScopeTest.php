<?php

use App\Support\DepartmentScope;

it('matches IT department names without matching unrelated substrings', function (): void {
    expect(DepartmentScope::isItDepartment('Hospitality'))->toBeFalse()
        ->and(DepartmentScope::isItDepartment('Information Technology'))->toBeTrue()
        ->and(DepartmentScope::isItDepartment('IT'))->toBeTrue()
        ->and(DepartmentScope::isItDepartment('CIT'))->toBeTrue()
        ->and(DepartmentScope::isItDepartment('CITE'))->toBeTrue()
        ->and(DepartmentScope::isItDepartment('ICT'))->toBeTrue()
        ->and(DepartmentScope::isItDepartment('BSIT'))->toBeTrue()
        ->and(DepartmentScope::isItDepartment('Computer Science'))->toBeTrue();
});
