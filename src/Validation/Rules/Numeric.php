<?php

// src/Validation/Rules/Numeric.php
namespace Weblitzer\CFDev\Validation\Rules;

use Weblitzer\CFDev\Contracts\Validatable;

final class Numeric implements Validatable
{
    public function validate(mixed $value): bool
    {
        return is_numeric($value);
    }

    public function getError(): string
    {
        return __('This field must be a number.', 'cfdev');
    }
}
