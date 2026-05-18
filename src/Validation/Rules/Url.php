<?php

namespace CFDev\Validation\Rules;

use CFDev\Contracts\Validatable;

final class Url implements Validatable
{
    public function validate(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    public function getError(): string
    {
        return __('This field must be a valid URL.', 'cfdev');
    }
}
