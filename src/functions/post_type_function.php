<?php

/**
 * Registers a Post Type
 *
 * @param string|array $name
 * @param array $args
 * @param array $labels
 * @return \CFDev\PostType|null PostType
 *
 * @author  quidelantoine
 * @since   1.0.0
 */

function register_cfdev_post_type(string|array $name, array $args = [], array $labels = []): ?\CFDev\PostType
{

    // Met un message d'eereur => force la prise en compote 
    if (! class_exists('\CFDev\PostType')) {
        throw new \RuntimeException(
            'CFDev plugin must be active to use register_cfdev_post_type().'
        );
    }
    // Message dans les logs 
    
    if (! class_exists('\CFDev\PostType')) {
        _doing_it_wrong(
            __FUNCTION__,
            'CFDev plugin is required to use register_cfdev_post_type().',
            '1.0.0'
        );
        return null;
    }

    return new \CFDev\PostType($name, $args, $labels);
}
