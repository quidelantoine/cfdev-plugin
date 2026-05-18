<?php

namespace CFDev\Validation\Rules;

use CFDev\Contracts\Validatable;

final class Regex implements Validatable
{
    public function __construct(
        private readonly string $pattern
    ) {
    }

    public function validate(mixed $value): bool
    {
        return (bool) preg_match($this->pattern, (string) $value);
    }

    public function getError(): string
    {
        return __('This field has an invalid format.', 'cfdev');
    }
}
