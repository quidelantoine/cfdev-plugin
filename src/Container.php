<?php

namespace CFDev;

use RuntimeException;

final class Container
{
    private array $bindings = [];

    public function bind(string $key, mixed $value): void
    {
        $this->bindings[$key] = $value;
    }

    public function get(string $key): mixed
    {
        if (! isset($this->bindings[$key])) {
            throw new RuntimeException(sprintf(
                "Binding '%s' not found in container",
                esc_html($key)
            ));
        }

        return $this->bindings[$key];
    }
}
