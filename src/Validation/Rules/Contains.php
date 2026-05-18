<?php

namespace Weblitzer\CFDev\Validation\Rules;

use Weblitzer\CFDev\Contracts\Validatable;

final class Contains implements Validatable
{
    public function __construct(
        private readonly string $needle
    ) {
    }

    public function validate(mixed $value): bool
    {
        return str_contains((string) $value, $this->needle);
    }

    public function getError(): string
    {
        return sprintf(__('This field must contain "%s".', 'cfdev'), $this->needle);
    }
}
