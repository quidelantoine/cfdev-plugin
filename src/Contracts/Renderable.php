<?php

namespace Weblitzer\CFDev\Contracts;

interface Renderable
{
    /** @param string|array<mixed> $value */
    public function outputHtml(string|array $value): string;
}
