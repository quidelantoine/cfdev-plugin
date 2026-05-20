<?php

namespace Weblitzer\CFDev\Tests\Unit;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Meta\UserMeta;
use Weblitzer\CFDev\Registry;

class RegistryRestFieldsTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Registry::reset();

        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(
            fn(string $s) => strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $s))
        );
        Functions\when('get_option')->justReturn(true);
        Functions\when('register_meta')->justReturn(true);
    }

    // -------------------------------------------------------------------------
    // Empty registry
    // -------------------------------------------------------------------------

    public function testRestFieldsIsEmptyWhenNothingRegistered(): void
    {
        $this->assertSame([], Registry::restFields());
    }

    public function testRestFieldsIsEmptyWhenNoFieldHasRestTrue(): void
    {
        new MetaBox('box', 'Box', 'post', [
            ['type' => 'text', 'id' => '_note', 'name' => 'Note'],
        ]);

        $this->assertSame([], Registry::restFields());
    }

    // -------------------------------------------------------------------------
    // Fields with rest: true
    // -------------------------------------------------------------------------

    public function testRestFieldsReturnsEntryForRestField(): void
    {
        new MetaBox('box', 'Box', 'post', [
            ['type' => 'text', 'id' => '_subtitle', 'name' => 'Subtitle', 'rest' => true],
        ]);

        $entries = Registry::restFields();

        $this->assertCount(1, $entries);
        $this->assertArrayHasKey('_subtitle', $entries[0]['fields']);
    }

    public function testRestFieldsExcludesNonRestFields(): void
    {
        new MetaBox('box', 'Box', 'post', [
            ['type' => 'text', 'id' => '_pub',     'name' => 'Public',   'rest' => true],
            ['type' => 'text', 'id' => '_private',  'name' => 'Private'],
        ]);

        $entries = Registry::restFields();
        $fields  = $entries[0]['fields'];

        $this->assertArrayHasKey('_pub', $fields);
        $this->assertArrayNotHasKey('_private', $fields);
    }

    public function testRestFieldsIncludesCorrectMetadata(): void
    {
        new MetaBox('details', 'Détails', 'book', [
            ['type' => 'number', 'id' => '_pages', 'name' => 'Pages', 'label' => 'Nombre de pages', 'rest' => true],
        ]);

        $entries = Registry::restFields();
        $field   = $entries[0]['fields']['_pages'];

        $this->assertSame('Nombre de pages', $field['label']);
        $this->assertSame('number', $field['type']);
        $this->assertSame('number', $field['rest_type']);
    }

    public function testRestFieldsIncludesMetaTypeAndTargets(): void
    {
        new MetaBox('box', 'Box', 'book', [
            ['type' => 'text', 'id' => '_pub', 'name' => 'Pub', 'rest' => true],
        ]);

        $entry = Registry::restFields()[0];

        $this->assertSame('post', $entry['meta_type']);
        $this->assertContains('book', $entry['targets']);
    }

    // -------------------------------------------------------------------------
    // Multiple meta types
    // -------------------------------------------------------------------------

    public function testRestFieldsAggregatesAcrossMetaTypes(): void
    {
        new MetaBox('post_box', 'Post Box', 'post', [
            ['type' => 'text', 'id' => '_pub_post', 'name' => 'Pub post', 'rest' => true],
        ]);
        new TermMeta('genre', '', [
            ['type' => 'text', 'id' => '_color', 'name' => 'Color', 'rest' => true],
        ]);
        new UserMeta('user_section', 'User', [
            ['type' => 'text', 'id' => '_bio', 'name' => 'Bio', 'rest' => true],
        ]);

        $this->assertCount(3, Registry::restFields());
    }

    // -------------------------------------------------------------------------
    // in_bundle fields excluded
    // -------------------------------------------------------------------------

    public function testRestFieldsExcludesInBundleFields(): void
    {
        new MetaBox('box', 'Box', 'post', [
            'bundle',
            'chapters_bundle',
            [
                ['type' => 'text', 'id' => '_ch_title', 'name' => 'Chapter title', 'rest' => true],
            ],
        ]);

        // Bundle sub-fields have in_bundle=true and must be excluded from REST
        $this->assertSame([], Registry::restFields());
    }
}
