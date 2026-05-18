<?php

// src/Validation/Rules/Min_Length.php
namespace CFDev\Validation\Rules;

use CFDev\Contracts\Validatable;

final class MinLength implements Validatable
{
    public function __construct(private readonly int $min)
    {
    }

    public function validate(mixed $value): bool
    {
        return strlen((string) $value) >= $this->min;
    }

    public function getError(): string
    {
        return sprintf(__('This field must be at least %d characters.', 'cfdev'), $this->min);
    }
}
