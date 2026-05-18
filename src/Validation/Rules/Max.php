<?php

namespace Weblitzer\CFDev\Validation\Rules;

use Weblitzer\CFDev\Contracts\Validatable;

final class Max implements Validatable
{
    public function __construct(
        private readonly int|float $max
    ) {
    }

    public function validate(mixed $value): bool
    {
        return is_numeric($value) && (float) $value <= $this->max;
    }

    public function getError(): string
    {
        return sprintf(__('This field must be at most %s.', 'cfdev'), $this->max);
    }
}
