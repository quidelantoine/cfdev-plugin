<?php

// src/Validation/Rules/Email.php
namespace CFDev\Validation\Rules;

use CFDev\Contracts\Validatable;

final class Email implements Validatable
{
    public function validate(mixed $value): bool
    {
        return (bool) is_email($value); // Fonction WordPress
    }

    public function getError(): string
    {
        return __('This field must be a valid email address.', 'cfdev');
    }
}
