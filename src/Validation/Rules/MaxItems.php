<?php

namespace Weblitzer\CFDev\Validation\Rules;

use Weblitzer\CFDev\Contracts\Validatable;

final class MaxItems implements Validatable
{
    public function __construct(
        private readonly int $max
    ) {
    }

    public function validate(mixed $value): bool
    {
        if (! is_array($value)) {
            return true;
        }

        $items = array_filter($value, fn($v) => $v !== '' && $v !== null && $v !== '-1');

        return count($items) <= $this->max;
    }

    public function getError(): string
    {
        return sprintf(
            _n(
                'This field must not exceed %d item.',
                'This field must not exceed %d items.',
                $this->max,
                'cfdev'
            ),
            $this->max
        );
    }
}