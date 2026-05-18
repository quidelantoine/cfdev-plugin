<?php

namespace CFDev\Config;

/**
 * Holds the immutable plugin configuration
 *
 * Created once in the Initializer and injected
 * into services via the Container.
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 *
 */
final class Config
{
    /**
     * @param string $version Current plugin version (e.g. '2.9.18')
     * @param string $dir     Absolute path to the plugin root directory (with trailing slash)
     * @param string $url     Public URL to the plugin root directory (with trailing slash)
     */
    public function __construct(
        public readonly string $version,
        public readonly string $dir,
        public readonly string $url,
        public readonly string $src_dir,
    ) {
    }
}
