<?php

namespace Weblitzer\CFDev\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Admin\DashboardPage;
use Weblitzer\CFDev\OptionsPage;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class DashboardPageTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Additional WP functions used by DashboardPage::render()
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('_n')->returnArg(1);
        Functions\when('esc_attr_e')->returnArg(1);
        Functions\when('esc_html_e')->returnArg(1);
        Functions\when('get_post_type_object')->justReturn(null);
        Functions\when('wp_create_nonce')->justReturn('test-nonce');
        Functions\when('admin_url')->justReturn('https://example.com/wp-admin/admin-ajax.php');
        Functions\when('wp_json_encode')->alias('json_encode');
    }

    private function captureRender(): string
    {
        ob_start();
        DashboardPage::render();
        return (string) ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // Capability guard
    // -------------------------------------------------------------------------

    public function testRenderReturnsEarlyWhenUserLacksCapability(): void
    {
        Functions\when('current_user_can')->justReturn(false);

        $output = $this->captureRender();

        $this->assertSame('', $output);
    }

    // -------------------------------------------------------------------------
    // Page structure — empty registry
    // -------------------------------------------------------------------------

    public function testRenderOutputsRegistryWrapperClass(): void
    {
        $output = $this->captureRender();
        $this->assertStringContainsString('cfdev-registry', $output);
    }

    public function testRenderOutputsHeaderWithGroupCount(): void
    {
        $output = $this->captureRender();
        $this->assertStringContainsString('cfdev-header__count', $output);
    }

    public function testRenderOutputsTabNavigationBar(): void
    {
        $output = $this->captureRender();
        $this->assertStringContainsString('cfdev-tabs-nav', $output);
    }

    public function testRenderOutputsTermsAndUsersTabIds(): void
    {
        $output = $this->captureRender();
        $this->assertStringContainsString('id="cfdev-tab-terms"', $output);
        $this->assertStringContainsString('id="cfdev-tab-users"', $output);
    }

    public function testRenderOutputsEmptyStateMessageForEachPanel(): void
    {
        $output = $this->captureRender();
        // Two panels (terms + users), both empty → two cfdev-empty paragraphs
        $this->assertGreaterThanOrEqual(2, substr_count($output, 'cfdev-empty'));
    }

    public function testRenderOutputsInspectModal(): void
    {
        $output = $this->captureRender();
        $this->assertStringContainsString('cfdev-inspect-modal', $output);
    }

    // -------------------------------------------------------------------------
    // Duplicate detection notice
    // -------------------------------------------------------------------------

    public function testRenderDoesNotShowDuplicatesNoticeWhenNoConflicts(): void
    {
        $output = $this->captureRender();
        $this->assertStringNotContainsString('cfdev-notice-dups', $output);
    }

    // -------------------------------------------------------------------------
    // Options tab
    // -------------------------------------------------------------------------

    private function registerOptionsPage(string $id = 'site_settings', string $title = 'Site Settings'): void
    {
        Functions\when('add_action')->justReturn(null);
        Functions\when('sanitize_title')->alias(
            fn(string $s) => strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $s))
        );
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('wp_json_encode')->alias('json_encode');
        new OptionsPage($id, $title);
    }

    public function testRenderOutputsOptionsTabWhenOptionsPagesRegistered(): void
    {
        $this->registerOptionsPage();
        Functions\when('menu_page_url')->justReturn('https://example.com/wp-admin/admin.php?page=cfdev-site_settings');

        $output = $this->captureRender();

        $this->assertStringContainsString('id="cfdev-tab-options"', $output);
    }

    public function testRenderOptionsTabCountMatchesRegisteredPages(): void
    {
        $this->registerOptionsPage('settings_a', 'Settings A');
        $this->registerOptionsPage('settings_b', 'Settings B');
        Functions\when('menu_page_url')->justReturn('');

        $output = $this->captureRender();

        // Options tab count badge should show 2
        preg_match('/#cfdev-tab-options.*?cfdev-tab-count[^>]*>(\d+)/s', $output, $m);
        $this->assertSame('2', $m[1] ?? '');
    }

    public function testRenderOptionsEntryShowsEditButtonNotInspect(): void
    {
        $this->registerOptionsPage();
        Functions\when('menu_page_url')->justReturn('https://example.com/wp-admin/admin.php?page=cfdev-site_settings');

        $output = $this->captureRender();

        // Options entries show Edit link instead of Inspect button
        $this->assertStringNotContainsString('cfdev-btn-inspect', $output);
        $this->assertStringContainsString('button-small', $output);
    }
}
