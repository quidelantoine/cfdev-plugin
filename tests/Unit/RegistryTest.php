<?php

namespace Weblitzer\CFDev\Tests\Unit;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Meta\UserMeta;
use Weblitzer\CFDev\Registry;

class RegistryTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Registry::reset();

        Functions\when('sanitize_title')->alias(
            fn(string $s) => strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $s))
        );
        // apply_filters passes the value through unchanged in tests
        Functions\when('apply_filters')->returnArg(2);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<mixed> $fields */
    private function makeMetaBox(string $id = 'my_box', string $post_type = 'post', array $fields = []): MetaBox
    {
        return new MetaBox($id, 'My Box', $post_type, $fields);
    }

    /** @param array<mixed> $fields */
    private function makeUserMeta(string $id = 'user_section', array $fields = []): UserMeta
    {
        return new UserMeta($id, 'User Section', $fields);
    }

    /** @param array<mixed> $fields */
    private function makeTermMeta(string $taxonomy = 'genre', array $fields = []): TermMeta
    {
        return new TermMeta($taxonomy, '', $fields);
    }

    // Explicit 'id' bypasses buildId() so the key is always predictable in tests
    /** @return array<string, mixed> */
    private function fieldDef(string $id): array
    {
        return ['type' => 'text', 'id' => $id, 'name' => $id, 'label' => ucfirst($id)];
    }

    // -------------------------------------------------------------------------
    // Registration — meta types and targets
    // -------------------------------------------------------------------------

    public function testMetaBoxRegistersWithCorrectMetaType(): void
    {
        $this->makeMetaBox();

        $this->assertSame('post', Registry::all()[0]['meta_type']);
    }

    public function testMetaBoxRegistersWithCorrectTargets(): void
    {
        $this->makeMetaBox('box', 'page');

        $this->assertSame(['page'], Registry::all()[0]['targets']);
    }

    public function testMetaBoxRegistersMultiplePostTypes(): void
    {
        new MetaBox('box', 'Box', ['post', 'book'], []);

        $this->assertSame(['post', 'book'], Registry::all()[0]['targets']);
    }

    public function testUserMetaRegistersWithCorrectMetaType(): void
    {
        $this->makeUserMeta();

        $this->assertSame('user', Registry::all()[0]['meta_type']);
    }

    public function testUserMetaRegistersWithDefaultLocations(): void
    {
        $this->makeUserMeta();

        $entry = Registry::all()[0];
        $this->assertContains('show_user_profile', $entry['targets']);
        $this->assertContains('edit_user_profile', $entry['targets']);
    }

    public function testTermMetaRegistersWithCorrectMetaType(): void
    {
        $this->makeTermMeta();

        $this->assertSame('term', Registry::all()[0]['meta_type']);
    }

    public function testTermMetaRegistersWithCorrectTaxonomy(): void
    {
        $this->makeTermMeta('category');

        $this->assertSame(['category'], Registry::all()[0]['targets']);
    }

    // -------------------------------------------------------------------------
    // Registration — basic fields
    // -------------------------------------------------------------------------

    public function testRegistersFieldListFromMetaBox(): void
    {
        $this->makeMetaBox('box', 'post', [$this->fieldDef('subtitle')]);

        $fields = Registry::all()[0]['fields'];
        $this->assertArrayHasKey('subtitle', $fields, 'Field key "subtitle" should exist — explicit id bypasses buildId()');
        $this->assertSame('text', $fields['subtitle']['type']);
        $this->assertSame('Subtitle', $fields['subtitle']['label']);
    }

    public function testRequiredFlagIsCaptured(): void
    {
        $this->makeMetaBox('box', 'post', [
            ['type' => 'text', 'id' => 'title', 'name' => 'title', 'label' => 'Title', 'required' => true],
        ]);

        $fields = Registry::all()[0]['fields'];
        $this->assertTrue($fields['title']['required']);
    }

    public function testMetaBoxWithNoFieldsHasEmptyFieldList(): void
    {
        $this->makeMetaBox();

        $this->assertSame([], Registry::all()[0]['fields']);
    }

    // -------------------------------------------------------------------------
    // Registration — layout detection
    // -------------------------------------------------------------------------

    public function testFlatLayoutIsDetected(): void
    {
        $this->makeMetaBox('box', 'post', [$this->fieldDef('title')]);

        $this->assertSame('flat', Registry::all()[0]['layout']);
    }

    // -------------------------------------------------------------------------
    // Registration — title fallback
    // -------------------------------------------------------------------------

    public function testTermMetaUsesIdAsTitleFallback(): void
    {
        $this->makeTermMeta('genre');

        $entry = Registry::all()[0];
        $this->assertSame('genre', $entry['title']);
    }

    // -------------------------------------------------------------------------
    // Conditions — MetaBox
    // -------------------------------------------------------------------------

    public function testOnlyForIdIsReflectedInRegistry(): void
    {
        $mb = $this->makeMetaBox();
        $mb->onlyForId(42);

        $conditions = Registry::all()[0]['conditions'];
        $this->assertSame(42, $conditions['post_id']);
    }

    public function testOnlyForTemplateIsReflectedInRegistry(): void
    {
        $mb = $this->makeMetaBox();
        $mb->onlyForTemplate('template-home.php');

        $conditions = Registry::all()[0]['conditions'];
        $this->assertSame('template-home.php', $conditions['template']);
    }

    public function testNoConditionsProducesEmptyArray(): void
    {
        $this->makeMetaBox();

        $this->assertSame([], Registry::all()[0]['conditions']);
    }

    // -------------------------------------------------------------------------
    // Conditions — UserMeta
    // -------------------------------------------------------------------------

    public function testOnlyForRoleIsReflectedInRegistry(): void
    {
        $um = $this->makeUserMeta();
        $um->onlyForRole('administrator');

        $conditions = Registry::all()[0]['conditions'];
        $this->assertSame(['administrator'], $conditions['roles']);
    }

    public function testOnlyForRoleAcceptsMultipleRoles(): void
    {
        $um = $this->makeUserMeta();
        $um->onlyForRole(['editor', 'author']);

        $conditions = Registry::all()[0]['conditions'];
        $this->assertSame(['editor', 'author'], $conditions['roles']);
    }

    // -------------------------------------------------------------------------
    // Conditions — TermMeta
    // -------------------------------------------------------------------------

    public function testOnlyIfParentIsReflectedInRegistry(): void
    {
        $tm = $this->makeTermMeta();
        $tm->onlyIfParent(5);

        $conditions = Registry::all()[0]['conditions'];
        $this->assertSame(5, $conditions['parent_id']);
    }

    // -------------------------------------------------------------------------
    // all() — accumulation
    // -------------------------------------------------------------------------

    public function testAllAccumulatesMultipleEntries(): void
    {
        $this->makeMetaBox('box_a', 'post');
        $this->makeMetaBox('box_b', 'page');

        $this->assertCount(2, Registry::all());
    }

    public function testAllReturnsEntriesInRegistrationOrder(): void
    {
        $this->makeMetaBox('first', 'post');
        $this->makeMetaBox('second', 'page');

        $ids = array_column(Registry::all(), 'id');
        $this->assertSame(['first', 'second'], $ids);
    }

    // -------------------------------------------------------------------------
    // intraBoxDuplicates()
    // -------------------------------------------------------------------------

    public function testIntraBoxDuplicatesDetectsFieldDeclaredTwiceInSameBox(): void
    {
        $this->makeMetaBox('box', 'post', [
            $this->fieldDef('price'),
            $this->fieldDef('price'), // duplicate
        ]);

        $warns = Registry::intraBoxDuplicates();
        $this->assertNotEmpty($warns);
        $this->assertSame('price', $warns[0]['field']);
        $this->assertSame('box',   $warns[0]['meta_box']);
    }

    public function testIntraBoxDuplicatesReturnsEmptyWhenAllFieldIdsUnique(): void
    {
        $this->makeMetaBox('box', 'post', [
            $this->fieldDef('price'),
            $this->fieldDef('stock'),
        ]);

        $this->assertSame([], Registry::intraBoxDuplicates());
    }

    public function testIntraBoxDuplicatesDoesNotFlagSameIdInDifferentBoxes(): void
    {
        $this->makeMetaBox('box_a', 'post', [$this->fieldDef('price')]);
        $this->makeMetaBox('box_b', 'post', [$this->fieldDef('price')]);

        // That's a cross-box duplicate (handled by duplicates()), not an intra-box one
        $this->assertSame([], Registry::intraBoxDuplicates());
    }

    public function testIntraBoxDuplicatesDetectsFieldDeclaredInTwoTabs(): void
    {
        // Same ID in two different tabs of the same meta box
        new MetaBox('box', 'Tabs Box', 'post', [
            'tabs',
            [
                'Tab A' => [$this->fieldDef('weight')],
                'Tab B' => [$this->fieldDef('weight')], // duplicate across tabs
            ],
        ]);

        $warns = Registry::intraBoxDuplicates();
        $this->assertNotEmpty($warns);
        $fields = array_column($warns, 'field');
        $this->assertContains('weight', $fields);
        $this->assertSame('box', $warns[0]['meta_box']);
    }

    public function testIntraBoxDuplicatesContextIsTabTitle(): void
    {
        new MetaBox('box', 'Tabs Box', 'post', [
            'tabs',
            [
                'Pricing' => [$this->fieldDef('price')],
                'Details' => [$this->fieldDef('price')], // duplicate
            ],
        ]);

        $warns = Registry::intraBoxDuplicates();
        // The context should name the tab where the second (duplicate) occurrence was found
        $this->assertSame('Details', $warns[0]['context']);
    }

    public function testIntraBoxDuplicatesDetectsDuplicateInBundleFields(): void
    {
        new MetaBox('box', 'Box', 'post', [
            'bundle',
            '_rows',
            [
                $this->fieldDef('title'),
                $this->fieldDef('title'), // duplicate inside bundle
            ],
        ]);

        $warns = Registry::intraBoxDuplicates();
        $this->assertNotEmpty($warns);
        $this->assertSame('title', $warns[0]['field']);
        $this->assertStringContainsString('_rows', $warns[0]['context']);
    }

    public function testIntraBoxDuplicatesContextIsFlatForTopLevelFields(): void
    {
        $this->makeMetaBox('box', 'post', [
            $this->fieldDef('price'),
            $this->fieldDef('price'),
        ]);

        $this->assertSame('flat', Registry::intraBoxDuplicates()[0]['context']);
    }

    // -------------------------------------------------------------------------
    // duplicateBundleIds()
    // -------------------------------------------------------------------------

    public function testDuplicateBundleIdsDetectsClashOnSamePostType(): void
    {
        new MetaBox('box_a', 'Box A', 'post', ['bundle', '_slides', [$this->fieldDef('title')]]);
        new MetaBox('box_b', 'Box B', 'post', ['bundle', '_slides', [$this->fieldDef('caption')]]);

        $dups = Registry::duplicateBundleIds();
        $this->assertArrayHasKey('_slides', $dups);
        $this->assertContains('post', $dups['_slides']);
    }

    public function testDuplicateBundleIdsIgnoresDifferentPostTypes(): void
    {
        new MetaBox('box_a', 'Box A', 'post', ['bundle', '_slides', [$this->fieldDef('title')]]);
        new MetaBox('box_b', 'Box B', 'page', ['bundle', '_slides', [$this->fieldDef('title')]]);

        $this->assertArrayNotHasKey('_slides', Registry::duplicateBundleIds());
    }

    public function testDuplicateBundleIdsReturnsEmptyWhenNoDuplicates(): void
    {
        new MetaBox('box_a', 'Box A', 'post', ['bundle', '_slides',  [$this->fieldDef('title')]]);
        new MetaBox('box_b', 'Box B', 'post', ['bundle', '_members', [$this->fieldDef('name')]]);

        $this->assertSame([], Registry::duplicateBundleIds());
    }

    public function testDuplicateBundleIdsDetectsClashInsideTabs(): void
    {
        // Bundle inside a tab of box_a
        new MetaBox('box_a', 'Box A', 'post', [
            'tabs',
            [
                'Sessions' => [['bundle', '_sessions', [$this->fieldDef('date')]]],
            ],
        ]);
        // Same bundle ID in a flat bundle on box_b
        new MetaBox('box_b', 'Box B', 'post', ['bundle', '_sessions', [$this->fieldDef('date')]]);

        $dups = Registry::duplicateBundleIds();
        $this->assertArrayHasKey('_sessions', $dups);
        $this->assertContains('post', $dups['_sessions']);
    }

    public function testDuplicateBundleIdsDetectsClashInsideAccordion(): void
    {
        new MetaBox('box_a', 'Box A', 'post', [
            'accordion',
            [
                'Galerie' => [['bundle', '_gallery', [$this->fieldDef('image')]]],
            ],
        ]);
        new MetaBox('box_b', 'Box B', 'post', [
            'accordion',
            [
                'Photos' => [['bundle', '_gallery', [$this->fieldDef('image')]]],
            ],
        ]);

        $dups = Registry::duplicateBundleIds();
        $this->assertArrayHasKey('_gallery', $dups);
        $this->assertContains('post', $dups['_gallery']);
    }

    // -------------------------------------------------------------------------
    // reservedFieldIds()
    // -------------------------------------------------------------------------

    public function testReservedFieldIdsDetectsWpThumbnailId(): void
    {
        $this->makeMetaBox('box', 'post', [
            ['type' => 'image', 'id' => '_thumbnail_id', 'label' => 'Featured'],
        ]);

        $reserved = Registry::reservedFieldIds();
        $this->assertArrayHasKey('_thumbnail_id', $reserved);
        $this->assertContains('box', $reserved['_thumbnail_id']);
    }

    public function testReservedFieldIdsReturnsEmptyForSafeIds(): void
    {
        $this->makeMetaBox('box', 'post', [
            $this->fieldDef('cover_image'),
            $this->fieldDef('price'),
        ]);

        $this->assertSame([], Registry::reservedFieldIds());
    }

    public function testReservedFieldIdsDetectsMultipleReservedKeysAcrossBoxes(): void
    {
        $this->makeMetaBox('box_a', 'post', [
            ['type' => 'image', 'id' => '_thumbnail_id',    'label' => 'Thumb'],
        ]);
        $this->makeMetaBox('box_b', 'post', [
            ['type' => 'text',  'id' => '_wp_page_template', 'label' => 'Tpl'],
        ]);

        $reserved = Registry::reservedFieldIds();
        $this->assertArrayHasKey('_thumbnail_id',     $reserved);
        $this->assertArrayHasKey('_wp_page_template', $reserved);
    }

    public function testReservedFieldIdsDetectsUserReservedKey(): void
    {
        new UserMeta('profile', 'Profile', [
            ['type' => 'text', 'id' => 'session_tokens', 'label' => 'Sessions'],
        ]);

        $reserved = Registry::reservedFieldIds();
        $this->assertArrayHasKey('session_tokens', $reserved);
        $this->assertContains('profile', $reserved['session_tokens']);
    }

    public function testReservedFieldIdsDetectsEditLockKey(): void
    {
        $this->makeMetaBox('box', 'post', [
            ['type' => 'text', 'id' => '_edit_lock', 'label' => 'Lock'],
        ]);

        $this->assertArrayHasKey('_edit_lock', Registry::reservedFieldIds());
    }

    public function testReservedFieldIdsReportsAllBoxesUsingTheSameReservedKey(): void
    {
        $this->makeMetaBox('box_a', 'post', [
            ['type' => 'image', 'id' => '_thumbnail_id', 'label' => 'A'],
        ]);
        $this->makeMetaBox('box_b', 'page', [
            ['type' => 'image', 'id' => '_thumbnail_id', 'label' => 'B'],
        ]);

        $reserved = Registry::reservedFieldIds();
        $this->assertContains('box_a', $reserved['_thumbnail_id']);
        $this->assertContains('box_b', $reserved['_thumbnail_id']);
    }

    // -------------------------------------------------------------------------
    // duplicateMetaBoxIds()
    // -------------------------------------------------------------------------

    public function testDuplicateMetaBoxIdsDetectsClashOnSamePostType(): void
    {
        $this->makeMetaBox('product_info', 'post');
        $this->makeMetaBox('product_info', 'post');

        $dups = Registry::duplicateMetaBoxIds();
        $this->assertArrayHasKey('product_info', $dups);
        $this->assertContains('post', $dups['product_info']);
    }

    public function testDuplicateMetaBoxIdsIgnoresDifferentPostTypes(): void
    {
        $this->makeMetaBox('shared_box', 'post');
        $this->makeMetaBox('shared_box', 'page');

        $this->assertArrayNotHasKey('shared_box', Registry::duplicateMetaBoxIds());
    }

    public function testDuplicateMetaBoxIdsReturnsEmptyWhenNoDuplicates(): void
    {
        $this->makeMetaBox('box_a', 'post');
        $this->makeMetaBox('box_b', 'post');

        $this->assertSame([], Registry::duplicateMetaBoxIds());
    }

    public function testDuplicateMetaBoxIdsIgnoresNonMetaBoxTypes(): void
    {
        $this->makeMetaBox('box_a', 'post');
        $this->makeUserMeta('box_a');

        $this->assertArrayNotHasKey('box_a', Registry::duplicateMetaBoxIds());
    }

    // -------------------------------------------------------------------------
    // duplicates()
    // -------------------------------------------------------------------------

    public function testDuplicatesDetectsClashOnSameTarget(): void
    {
        $this->makeMetaBox('box_a', 'page', [$this->fieldDef('hero')]);
        $this->makeMetaBox('box_b', 'page', [$this->fieldDef('hero')]);

        $dups = Registry::duplicates();
        $this->assertArrayHasKey('hero', $dups);
        $this->assertContains('box_a', $dups['hero']);
        $this->assertContains('box_b', $dups['hero']);
    }

    public function testDuplicatesIgnoresDifferentTargets(): void
    {
        $this->makeMetaBox('box_a', 'post', [$this->fieldDef('hero')]);
        $this->makeMetaBox('box_b', 'page', [$this->fieldDef('hero')]);

        $this->assertArrayNotHasKey('hero', Registry::duplicates());
    }

    public function testDuplicatesIgnoresDifferentMetaTypes(): void
    {
        $this->makeMetaBox('box_a', 'post', [$this->fieldDef('bio')]);
        $this->makeUserMeta('user_section', [$this->fieldDef('bio')]);

        $this->assertArrayNotHasKey('bio', Registry::duplicates());
    }

    public function testDuplicatesReturnsEmptyWhenNoDuplicates(): void
    {
        $this->makeMetaBox('box_a', 'post', [$this->fieldDef('title')]);
        $this->makeMetaBox('box_b', 'post', [$this->fieldDef('subtitle')]);

        $this->assertSame([], Registry::duplicates());
    }

    // -------------------------------------------------------------------------
    // fields — heading exclusion
    // -------------------------------------------------------------------------

    public function testHeadingFieldsAreExcludedFromRegistryFields(): void
    {
        $this->makeMetaBox('box', 'post', [
            ['type' => 'heading', 'label' => 'Section'],
            $this->fieldDef('title'),
        ]);

        $fields = Registry::all()[0]['fields'];
        $this->assertArrayHasKey('title', $fields, 'Regular field should be present');
        foreach ($fields as $f) {
            $this->assertNotSame('heading', $f['type'], 'Heading fields must be excluded from registry');
        }
    }

    // -------------------------------------------------------------------------
    // bundles — flat layout has empty bundles key
    // -------------------------------------------------------------------------

    public function testFlatLayoutHasEmptyBundlesKey(): void
    {
        $this->makeMetaBox('box', 'post', [$this->fieldDef('title')]);

        $entry = Registry::all()[0];
        $this->assertArrayHasKey('bundles', $entry);
        $this->assertEmpty($entry['bundles']);
    }

    // -------------------------------------------------------------------------
    // bundles — direct bundle layout
    // -------------------------------------------------------------------------

    public function testBundleLayoutHasEmptyFlatFieldsAndPopulatedBundles(): void
    {
        $this->makeMetaBox('box', 'post', [
            'bundle',
            [$this->fieldDef('name'), $this->fieldDef('qty')],
        ]);

        $entry = Registry::all()[0];
        $this->assertSame('bundle', $entry['layout']);
        $this->assertEmpty($entry['fields'], 'Bundle row fields should not appear in flat fields');
        $this->assertCount(1, $entry['bundles']);
        $bundle = reset($entry['bundles']);
        $this->assertArrayHasKey('name', $bundle['fields']);
        $this->assertArrayHasKey('qty', $bundle['fields']);
    }

    public function testBundleFieldLabelAndRequiredAreCapturedInBundles(): void
    {
        $this->makeMetaBox('box', 'post', [
            'bundle',
            [
                ['type' => 'text', 'id' => 'sku', 'name' => 'sku', 'label' => 'SKU', 'required' => true],
            ],
        ]);

        $bundle = reset(Registry::all()[0]['bundles']);
        $this->assertSame('text', $bundle['fields']['sku']['type']);
        $this->assertSame('SKU', $bundle['fields']['sku']['label']);
        $this->assertTrue($bundle['fields']['sku']['required']);
    }

    // -------------------------------------------------------------------------
    // bundles — tabs with nested bundle
    // -------------------------------------------------------------------------

    public function testTabsWithBundleSeparatesBundleFromFlatFields(): void
    {
        $this->makeMetaBox('box', 'post', [
            'tabs',
            [
                'Tab A' => [$this->fieldDef('name')],
                'Tab B' => [['bundle', [$this->fieldDef('item'), $this->fieldDef('price')]]],
            ],
        ]);

        $entry = Registry::all()[0];
        $this->assertArrayHasKey('name', $entry['fields'], 'Flat tab field must appear in fields');
        $this->assertArrayNotHasKey('item', $entry['fields'], 'Bundle field must not appear in flat fields');
        $this->assertArrayNotHasKey('price', $entry['fields'], 'Bundle field must not appear in flat fields');
        $this->assertCount(1, $entry['bundles']);
        $bundle = reset($entry['bundles']);
        $this->assertArrayHasKey('item', $bundle['fields']);
        $this->assertArrayHasKey('price', $bundle['fields']);
    }

    // -------------------------------------------------------------------------
    // bundles — accordion with nested bundle
    // -------------------------------------------------------------------------

    public function testAccordionWithBundleSeparatesBundleFromFlatFields(): void
    {
        $this->makeMetaBox('box', 'post', [
            'accordion',
            [
                'Section A' => [$this->fieldDef('name')],
                'Section B' => [['bundle', [$this->fieldDef('item'), $this->fieldDef('qty')]]],
            ],
        ]);

        $entry = Registry::all()[0];
        $this->assertArrayHasKey('name', $entry['fields']);
        $this->assertArrayNotHasKey('item', $entry['fields']);
        $this->assertCount(1, $entry['bundles']);
        $bundle = reset($entry['bundles']);
        $this->assertArrayHasKey('item', $bundle['fields']);
        $this->assertArrayHasKey('qty', $bundle['fields']);
    }

    // -------------------------------------------------------------------------
    // sections — tabs / accordion
    // -------------------------------------------------------------------------

    public function testFlatLayoutHasEmptySectionsKey(): void
    {
        $this->makeMetaBox('box', 'post', [$this->fieldDef('title')]);

        $this->assertEmpty(Registry::all()[0]['sections']);
    }

    public function testTabsLayoutExposesSectionsWithTitlesAndFields(): void
    {
        $this->makeMetaBox('box', 'post', [
            'tabs',
            [
                'Onglet A' => [$this->fieldDef('name')],
                'Onglet B' => [$this->fieldDef('email')],
            ],
        ]);

        $sections = Registry::all()[0]['sections'];
        $this->assertCount(2, $sections);
        $this->assertSame('Onglet A', $sections[0]['title']);
        $this->assertArrayHasKey('name', $sections[0]['fields']);
        $this->assertNull($sections[0]['bundle_id']);
        $this->assertSame('Onglet B', $sections[1]['title']);
        $this->assertArrayHasKey('email', $sections[1]['fields']);
    }

    public function testAccordionWithBundleSectionExposesBundleId(): void
    {
        $this->makeMetaBox('box', 'post', [
            'accordion',
            [
                'Section A' => [$this->fieldDef('name')],
                'Section B' => [['bundle', [$this->fieldDef('item')]]],
            ],
        ]);

        $sections = Registry::all()[0]['sections'];
        $this->assertCount(2, $sections);
        $this->assertNull($sections[0]['bundle_id'], 'Plain section should have null bundle_id');
        $this->assertNotNull($sections[1]['bundle_id'], 'Bundle section should expose bundle_id');
        $this->assertEmpty($sections[1]['fields'], 'Bundle section fields should be empty (use bundles key instead)');
    }

    // -------------------------------------------------------------------------
    // reset()
    // -------------------------------------------------------------------------

    public function testResetClearsAllEntries(): void
    {
        $this->makeMetaBox();
        $this->makeMetaBox();
        Registry::reset();

        $this->assertSame([], Registry::all());
    }

    public function testResetAllowsNewRegistrationsAfterwards(): void
    {
        $this->makeMetaBox('old');
        Registry::reset();
        $this->makeMetaBox('new');

        $entries = Registry::all();
        $this->assertCount(1, $entries);
        $this->assertSame('new', $entries[0]['id']);
    }
}
