<?php

namespace CFDev\Validation\Rules;

use CFDev\Contracts\Validatable;
use DateTime;

/**
 * Example:
 *   new Date_After('2024-01-01')
 *   new Date_After('2024-01-01', 'd/m/Y') // custom format
 */
final class DateAfter implements Validatable
{
    private readonly DateTime $limit;

    public function __construct(
        string $date,
        private readonly string $format = 'Y-m-d'
    ) {
        $this->limit = ( new DateTime($date) )->setTime(0, 0, 0);
    }

    public function validate(mixed $value): bool
    {
        $date = DateTime::createFromFormat($this->format, (string) $value)
             ?: DateTime::createFromFormat('Y-m-d H:i', (string) $value);

        if ($date === false) {
            return false;
        }

        return $date->setTime(0, 0, 0) > $this->limit;
    }

    public function getError(): string
    {
        return sprintf(
            __('This date must be after %s.', 'cfdev'),
            $this->limit->format($this->format)
        );
    }
}
