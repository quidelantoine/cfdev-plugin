<?php

// src/Validation/Rules/Required.php
namespace CFDev\Validation\Rules;

use CFDev\Contracts\Validatable;

final class Required implements Validatable
{
    public function validate(mixed $value): bool
    {
        return ! empty($value);
    }

    public function getError(): string
    {
        return __('This field is required.', 'cfdev');
    }
}
