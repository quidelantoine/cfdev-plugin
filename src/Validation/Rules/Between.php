<?php

namespace Weblitzer\CFDev\Validation\Rules;

use Weblitzer\CFDev\Contracts\Validatable;

final class Between implements Validatable
{
    public function __construct(
        private readonly int|float $min,
        private readonly int|float $max
    ) {
    }

    public function validate(mixed $value): bool
    {
        return is_numeric($value)
            && (float) $value >= $this->min
            && (float) $value <= $this->max;
    }

    public function getError(): string
    {
        return sprintf(__('This field must be between %s and %s.', 'cfdev'), $this->min, $this->max);
    }
}
