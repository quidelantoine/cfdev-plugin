<?php

namespace Weblitzer\CFDev\Tests\Integration;

use WP_UnitTestCase;

abstract class IntegrationTestCase extends WP_UnitTestCase
{
    private static string $tmp_uploads;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$tmp_uploads = sys_get_temp_dir() . '/cfdev-test-uploads-' . uniqid();
        mkdir(self::$tmp_uploads, 0755, true); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
    }

    public static function tearDownAfterClass(): void
    {
        // Nettoyage des fichiers tmp créés pendant les tests
        foreach (glob(self::$tmp_uploads . '/**/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
            }
        }
        foreach (glob(self::$tmp_uploads . '/*') ?: [] as $dir) {
            if (is_dir($dir)) {
                rmdir($dir); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_rmdir
            }
        }
        rmdir(self::$tmp_uploads); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_rmdir
        parent::tearDownAfterClass();
    }

    /**
     * Suppress warnings from WP Core's _doing_it_wrong() calls triggered during
     * init re-fires or internal WP operations — not relevant for plugin integration tests.
     */
    public function expectedDeprecated(): void
    {
        $this->caught_deprecated      = [];
        $this->caught_doing_it_wrong  = [];
    }

    /**
     * PHPUnit 13 removed Util\Test::parseTestMethodAnnotations() used by WP's expectDeprecated().
     * We skip annotation parsing (unused in our tests) and keep the WP hook setup only.
     */
    public function expectDeprecated(): void
    {
        add_action('deprecated_function_run', [$this, 'deprecated_function_run'], 10, 3);
        add_action('deprecated_argument_run', [$this, 'deprecated_function_run'], 10, 3);
        add_action('deprecated_class_run', [$this, 'deprecated_function_run'], 10, 3);
        add_action('deprecated_file_included', static function (string $file, string $message, string $replacement, string $version): void {
        }, 10, 4);
        add_action('deprecated_hook_run', static function (string $hook, string $replacement, string $version, string $message): void {
        }, 10, 4);
        add_action('doing_it_wrong_run', [$this, 'doing_it_wrong_run'], 10, 3);

        add_filter('deprecated_function_trigger_error', '__return_false');
        add_filter('deprecated_argument_trigger_error', '__return_false');
        add_filter('deprecated_class_trigger_error', '__return_false');
        add_filter('deprecated_file_trigger_error', '__return_false');
        add_filter('deprecated_hook_trigger_error', '__return_false');
        add_filter('doing_it_wrong_trigger_error', '__return_false');
    }
}
