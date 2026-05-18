<?php

namespace Weblitzer\CFDev\Validation\Rules;

use Weblitzer\CFDev\Contracts\Validatable;

final class Positive implements Validatable
{
    public function validate(mixed $value): bool
    {
        return is_numeric($value) && (float) $value > 0;
    }

    public function getError(): string
    {
        return __('This field must be a positive number.', 'cfdev');
    }
}
