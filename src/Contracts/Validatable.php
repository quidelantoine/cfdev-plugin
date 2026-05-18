<?php

// src/Contracts/Validatable.php
namespace Weblitzer\CFDev\Contracts;

interface Validatable
{
    /**
     * Validates a value and returns true if valid
     *
     * @param  mixed $value
     * @return bool
     */
    public function validate(mixed $value): bool;

    /**
     * Returns the error message if validation fails
     *
     * @return string
     */
    public function getError(): string;
}
