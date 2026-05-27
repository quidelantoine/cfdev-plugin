<?php

namespace Weblitzer\CFDev\Abstracts;

abstract class FieldContainer
{
    public string $id = '';
    public string|bool $meta_type = false;

    abstract public function output(object $post): void;
}
