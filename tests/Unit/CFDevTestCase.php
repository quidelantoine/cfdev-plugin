<?php

namespace Weblitzer\CFDev\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

abstract class CFDevTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Mock WP i18n functions used in getError() of every rule
        Functions\when('__')->returnArg(1);
        Functions\when('_e')->returnArg(1);
        Functions\when('esc_html')->returnArg(1);
        Functions\when('esc_html__')->returnArg(1);
        Functions\when('esc_html_e')->returnArg(1);
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_url')->returnArg();
        Functions\when('wp_kses_post')->returnArg();
        Functions\when('sanitize_title')->returnArg(1);
        Functions\when('get_posts')->justReturn([]);
        Functions\when('get_terms')->justReturn([]);
        Functions\when('get_users')->justReturn([]);
    }

    protected function tearDown(): void
    {
        \Weblitzer\CFDev\Registry::reset();
        Monkey\tearDown();
        parent::tearDown();
    }
}
