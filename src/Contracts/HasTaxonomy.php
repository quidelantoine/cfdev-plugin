<?php

// src/Contracts/HasTaxonomy.php
namespace CFDev\Contracts;

interface HasTaxonomy
{
    public function addTaxonomy(string|array $name, array $args = [], array $labels = []): static;
}
