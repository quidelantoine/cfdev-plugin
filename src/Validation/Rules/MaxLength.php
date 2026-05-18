<?php

// src/Validation/Rules/Max_Length.php
namespace Weblitzer\CFDev\Validation\Rules;

use Weblitzer\CFDev\Contracts\Validatable;

final class MaxLength implements Validatable
{
    public function __construct(private readonly int $max)
    {
    }

    public function validate(mixed $value): bool
    {
        if (! is_scalar($value) && $value !== null) {
            return false;
        }

        return strlen((string) $value) <= $this->max;
    }

    public function getError(): string
    {
        return sprintf(__('This field must not exceed %d characters.', 'cfdev'), $this->max);
    }
}
