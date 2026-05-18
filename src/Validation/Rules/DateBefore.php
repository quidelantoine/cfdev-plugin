<?php

namespace Weblitzer\CFDev\Validation\Rules;

use Weblitzer\CFDev\Contracts\Validatable;
use DateTime;

/**
 * Example:
 *   new Date_Before('2030-12-31')
 */
final class DateBefore implements Validatable
{
    private readonly DateTime $limit;

    public function __construct(
        string $date,
        private readonly string $format = 'Y-m-d'
    ) {
        $this->limit = new DateTime($date);
    }

    public function validate(mixed $value): bool
    {
        $date = DateTime::createFromFormat($this->format, (string) $value)
             ?: DateTime::createFromFormat('Y-m-d H:i', (string) $value);

        return $date !== false && $date < $this->limit;
    }

    public function getError(): string
    {
        return sprintf(
            __('This date must be before %s.', 'cfdev'),
            $this->limit->format($this->format)
        );
    }
}
