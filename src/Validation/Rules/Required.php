<?php

// src/Validation/Rules/Required.php
namespace Weblitzer\CFDev\Validation\Rules;

use Weblitzer\CFDev\Contracts\Validatable;

final class Required implements Validatable
{
    public function validate(mixed $value): bool
    {
        if (is_array($value)) {
            return !empty(array_filter($value, fn($v) => $v !== '' && $v !== null));
        }
        return ! empty($value);
    }

    public function getError(): string
    {
        return __('This field is required.', 'cfdev');
    }
}
