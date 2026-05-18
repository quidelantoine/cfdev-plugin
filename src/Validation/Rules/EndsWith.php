<?php

namespace Weblitzer\CFDev\Validation\Rules;

use Weblitzer\CFDev\Contracts\Validatable;

final class EndsWith implements Validatable
{
    public function __construct(
        private readonly string $suffix
    ) {
    }

    public function validate(mixed $value): bool
    {
        return str_ends_with((string) $value, $this->suffix);
    }

    public function getError(): string
    {
        return sprintf(__('This field must end with "%s".', 'cfdev'), $this->suffix);
    }
}
