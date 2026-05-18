<?php

// src/Validation/Validator.php
namespace CFDev\Validation;

use CFDev\Contracts\Validatable;

/**
 * Runs a set of validation rules against a value
 *
 * @package CFDev\Validation
 * @since   1.0.0
 *
 * @example
 * $validator = new Validator($value, [
 *     new Rules\Required(),
 *     new Rules\Min_Length(3),
 *     new Rules\Email(),
 * ]);
 *
 * if ( ! $validator->passes() ) {
 *     $errors = $validator->errors();
 * }
 */
final class Validator
{
    /**
     * @var array<string>
     */
    private array $errors = [];

    /**
     * @since  1.0.0
     * @param  mixed                  $value  Value to validate
     * @param  array<Validatable>     $rules  Rules to run
     */
    public function __construct(
        private readonly mixed $value,
        private readonly array $rules
    ) {
        $this->run();
    }

    /**
     * Runs all rules against the value
     *
     * @since  1.0.0
     * @return void
     */
    private function run(): void
    {
        foreach ($this->rules as $rule) {
            if (! $rule->validate($this->value)) {
                $this->errors[] = $rule->getError();
            }
        }
    }

    /**
     * Returns true if all rules passed
     *
     * @since  1.0.0
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Returns all validation error messages
     *
     * @since  1.0.0
     * @return array<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
