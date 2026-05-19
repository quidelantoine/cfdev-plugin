<?php

namespace Weblitzer\CFDev\Validation\Rules;

use Weblitzer\CFDev\Contracts\Validatable;

final class MinItems implements Validatable
{
    public function __construct(
        private readonly int $min
    ) {
    }

    public function validate(mixed $value): bool
    {
        if (! is_array($value)) {
            return $this->min <= 0;
        }

        $items = array_filter($value, fn($v) => $v !== '' && $v !== null && $v !== '-1');

        return count($items) >= $this->min;
    }

    public function getError(): string
    {
        return sprintf(
            _n(
                'This field requires at least %d item.',
                'This field requires at least %d items.',
                $this->min,
                'cfdev'
            ),
            $this->min
        );
    }
}