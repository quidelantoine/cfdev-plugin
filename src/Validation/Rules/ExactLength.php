<?php

namespace CFDev\Validation\Rules;

use CFDev\Contracts\Validatable;

/**
 * Example:
 *   new Exact_Length(5)  // code postal FR
 *   new Exact_Length(34) // IBAN FR
 */
final class ExactLength implements Validatable
{
    public function __construct(
        private readonly int $length
    ) {
    }

    public function validate(mixed $value): bool
    {
        return mb_strlen((string)$value) === $this->length;
    }

    public function getError(): string
    {
        return sprintf(
            __('This field must be exactly %d characters long.', 'cfdev'),
            $this->length
        );
    }
}
