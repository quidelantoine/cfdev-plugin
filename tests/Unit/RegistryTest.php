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
        return new TermMeta($taxonomy, $fields);
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
