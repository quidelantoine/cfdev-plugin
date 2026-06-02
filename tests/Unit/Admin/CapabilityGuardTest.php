<?php

namespace Weblitzer\CFDev\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Admin\CachePage;
use Weblitzer\CFDev\Admin\DashboardPage;
use Weblitzer\CFDev\Admin\RestPage;
use Weblitzer\CFDev\Config\Ajax\AjaxHandler;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class CapabilityGuardTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('current_user_can')->justReturn(false);
    }

    // -------------------------------------------------------------------------
    // Admin pages — render() must produce no output for non-admins
    // -------------------------------------------------------------------------

    public function testDashboardPageReturnsEmptyForNonAdmin(): void
    {
        ob_start();
        DashboardPage::render();
        $output = (string) ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testCachePageReturnsEmptyForNonAdmin(): void
    {
        ob_start();
        CachePage::render();
        $output = (string) ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testRestPageReturnsEmptyForNonAdmin(): void
    {
        ob_start();
        RestPage::render();
        $output = (string) ob_get_clean();

        $this->assertSame('', $output);
    }

    // -------------------------------------------------------------------------
    // AJAX handlers — must send JSON 403 and stop for non-admins
    // -------------------------------------------------------------------------

    public function testHandleInspectSendsJson403ForNonAdmin(): void
    {
        Functions\expect('wp_send_json_error')
            ->once()
            ->with(['message' => 'Forbidden'], 403)
            ->andReturnUsing(static function (): void {
                throw new \RuntimeException('json_error');
            });

        $this->expectException(\RuntimeException::class);
        AjaxHandler::handleInspect();
    }

    public function testHandleSearchObjectsSendsJson403ForNonAdmin(): void
    {
        Functions\expect('wp_send_json_error')
            ->once()
            ->with(['message' => 'Forbidden'], 403)
            ->andReturnUsing(static function (): void {
                throw new \RuntimeException('json_error');
            });

        $this->expectException(\RuntimeException::class);
        AjaxHandler::handleSearchObjects();
    }
}
