<?php

namespace CFDev;

use CFDev\Abstracts\ContentType;
use CFDev\Contracts\HasTaxonomy;
use CFDev\Support\NameResolver;

/**
 * Registers and manages a Custom Post Type
 *
 * Supports method chaining for adding taxonomies,
 * meta boxes and post type features.
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 *
 * @example
 * register_cfdev_post_type(['book', 'books'], ['public' => true])
 *     ->addTaxonomy('genre')
 *     ->addSupport('thumbnail')
 *     ->addMetaBox('details', 'Book Details', $fields);
 */
class PostType extends ContentType implements HasTaxonomy
{
    /**
     * Registers a new Custom Post Type
     *
     * @since   1.0.0
     * @param  string|array<string> $name    Singular name, or [singular, plural]
     * @param  array<string,mixed>  $args    Arguments passed to register_post_type()
     * @param  array<string,string> $labels  Custom labels override
     */
    public function __construct(string|array $name, array $args = [], array $labels = [])
    {
        if (empty($name)) {
            return;
        }

        // Resolve names
        $resolved     = new NameResolver($name);
        $this->name   = $resolved->slug;
        $this->title  = $resolved->singular;
        $this->plural = $resolved->plural;

        $this->args   = $args;
        $this->labels = $labels;

        if (! post_type_exists($this->name)) {
            $this->register();
        }
    }

    /**
     * Registers the post type with WordPress
     *
     * @since   1.0.0
     * @return void
     */
    public function register(): void
    {
        register_post_type($this->name, $this->buildArgs());
    }

    /**
     * Adds a taxonomy to the post type
     *
     * @since   1.0.0
     * @param  string|array<string> $name    Taxonomy name, or [singular, plural]
     * @param  array<string,mixed>  $args    Arguments passed to register_taxonomy()
     * @param  array<string,string> $labels  Custom labels override
     * @return static
     */
    public function addTaxonomy(string|array $name, array $args = [], array $labels = []): static
    {
        new Taxonomy($name, $this->name, $args, $labels);

        return $this;
    }

    /**
     * Builds the labels array for WordPress registration
     *
     * @since   1.0.0
     * @return array<string, string>
     */
    protected function buildLabels(): array
    {
        return array_merge([
            'name'               => sprintf(_x('%s', 'post type general name', 'cfdev'), $this->plural),
            'singular_name'      => sprintf(_x('%s', 'post type singular name', 'cfdev'), $this->title),
            'menu_name'          => sprintf(__('%s', 'cfdev'), $this->plural),
            'all_items'          => sprintf(__('All %s', 'cfdev'), $this->plural),
            'add_new'            => sprintf(_x('Add New', '%s', 'cfdev'), $this->title),
            'add_new_item'       => sprintf(__('Add New %s', 'cfdev'), $this->title),
            'edit_item'          => sprintf(__('Edit %s', 'cfdev'), $this->title),
            'new_item'           => sprintf(__('New %s', 'cfdev'), $this->title),
            'view_item'          => sprintf(__('View %s', 'cfdev'), $this->title),
            'items_archive'      => sprintf(__('%s Archive', 'cfdev'), $this->title),
            'search_items'       => sprintf(__('Search %s', 'cfdev'), $this->plural),
            'not_found'          => sprintf(__('No %s found', 'cfdev'), $this->plural),
            'not_found_in_trash' => sprintf(__('No %s found in trash', 'cfdev'), $this->plural),
            'parent_item_colon'  => sprintf(__('%s Parent', 'cfdev'), $this->title),
        ], $this->labels);
    }

    /**
     * Builds the args array for WordPress registration
     *
     * @since   1.0.0
     * @return array<string, mixed>
     */
    protected function buildArgs(): array
    {
        return array_merge([
            'label'       => sprintf(__('%s', 'cfdev'), $this->plural),
            'labels'      => $this->buildLabels(),
            'public'      => true,
            'supports'    => ['title', 'editor'],
            'has_archive' => sanitize_title($this->plural),
        ], $this->args);
    }
}
