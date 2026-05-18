<?php

// src/Contracts/HasTaxonomy.php
namespace Weblitzer\CFDev\Contracts;

interface HasTaxonomy
{
    /**
     * @param string|array<string> $name
     * @param array<mixed>         $args
     * @param array<string>        $labels
     */
    public function addTaxonomy(string|array $name, array $args = [], array $labels = []): static;
}
