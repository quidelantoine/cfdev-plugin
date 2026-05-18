<?php

namespace Weblitzer\CFDev\Abstracts;

use Weblitzer\CFDev\Contracts\Registerable;
use Weblitzer\CFDev\Contracts\Supportable;
use Weblitzer\CFDev\Contracts\HasMetaBox;

/**
 * Base class for all content types (Post Type, Taxonomy)
 *
 * Provides shared logic for registration, meta boxes,
 * and post type feature management.
 *
 * @package CFDev\Abstracts
 * @author  quidelantoine
 * @since   1.0.0
 */
abstract class ContentType implements Registerable, Supportable, HasMetaBox
{
    /**
     * Registered slug
     *
     * @since   1.0.0
     * @var   string
     */
    public string $name;

    /**
     * Singular display name
     *
     * @since   1.0.0
     * @var   string
     */
    public string $title;

    /**
     * Plural display name
     *
     * @since   1.0.0
     * @var   string
     */
    public string $plural;

    /**
     * Arguments passed to WordPress registration function
     *
     * @since   1.0.0
     * @var   array<string, mixed>
     */
    public array $args = [];

    /**
     * Custom labels override
     *
     * @since   1.0.0
     * @var   array<string, string>
     */
    public array $labels = [];

    /**
     * Builds the labels array for WordPress registration
     *
     * @since   1.0.0
     * @return array<string, string>
     */
    abstract protected function buildLabels(): array;

    /**
     * Builds the args array for WordPress registration
     *
     * @since   1.0.0
     * @return array<string, mixed>
     */
    abstract protected function buildArgs(): array;

    /**
     * Adds a meta box to the content type
     *
     * @since   1.0.0
     * @param  string         $id       Unique identifier
     * @param  string         $title    Display title
     * @param  array<mixed>   $fields   Fields to display
     * @param  string         $context  Display context: 'normal', 'side', 'advanced'
     * @param  string         $priority Display priority: 'default', 'high', 'low'
     * @return static
     */
    public function addMetaBox(string $id, string $title, array $fields = [], string $context = 'normal', string $priority = 'default'): static
    {
        new \Weblitzer\CFDev\Meta\MetaBox($id, $title, $this->name, $fields, $context, $priority);

        return $this;
    }

    /**
     * Adds support for one or more post type features
     *
     * @since   1.0.0
     * @param  string|array<string> $feature  Feature(s) to add (e.g. 'thumbnail', 'excerpt')
     * @return static
     */
    public function addSupport(string|array $feature): static
    {
        add_post_type_support($this->name, $feature);

        return $this;
    }

    /**
     * Removes support for one or more post type features
     *
     * @since   1.0.0
     * @param  string|array<string> $features  Feature(s) to remove
     * @return static
     */
    public function removeSupport(string|array $features): static
    {
        foreach ((array) $features as $feature) {
            remove_post_type_support($this->name, $feature);
        }

        return $this;
    }

    /**
     * Checks whether the content type supports a given feature
     *
     * @since   1.0.0
     * @param  string $feature  Feature to check (e.g. 'thumbnail', 'excerpt')
     * @return bool
     */
    public function supports(string $feature): bool
    {
        return post_type_supports($this->name, $feature);
    }
}
