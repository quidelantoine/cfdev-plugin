<?php

namespace Weblitzer\CFDev\Validation\Rules;

use Weblitzer\CFDev\Contracts\Validatable;
use DateTime;

/**
 * Ensures the date is strictly in the future (after today at midnight).
 *
 * Example:
 *   new Date_After_Today()
 *   new Date_After_Today('d/m/Y') // custom input format
 */
final class DateAfterToday implements Validatable
{
    public function __construct(
        private readonly string $format = 'Y-m-d'
    ) {
    }

    public function validate(mixed $value): bool
    {
        $date = DateTime::createFromFormat($this->format, (string) $value)
             ?: DateTime::createFromFormat('Y-m-d H:i', (string) $value);

        if ($date === false) {
            return false;
        }

        $today = ( new DateTime('today') )->setTime(0, 0, 0);

        return $date->setTime(0, 0, 0) > $today;
    }

    public function getError(): string
    {
        return __('This date must be in the future.', 'cfdev');
    }
}
