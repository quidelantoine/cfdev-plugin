<?php

namespace CFDev\Support;

/**
 * Resolves singular, plural and slug from a name input
 *
 * Accepts either a single string or an array of [singular, plural].
 * Used by PostType and Taxonomy to avoid duplicating name resolution logic.
 *
 * @package CFDev\Support
 * @author  quidelantoine
 * @since   1.0.0
 *
 * @example
 * $resolved = new NameResolver(['book', 'books']);
 * echo $resolved->slug;     // 'book'
 * echo $resolved->singular; // 'Book'
 * echo $resolved->plural;   // 'Books'
 *
 * $resolved = new NameResolver('book');
 * echo $resolved->plural;   // 'Books' (auto-pluralized)
 */
final class NameResolver
{
    /**
     * URL-friendly slug
     *
     * @since   1.0.0
     * @var   string
     */
    public readonly string $slug;

    /**
     * Singular display name
     *
     * @since   1.0.0
     * @var   string
     */
    public readonly string $singular;

    /**
     * Plural display name
     *
     * @since   1.0.0
     * @var   string
     */
    public readonly string $plural;

    /**
     * Resolves names from a string or [singular, plural] array
     *
     * @since   1.0.0
     * @param  string|array<string> $name  Singular name, or [singular, plural]
     */
    public function __construct(string|array $name)
    {
        if (is_array($name)) {
            $this->slug     = Str::uglify($name[0]);
            $this->singular = Str::beautify($name[0]);
            $this->plural   = Str::beautify($name[1]);
        } else {
            $this->slug     = Str::uglify($name);
            $this->singular = Str::beautify($name);
            $this->plural   = Str::pluralize(Str::beautify($name));
        }
    }
}
