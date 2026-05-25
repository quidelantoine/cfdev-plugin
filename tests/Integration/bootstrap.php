<?php

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', __DIR__ . '/stubs');
define('WP_TESTS_CONFIG_FILE_PATH', dirname(__DIR__, 2) . '/wp-tests-config.php');

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$_tests_dir = getenv('WP_PHPUNIT__DIR');

require_once $_tests_dir . '/includes/functions.php';

// Redirige wp_upload_dir() vers un dossier tmp writable avant le boot du plugin,
// afin que CacheStore ne tente jamais d'écrire/supprimer dans le répertoire Docker (root:root).
$_cfdev_test_uploads = sys_get_temp_dir() . '/cfdev-test-uploads';
if (! is_dir($_cfdev_test_uploads)) {
    mkdir($_cfdev_test_uploads, 0755, true); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
}
tests_add_filter('upload_dir', static function (array $dirs) use ($_cfdev_test_uploads): array {
    $dirs['basedir'] = $_cfdev_test_uploads;
    $dirs['baseurl'] = 'http://cfdev.test/uploads';
    $dirs['path']    = $_cfdev_test_uploads;
    $dirs['url']     = 'http://cfdev.test/uploads';
    return $dirs;
});

tests_add_filter('muplugins_loaded', static function (): void {
    require_once dirname(__DIR__, 2) . '/cfdev-plugin.php';
});

require_once $_tests_dir . '/includes/bootstrap.php';
