<?php

namespace CFDev\Validation\Rules;

use CFDev\Contracts\Validatable;

final class Uuid implements Validatable
{
    public function validate(mixed $value): bool
    {
        if (! is_scalar($value) && $value !== null) {
            return false;
        }

        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            (string) $value
        );
    }

    public function getError(): string
    {
        return __('This field must be a valid UUID.', 'cfdev');
    }
}
