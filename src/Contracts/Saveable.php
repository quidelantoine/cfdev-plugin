<?php

namespace Weblitzer\CFDev\Contracts;

interface Saveable
{
    /**
     * @param  string|array<mixed> $value
     * @return int|bool|\WP_Error
     */
    public function save(int $objectId, string|array $value): int|bool|\WP_Error;
}
