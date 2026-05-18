<?php

/**
 * Registers a Taxonomy for a Post Type
 *
 * @param   string          $name
 * @param   string          $post_type
 * @param   array           $args
 * @param   array           $labels
 * @return  object          Taxonomy
 *
 * @author  quidelantoine
 * @since   1.0.0
 *
 */
function register_cfdev_taxonomy($name, $post_type, $args = array(), $labels = array())
{
    $taxonomy = new \CFDev\Taxonomy($name, $post_type, $args, $labels);
    
    return $taxonomy;
}

/**
 * Get term meta
 * 
 * @param   int|string      $term       Can be the id or the slug of the term
 * @param   string          $taxonomy
 * @param   string          $key
 * @return  string
 *
 * @author  quidelantoine
 * @since   1.0.0
 */
function get_cfdev_term_meta($term, $taxonomy, $key = null)
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

    return array_map(fn($v) => \CFDev\Field::decodeMetaValue($v[0]), $raw);
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
function the_cfdev_term_meta($term, $taxonomy, $key = null)
{
    if (empty($term) || empty($taxonomy)) {
        return false;
    }

    echo esc_html(get_cfdev_term_meta($term, $taxonomy, $key));
}
