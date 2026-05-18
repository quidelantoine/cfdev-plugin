<?php

namespace CFDev\Validation\Rules;

use CFDev\Contracts\Validatable;

final class Slug implements Validatable
{
    public function validate(mixed $value): bool
    {
        if (! is_scalar($value) && $value !== null) {
            return false;
        }

        return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $value);
    }

    public function getError(): string
    {
        return __('This field must be a valid slug (e.g. my-slug-123).', 'cfdev');
    }
}
