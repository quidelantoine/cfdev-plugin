<?php

namespace Weblitzer\CFDev\Tests\Integration\Admin;

use Weblitzer\CFDev\Admin\CachePage;
use Weblitzer\CFDev\Admin\DashboardPage;
use Weblitzer\CFDev\Admin\RestPage;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie que les pages admin CFDev refusent l'accès aux utilisateurs
 * sans la capacité manage_options (éditeurs, auteurs, etc.).
 */
class CapabilityGuardTest extends IntegrationTestCase
{
    private int $editor_id;
    private int $author_id;

    public function setUp(): void
    {
        parent::setUp();
        $this->editor_id = static::factory()->user->create(['role' => 'editor']);
        $this->author_id = static::factory()->user->create(['role' => 'author']);
    }

    public function tearDown(): void
    {
        wp_set_current_user(0);
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // DashboardPage
    // -------------------------------------------------------------------------

    public function testDashboardPageReturnsEmptyForEditor(): void
    {
        wp_set_current_user($this->editor_id);

        ob_start();
        DashboardPage::render();
        $output = (string) ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testDashboardPageReturnsEmptyForAuthor(): void
    {
        wp_set_current_user($this->author_id);

        ob_start();
        DashboardPage::render();
        $output = (string) ob_get_clean();

        $this->assertSame('', $output);
    }

    // -------------------------------------------------------------------------
    // CachePage
    // -------------------------------------------------------------------------

    public function testCachePageReturnsEmptyForEditor(): void
    {
        wp_set_current_user($this->editor_id);

        ob_start();
        CachePage::render();
        $output = (string) ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testCachePageReturnsEmptyForAuthor(): void
    {
        wp_set_current_user($this->author_id);

        ob_start();
        CachePage::render();
        $output = (string) ob_get_clean();

        $this->assertSame('', $output);
    }

    // -------------------------------------------------------------------------
    // RestPage
    // -------------------------------------------------------------------------

    public function testRestPageReturnsEmptyForEditor(): void
    {
        wp_set_current_user($this->editor_id);

        ob_start();
        RestPage::render();
        $output = (string) ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testRestPageReturnsEmptyForAuthor(): void
    {
        wp_set_current_user($this->author_id);

        ob_start();
        RestPage::render();
        $output = (string) ob_get_clean();

        $this->assertSame('', $output);
    }
}
