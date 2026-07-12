<?php

use App\Models\Employee;

test('employee sensitive service-managed fields are not mass assignable', function () {
    $employee = new Employee;

    expect($employee->isFillable('created_by'))->toBeFalse();
    expect($employee->isFillable('updated_by'))->toBeFalse();
    expect($employee->isFillable('face_embedding'))->toBeFalse();
    expect($employee->isFillable('pin'))->toBeFalse();
});
