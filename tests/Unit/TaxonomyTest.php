<?php

namespace Weblitzer\CFDev\Tests\Unit;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Taxonomy;

class TaxonomyTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Functions\when('taxonomy_exists')->justReturn(true); // → attach() path
        Functions\when('register_taxonomy_for_object_type')->justReturn(true);
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('register_meta')->justReturn(true);
    }

    private function makeTaxonomy(): Taxonomy
    {
        return new Taxonomy('genre', 'post');
    }

    // -------------------------------------------------------------------------
    // addTermMeta()
    // -------------------------------------------------------------------------

    public function testAddTermMetaRegistersTermMetaForTaxonomyAndReturnsSelf(): void
    {
        $taxonomy = $this->makeTaxonomy();

        $result = $taxonomy->addTermMeta([
            ['type' => 'text', 'id' => '_desc', 'name' => 'Description'],
        ], 'Genre Details');

        $this->assertSame($taxonomy, $result);
    }

    public function testAddTermMetaAppearsInRegistry(): void
    {
        $taxonomy = $this->makeTaxonomy();
        $taxonomy->addTermMeta([
            ['type' => 'text', 'id' => '_label', 'name' => 'Label'],
        ]);

        $entries = Registry::all();
        $termEntries = array_values(array_filter($entries, fn($e) => $e['meta_type'] === 'term'));
        $this->assertCount(1, $termEntries);
        $this->assertSame('genre', $termEntries[0]['targets'][0] ?? '');
    }

    // -------------------------------------------------------------------------
    // onlyIfParent()
    // -------------------------------------------------------------------------

    public function testOnlyIfParentSetsConditionOnLastTermMetaAndReturnsSelf(): void
    {
        $taxonomy = $this->makeTaxonomy();
        $taxonomy->addTermMeta([
            ['type' => 'text', 'id' => '_desc', 'name' => 'Desc'],
        ]);

        $result = $taxonomy->onlyIfParent(5);

        $this->assertSame($taxonomy, $result);
    }

    public function testOnlyIfParentPropagatesConditionToRegistryEntry(): void
    {
        $taxonomy = $this->makeTaxonomy();
        $taxonomy->addTermMeta([
            ['type' => 'text', 'id' => '_desc', 'name' => 'Desc'],
        ])->onlyIfParent(7);

        $entries     = Registry::all();
        $termEntries = array_values(array_filter($entries, fn($e) => $e['meta_type'] === 'term'));
        $this->assertSame(7, $termEntries[0]['conditions']['parent_id'] ?? null);
    }

    public function testOnlyIfParentWithoutPriorAddTermMetaDoesNotCrash(): void
    {
        $taxonomy = $this->makeTaxonomy();

        // No addTermMeta() called → lastTermMeta is null → onlyIfParent is a no-op
        $result = $taxonomy->onlyIfParent(3);

        $this->assertSame($taxonomy, $result);
    }
}
