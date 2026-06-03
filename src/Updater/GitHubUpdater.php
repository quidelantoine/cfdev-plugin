<?php

namespace Weblitzer\CFDev\Updater;

class GitHubUpdater
{
    private const REPO         = 'quidelantoine/cfdev-plugin';
    private const TRANSIENT    = 'cfdev_github_release';
    private const CACHE_TTL    = 43200; // 12 h
    private const CACHE_FAIL   = 300;   // 5 min on error

    public function __construct(
        private readonly string $plugin_file,
        private readonly string $current_version,
    ) {
    }

    public function register(): void
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'injectUpdate']);
        add_filter('plugins_api', [$this, 'pluginInfo'], 10, 3);
    }

    /**
     * Inject update data into WP's update transient when a newer release exists on GitHub.
     */
    public function injectUpdate(\stdClass $transient): \stdClass
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $release = $this->fetchRelease();
        if ($release === null) {
            return $transient;
        }

        $remote = ltrim($release['tag_name'], 'v');
        if (version_compare($remote, $this->current_version, '>')) {
            $basename = plugin_basename($this->plugin_file);
            if (!isset($transient->response) || !is_array($transient->response)) {
                $transient->response = [];
            }
            $transient->response[$basename] = (object) [
                'slug'         => 'cfdev-plugin',
                'plugin'       => $basename,
                'new_version'  => $remote,
                'url'          => 'https://github.com/' . self::REPO,
                'package'      => $release['download_url'],
                'requires'     => '6.5',
                'requires_php' => '8.2',
                'icons'        => [],
                'banners'      => [],
            ];
        }

        return $transient;
    }

    /**
     * Return plugin information for the "View version details" popup.
     */
    public function pluginInfo(false|object $result, string $action, object $args): false|object
    {
        if ($action !== 'plugin_information' || !isset($args->slug) || $args->slug !== 'cfdev-plugin') {
            return $result;
        }

        $release = $this->fetchRelease();
        if ($release === null) {
            return $result;
        }

        return (object) [
            'name'          => 'Custom Field For Dev',
            'slug'          => 'cfdev-plugin',
            'version'       => ltrim($release['tag_name'], 'v'),
            'author'        => 'quidelantoine',
            'homepage'      => 'https://github.com/' . self::REPO,
            'requires'      => '6.5',
            'requires_php'  => '8.2',
            'last_updated'  => $release['published_at'],
            'download_link' => $release['download_url'],
            'sections'      => [
                'description' => 'Code-first API for custom meta fields. 30+ types, bundles, tabs, validation, REST API and file cache.',
                'changelog'   => $release['body'],
            ],
        ];
    }

    /**
     * @return array{tag_name: string, published_at: string, body: string, download_url: string}|null
     */
    private function fetchRelease(): ?array
    {
        $cached = get_transient(self::TRANSIENT);
        if ($cached !== false) {
            return $cached === [] ? null : $cached;
        }

        $response = wp_remote_get( // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
            'https://api.github.com/repos/' . self::REPO . '/releases/latest',
            [
                'headers' => [
                    'Accept'     => 'application/vnd.github+json',
                    'User-Agent' => 'CFDev-Updater/' . $this->current_version,
                ],
                'timeout' => 3, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
            ]
        );

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            set_transient(self::TRANSIENT, [], self::CACHE_FAIL);
            return null;
        }

        /** @var array<string, mixed> $body */
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['tag_name']) || !is_string($body['tag_name'])) {
            set_transient(self::TRANSIENT, [], self::CACHE_FAIL);
            return null;
        }

        $version      = ltrim($body['tag_name'], 'v');
        $download_url = 'https://github.com/' . self::REPO . '/releases/download/' . $body['tag_name'] . '/cfdev-plugin-' . $version . '.zip';

        if (!empty($body['assets']) && is_array($body['assets'])) {
            foreach ($body['assets'] as $asset) {
                if (is_array($asset) && isset($asset['name'], $asset['browser_download_url']) && str_ends_with((string) $asset['name'], '.zip')) {
                    $download_url = (string) $asset['browser_download_url'];
                    break;
                }
            }
        }

        $data = [
            'tag_name'     => $body['tag_name'],
            'published_at' => isset($body['published_at']) && is_string($body['published_at']) ? $body['published_at'] : '',
            'body'         => isset($body['body']) && is_string($body['body']) ? $body['body'] : '',
            'download_url' => $download_url,
        ];

        set_transient(self::TRANSIENT, $data, self::CACHE_TTL);
        return $data;
    }
}
