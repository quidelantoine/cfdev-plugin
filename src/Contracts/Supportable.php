<?php

// src/Contracts/Supportable.php
namespace Weblitzer\CFDev\Contracts;

interface Supportable
{
    /** @param string|array<string> $feature */
    public function addSupport(string|array $feature): static;
    /** @param string|array<string> $features */
    public function removeSupport(string|array $features): static;
    public function supports(string $feature): bool;
}
