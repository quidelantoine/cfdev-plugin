<?php

namespace Weblitzer\CFDev\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Admin\DashboardPage;
use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Meta\UserMeta;
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

    // =========================================================================
    // Helpers for MetaBox registration
    // =========================================================================

    /**
     * @param array<mixed>        $data
     * @param string|array<string> $pt
     */
    private function makeMetaBox(string $id, string|array $pt, array $data, string $title = ''): MetaBox
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('register_meta')->justReturn(true);
        return new MetaBox($id, $title ?: ucfirst($id), $pt, $data);
    }

    /** @return array<string, mixed> */
    private function f(string $id, string $type = 'text', bool $required = false): array
    {
        return ['type' => $type, 'id' => $id, 'name' => ucfirst($id), 'required' => $required];
    }

    // =========================================================================
    // Group header — title, ID, count, buttons
    // =========================================================================

    public function testRenderGroupShowsGroupTitle(): void
    {
        $this->makeMetaBox('details', 'post', [$this->f('_title')], 'Book Details');

        $output = $this->captureRender();

        $this->assertStringContainsString('Book Details', $output);
    }

    public function testRenderGroupShowsGroupId(): void
    {
        $this->makeMetaBox('cfdev_book', 'post', [$this->f('_pages')]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev_book', $output);
    }

    public function testRenderGroupShowsFieldCountBadge(): void
    {
        $this->makeMetaBox('box', 'post', [$this->f('_f1'), $this->f('_f2')]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-field-count', $output);
        $this->assertMatchesRegularExpression('/cfdev-field-count[^>]*>\s*2/', $output);
    }

    public function testRenderGroupShowsInspectButton(): void
    {
        $this->makeMetaBox('box', 'post', [$this->f('_f1')]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-btn-inspect', $output);
    }

    public function testRenderGroupShowsCodeButton(): void
    {
        $this->makeMetaBox('box', 'post', [$this->f('_f1')]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-btn-code', $output);
    }

    // =========================================================================
    // Layout badges
    // =========================================================================

    public function testRenderShowsFlatLayoutBadge(): void
    {
        $this->makeMetaBox('box', 'post', [$this->f('_f1')]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-badge--flat', $output);
    }

    public function testRenderShowsTabsLayoutBadge(): void
    {
        $this->makeMetaBox('box', 'post', [
            'tabs',
            ['Tab A' => [$this->f('_f1')], 'Tab B' => [$this->f('_f2')]],
        ]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-badge--tabs', $output);
    }

    public function testRenderShowsAccordionLayoutBadge(): void
    {
        $this->makeMetaBox('box', 'post', [
            'accordion',
            ['Section A' => [$this->f('_f1')]],
        ]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-badge--accordion', $output);
    }

    public function testRenderShowsBundleBadgeAlongsideTabsWhenGroupHasBundles(): void
    {
        $this->makeMetaBox('box', 'post', [
            'tabs',
            [
                'Info'  => [$this->f('_name')],
                'Items' => [['bundle', [$this->f('_item')]]],
            ],
        ]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-badge--tabs', $output);
        $this->assertStringContainsString('cfdev-badge--bundle', $output);
    }

    // =========================================================================
    // Condition badges
    // =========================================================================

    public function testRenderShowsConditionBadgeForPostId(): void
    {
        $mb = $this->makeMetaBox('box', 'post', [$this->f('_f1')]);
        $mb->onlyForId(42);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-condition-badge', $output);
        $this->assertStringContainsString('ID : 42', $output);
    }

    public function testRenderShowsConditionBadgeForTemplate(): void
    {
        $mb = $this->makeMetaBox('box', 'post', [$this->f('_f1')]);
        $mb->onlyForTemplate('templates/home.php');

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-condition-badge', $output);
        $this->assertStringContainsString('Template : home.php', $output);
    }

    public function testRenderShowsConditionBadgeForRoles(): void
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('register_meta')->justReturn(true);
        $um = new UserMeta('profile_extra', 'Profile', [$this->f('_bio')]);
        $um->onlyForRole('editor');

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-condition-badge', $output);
        $this->assertStringContainsString('Role: editor', $output);
    }

    public function testRenderShowsConditionBadgeForParentId(): void
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('register_meta')->justReturn(true);
        $tm = new TermMeta('genre', 'Genre', [$this->f('_desc')]);
        $tm->onlyIfParent(5);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-condition-badge', $output);
        $this->assertStringContainsString('Parent : 5', $output);
    }

    // =========================================================================
    // Field table (group body)
    // =========================================================================

    public function testRenderGroupBodyContainsFieldsTable(): void
    {
        $this->makeMetaBox('box', 'post', [$this->f('_subtitle')]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-fields-table', $output);
    }

    public function testRenderFieldsTableContainsFieldId(): void
    {
        $this->makeMetaBox('box', 'post', [$this->f('_isbn')]);

        $output = $this->captureRender();

        $this->assertStringContainsString('_isbn', $output);
    }

    public function testRenderFieldsTableContainsTypeBadge(): void
    {
        $this->makeMetaBox('box', 'post', [$this->f('_count', 'number')]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-type--number', $output);
    }

    public function testRenderFieldsTableShowsRequiredBadge(): void
    {
        $this->makeMetaBox('box', 'post', [$this->f('_title', 'text', true)]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-rule-badge--required', $output);
    }

    public function testRenderFieldsTableMarksDuplicateFieldWithDupClass(): void
    {
        // Same field ID in two different MetaBoxes → cross-box duplicate
        $this->makeMetaBox('box_a', 'post', [$this->f('_shared')]);
        $this->makeMetaBox('box_b', 'post', [$this->f('_shared')]);

        $output = $this->captureRender();

        $this->assertStringContainsString('class="cfdev-dup"', $output);
    }

    // =========================================================================
    // Sections — tabs layout
    // =========================================================================

    public function testRenderTabsGroupHasSectionDivForEachTab(): void
    {
        $this->makeMetaBox('box', 'post', [
            'tabs',
            [
                'General' => [$this->f('_name')],
                'Details' => [$this->f('_desc')],
            ],
        ]);

        $output = $this->captureRender();

        $this->assertGreaterThanOrEqual(2, substr_count($output, 'cfdev-section'));
    }

    public function testRenderTabsSectionTitleAppearsInOutput(): void
    {
        $this->makeMetaBox('box', 'post', [
            'tabs',
            ['My Tab Section' => [$this->f('_f1')]],
        ]);

        $output = $this->captureRender();

        $this->assertStringContainsString('My Tab Section', $output);
    }

    // =========================================================================
    // Sections — accordion layout
    // =========================================================================

    public function testRenderAccordionGroupHasAccordionSectionClass(): void
    {
        $this->makeMetaBox('box', 'post', [
            'accordion',
            ['Pricing' => [$this->f('_price')]],
        ]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-section--accordion', $output);
    }

    // =========================================================================
    // Bundle section ref (tabs/accordion with a bundle tab)
    // =========================================================================

    public function testRenderSectionWithBundleShowsBundleRef(): void
    {
        $this->makeMetaBox('box', 'post', [
            'accordion',
            [
                'Info'  => [$this->f('_name')],
                'Items' => [['bundle', [$this->f('_item')]]],
            ],
        ]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-section-bundle-ref', $output);
    }

    // =========================================================================
    // Standalone bundle layout
    // =========================================================================

    public function testRenderBundleLayoutGroupShowsBundleDiv(): void
    {
        $this->makeMetaBox('box', 'post', [
            'bundle',
            '_rows',
            [$this->f('_qty'), $this->f('_price')],
        ]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-bundle', $output);
        $this->assertStringContainsString('_rows', $output);
    }

    // =========================================================================
    // Warning notices
    // =========================================================================

    public function testRenderShowsDuplicateBoxIdBadgeWhenSameIdRegisteredTwice(): void
    {
        $this->makeMetaBox('same_id', 'post', [$this->f('_f1')]);
        $this->makeMetaBox('same_id', 'post', [$this->f('_f2')]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-dup-badge--box', $output);
    }

    public function testRenderShowsIntraBoxDuplicateNoticeWhenSameFieldInTwoTabs(): void
    {
        $this->makeMetaBox('box', 'post', [
            'tabs',
            [
                'Tab A' => [$this->f('_dupe')],
                'Tab B' => [$this->f('_dupe')],
            ],
        ]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-notice-dups', $output);
    }

    public function testRenderShowsReservedKeyNoticeWhenFieldUsesWpInternalMetaKey(): void
    {
        $this->makeMetaBox('box', 'post', [
            ['type' => 'image', 'id' => '_thumbnail_id', 'name' => 'Image'],
        ]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-notice-dups', $output);
        $this->assertStringContainsString('_thumbnail_id', $output);
    }

    // =========================================================================
    // "Also in" tag for multi-post-type groups
    // =========================================================================

    public function testRenderShowsAlsoInTagWhenGroupAssignedToMultiplePostTypes(): void
    {
        $this->makeMetaBox('box', ['post', 'page'], [$this->f('_f1')]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-also-in', $output);
    }

    // =========================================================================
    // Tab count per post type
    // =========================================================================

    public function testRenderTabCountBadgeMatchesGroupCountForPostType(): void
    {
        $this->makeMetaBox('box_a', 'post', [$this->f('_f1')]);
        $this->makeMetaBox('box_b', 'post', [$this->f('_f2')]);

        $output = $this->captureRender();

        // Tab link for 'post' should have count badge showing 2
        preg_match('/#cfdev-tab-pt-post[^>]*>.*?cfdev-tab-count[^>]*>(\d+)/s', $output, $m);
        $this->assertSame('2', $m[1] ?? '');
    }
}
