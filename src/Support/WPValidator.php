<?php

namespace CFDev\Support;

/**
 * WordPress-specific validation helpers
 *
 * @package CFDev\Support
 * @author  quidelantoine
 * @since   1.0.0
 */
final class WPValidator
{
    private const RESERVED_TERMS = [
        'attachment', 'attachment_id', 'author', 'author_name', 'calendar', 'cat',
        'category', 'category__and', 'category__in', 'category__not_in', 'category_name',
        'comments_per_page', 'comments_popup', 'cpage', 'day', 'debug', 'error', 'exact',
        'feed', 'hour', 'link_category', 'm', 'minute', 'monthnum', 'more', 'name',
        'nav_menu', 'nopaging', 'offset', 'order', 'orderby', 'p', 'page', 'page_id',
        'paged', 'pagename', 'pb', 'perm', 'post', 'post__in', 'post__not_in',
        'post_format', 'post_mime_type', 'post_status', 'post_tag', 'post_type', 'posts',
        'posts_per_archive_page', 'posts_per_page', 'preview', 'robots', 's', 'search',
        'second', 'sentence', 'showposts', 'static', 'subpost', 'subpost_id', 'tag',
        'tag__and', 'tag__in', 'tag__not_in', 'tag_id', 'tag_slug__and', 'tag_slug__in',
        'taxonomy', 'tb', 'term', 'type', 'w', 'withcomments', 'withoutcomments', 'year',
    ];

    /**
     * @since   1.0.0
     */
    public static function isReservedTerm(string $term): \WP_Error|false
    {
        if (!in_array($term, self::RESERVED_TERMS, true)) {
            return false;
        }

        return new \WP_Error('reserved_term_used', __('Use of a reserved term.', 'cfdev'));
    }

    /**
     * @since   1.0.0
     */
    /** @param string|array<mixed> $callback */
    public static function isWpCallback(string|array $callback): bool
    {
        if (!is_array($callback)) {
            return true;
        }

        [$class, $method] = [$callback[0] ?? null, $callback[1] ?? null];

        return is_string($method)
            ? method_exists($class, $method)
            : (is_string($class) && class_exists($class));
    }
}
