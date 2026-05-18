<?php
 
namespace Weblitzer\CFDev\Validation\Rules;
 
use Weblitzer\CFDev\Contracts\Validatable;
 
final class Alpha implements Validatable
{
    public function validate(mixed $value): bool
    {
        return (bool) preg_match('/^[a-zA-Z]+$/', $value);
    }
 
    public function getError(): string
    {
        return __('This field must contain only letters.', 'cfdev');
    }
}
