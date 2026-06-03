<?php

namespace Weblitzer\CFDev\Tests\Unit\Admin\Export;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Admin\Export\ExportHandler;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

/**
 * @covers \Weblitzer\CFDev\Admin\Export\ExportHandler
 */
class ExportHandlerTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_unslash')->returnArg(1);
        Functions\when('admin_url')->justReturn('https://example.com/wp-admin/admin-post.php');
        Functions\when('wp_nonce_field')->justReturn('');
        Functions\when('esc_html__')->returnArg(1);
        Functions\when('esc_attr')->returnArg(1);
        Functions\when('esc_url')->returnArg(1);
        if (! defined('CFDEV_VERSION')) {
            define('CFDEV_VERSION', '1.0.6');
        }
    }

    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    public function testActionConstantValue(): void
    {
        $this->assertSame('cfdev_export', ExportHandler::ACTION);
    }

    public function testNonceConstantValue(): void
    {
        $this->assertSame('cfdev_export', ExportHandler::NONCE);
    }

    // -------------------------------------------------------------------------
    // handle() — capability guard
    // -------------------------------------------------------------------------

    public function testHandleDiesWhenUserLacksCapability(): void
    {
        Functions\when('current_user_can')->justReturn(false);
        Functions\expect('wp_die')
            ->once()
            ->andReturnUsing(static function (): void {
                throw new \RuntimeException('wp_die');
            });

        $this->expectException(\RuntimeException::class);
        ExportHandler::handle();
    }

    // -------------------------------------------------------------------------
    // handle() — nonce guard
    // -------------------------------------------------------------------------

    public function testHandleDiesOnInvalidNonce(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(false);
        Functions\expect('wp_die')
            ->once()
            ->andReturnUsing(static function (): void {
                throw new \RuntimeException('wp_die');
            });

        $this->expectException(\RuntimeException::class);
        $_POST['cfdev_export_nonce'] = 'bad-nonce';
        ExportHandler::handle();
    }
}
