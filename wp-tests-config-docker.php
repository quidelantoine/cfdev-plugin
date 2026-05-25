<?php

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

define('DB_NAME', 'wordpress_test');
define('DB_USER', 'wpuser');
define('DB_PASSWORD', 'wppassword');
define('DB_HOST', 'db');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

define('ABSPATH', '/app/public/');

define('WP_TESTS_DOMAIN', 'cfdev.test');
define('WP_TESTS_EMAIL', 'admin@cfdev.test');
define('WP_TESTS_TITLE', 'CFDev Integration Tests');
define('WP_PHP_BINARY', 'php');

$table_prefix = 'wptests_'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
