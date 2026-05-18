<?php

// src/Validation/Rules/Min_Length.php
namespace Weblitzer\CFDev\Validation\Rules;

use Weblitzer\CFDev\Contracts\Validatable;

final class MinLength implements Validatable
{
    public function __construct(private readonly int $min)
    {
    }

    public function validate(mixed $value): bool
    {
        if (! is_scalar($value) && $value !== null) {
            return false;
        }

        return strlen((string) $value) >= $this->min;
    }

    public function getError(): string
    {
        return sprintf(__('This field must be at least %d characters.', 'cfdev'), $this->min);
    }
}
