<?php

// src/Contracts/Supportable.php
namespace CFDev\Contracts;

interface Supportable
{
    public function addSupport(string|array $feature): static;
    public function removeSupport(string|array $features): static;
    public function supports(string $feature): bool;
}
