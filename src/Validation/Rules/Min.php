<?php

namespace CFDev\Validation\Rules;

use CFDev\Contracts\Validatable;

final class Min implements Validatable
{
    public function __construct(
        private readonly int|float $min
    ) {
    }

    public function validate(mixed $value): bool
    {
        return is_numeric($value) && (float) $value >= $this->min;
    }

    public function getError(): string
    {
        return sprintf(__('This field must be at least %s.', 'cfdev'), $this->min);
    }
}
