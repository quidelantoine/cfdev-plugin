<?php

// src/Validation/Rules/Numeric.php
namespace CFDev\Validation\Rules;

use CFDev\Contracts\Validatable;

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
