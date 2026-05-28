<?php

/**
 * Registers a User Meta group
 *
 * @param string               $id        Unique identifier for the meta group
 * @param string               $title     Label displayed on the user profile page
 * @param array<mixed>         $fields    Field definitions
 * @param string|array<string> $locations WP hooks to attach to (default: show & edit user profile)
 * @param int                  $priority  Hook priority — controls display order
 * @return \Weblitzer\CFDev\Meta\UserMeta
 *
 * @author  quidelantoine
 * @since   1.0.0
 */
function register_cfdev_user_meta(
    string $id,
    string $title,
    array $fields = [],
    string|array $locations = [],
    int $priority = 10
): \Weblitzer\CFDev\Meta\UserMeta {
    return new \Weblitzer\CFDev\Meta\UserMeta($id, $title, $fields, $locations, $priority);
}
