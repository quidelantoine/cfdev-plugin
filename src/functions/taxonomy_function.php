<?php

/**
 * Registers a Taxonomy for a Post Type
 *
 * @param   string|array<string> $name
 * @param   string               $post_type
 * @param   array<mixed>         $args
 * @param   array<string>        $labels
 * @return  \CFDev\Taxonomy
 *
 * @author  quidelantoine
 * @since   1.0.0
 *
 */
function register_cfdev_taxonomy(string|array $name, string $post_type, array $args = array(), array $labels = array()): \CFDev\Taxonomy
{
    $taxonomy = new \CFDev\Taxonomy($name, $post_type, $args, $labels);

    return $taxonomy;
}

/**
 * Get term meta
 *
 * @param   int|string      $term       Can be the id or the slug of the term
 * @param   string          $taxonomy
 * @param   string|null     $key
 * @return  mixed
 *
 * @author  quidelantoine
 * @since   1.0.0
 */
function get_cfdev_term_meta(int|string $term, string $taxonomy, ?string $key = null): mixed
{
    if (empty($taxonomy) || empty($term)) {
        return false;
    }

    if (! is_numeric($term)) {
        $term = get_term_by('slug', $term, $taxonomy);
        $term = $term->term_id;
    }

    if ($key) {
        return \CFDev\Field::decodeMetaValue(get_term_meta($term, $key, true));
    }

    $raw = get_term_meta($term);
    if (empty($raw)) {
        return [];
    }

    return array_map(fn($v) => \CFDev\Field::decodeMetaValue(maybe_unserialize($v[0])), $raw);
}

/**
 * Get term meta
 *
 * @param   int|string      $term       Can be the id or the slug of the term
 * @param   string          $taxonomy
 * @param   string          $key
 *
 * @author  quidelantoine
 * @since   1.0.0
 */
function the_cfdev_term_meta(int|string $term, string $taxonomy, ?string $key = null): void
{
    if (empty($term) || empty($taxonomy)) {
        return;
    }

    echo esc_html(get_cfdev_term_meta($term, $taxonomy, $key));
}
