<?php

namespace Weblitzer\CFDev\Cache;

/**
 * Low-level file I/O for CFDev cache.
 * All files are stored as JSON in wp-content/uploads/cfdev-cache/.
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 */
final class CacheStore
{
    private string $dir;

    public function __construct()
    {
        $upload    = wp_upload_dir();
        $this->dir = trailingslashit($upload['basedir']) . 'cfdev-cache/';

        if (! is_dir($this->dir)) {
            wp_mkdir_p($this->dir);
        }

        if (! file_exists($this->dir . '.htaccess')) {
            $this->writeHtaccess();
        }

        if (! file_exists($this->dir . 'index.php')) {
            // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
            file_put_contents($this->dir . 'index.php', '<?php // Silence is golden.');
        }
    }

    private function writeHtaccess(): void
    {
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
        file_put_contents(
            $this->dir . '.htaccess',
            "# CFDev cache — deny direct HTTP access\n" .
            "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n" .
            "<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n"
        );
    }

    /** @param array<string, mixed> $data */
    public function write(string $key, array $data): void
    {
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
        file_put_contents($this->path($key), wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /** @return array<string, mixed>|null */
    public function read(string $key): ?array
    {
        $path = $this->path($key);
        if (! file_exists($path)) {
            return null;
        }
        // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
        $raw = file_get_contents($path);

        return ($raw !== false) ? (json_decode($raw, true) ?: null) : null;
    }

    public function exists(string $key): bool
    {
        return file_exists($this->path($key));
    }

    /** Seconds since the file was last written. */
    public function age(string $key): int
    {
        $path = $this->path($key);
        return file_exists($path) ? (int) (time() - filemtime($path)) : PHP_INT_MAX;
    }

    public function delete(string $key): void
    {
        $path = $this->path($key);
        if (file_exists($path)) {
            // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
            unlink($path);
        }
    }

    public function deleteAll(): int
    {
        $files = $this->listFiles();
        foreach ($files as $file) {
            // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
            unlink($file);
        }
        return count($files);
    }

    /**
     * @return array<int, array{key: string, path: string, size: int, age: int, modified: int}>
     */
    public function listAll(): array
    {
        $result = [];
        foreach ($this->listFiles() as $file) {
            $key      = basename($file, '.tmp');
            $modified = (int) filemtime($file);
            $result[] = [
                'key'      => $key,
                'path'     => $file,
                'size'     => (int) filesize($file),
                'age'      => (int) (time() - $modified),
                'modified' => $modified,
            ];
        }
        usort($result, fn($a, $b) => $b['modified'] - $a['modified']);
        return $result;
    }

    public function dir(): string
    {
        return $this->dir;
    }

    private function path(string $key): string
    {
        return $this->dir . sanitize_file_name($key) . '.tmp';
    }

    /** @return array<int, string> */
    private function listFiles(): array
    {
        return glob($this->dir . '*.tmp') ?: [];
    }
}
