<?php

namespace Weblitzer\CFDev\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Admin\FieldsPage;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class FieldsPageTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Additional WP functions used by FieldsPage::render()
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('_n')->returnArg(1);
        Functions\when('esc_attr_e')->returnArg(1);
        Functions\when('esc_html_e')->returnArg(1);
        Functions\when('get_post_type_object')->justReturn(null);
    }

    private function captureRender(): string
    {
        ob_start();
        FieldsPage::render();
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

    public function testRenderOutputsInlineScript(): void
    {
        $output = $this->captureRender();
        $this->assertStringContainsString('cfdev-registry-js', $output);
    }

    // -------------------------------------------------------------------------
    // Duplicate detection notice
    // -------------------------------------------------------------------------

    public function testRenderDoesNotShowDuplicatesNoticeWhenNoConflicts(): void
    {
        $output = $this->captureRender();
        $this->assertStringNotContainsString('cfdev-notice-dups', $output);
    }
}
