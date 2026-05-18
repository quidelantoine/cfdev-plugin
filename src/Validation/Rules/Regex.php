<?php

namespace Weblitzer\CFDev\Validation\Rules;

use Weblitzer\CFDev\Contracts\Validatable;

final class Regex implements Validatable
{
    public function __construct(
        private readonly string $pattern
    ) {
    }

    public function validate(mixed $value): bool
    {
        if (! is_scalar($value) && $value !== null) {
            return false;
        }

        return (bool) preg_match($this->pattern, (string) $value);
    }

    public function getError(): string
    {
        return __('This field has an invalid format.', 'cfdev');
    }
}
