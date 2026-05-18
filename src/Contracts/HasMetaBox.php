<?php

// src/Contracts/Has_Meta_Box.php
namespace CFDev\Contracts;

interface HasMetaBox
{
    /** @param array<mixed> $fields */
    public function addMetaBox(string $id, string $title, array $fields = [], string $context = 'normal', string $priority = 'default'): static;
}
