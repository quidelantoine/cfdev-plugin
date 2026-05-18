<?php

namespace CFDev\Validation\Rules;

use CFDev\Contracts\Validatable;

final class StartsWith implements Validatable
{
    public function __construct(
        private readonly string $prefix
    ) {
    }

    public function validate(mixed $value): bool
    {
        return str_starts_with((string) $value, $this->prefix);
    }

    public function getError(): string
    {
        return sprintf(__('This field must start with "%s".', 'cfdev'), $this->prefix);
    }
}
