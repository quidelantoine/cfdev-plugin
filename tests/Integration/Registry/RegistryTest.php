<?php

namespace Weblitzer\CFDev\Tests\Integration\Registry;

use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Meta\UserMeta;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie que Registry accumule, expose et filtre correctement
 * les entrées MetaBox / TermMeta / UserMeta enregistrées.
 */
class RegistryTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        register_cfdev_post_type(['article', 'articles'], ['public' => true]);
        register_cfdev_taxonomy(['sujet', 'sujets'], 'article', ['public' => true]);
        do_action('init');

        Registry::reset(); // must run after init — demo boxes re-register on every init fire
    }

    public function tearDown(): void
    {
        Registry::reset(); // prevent bleed into subsequent tests
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // all()
    // -------------------------------------------------------------------------

    public function testAllReturnsEmptyBeforeRegistration(): void
    {
        $this->assertSame([], Registry::all());
    }

    public function testAllReturnsMetaboxEntry(): void
    {
        new MetaBox('infos', 'Infos', 'article', [
            ['type' => 'text', 'id' => 'titre', 'label' => 'Titre'],
        ]);

        $entries = Registry::all();

        $this->assertCount(1, $entries);
        $this->assertSame('infos', $entries[0]['id']);
        $this->assertSame('post', $entries[0]['meta_type']);
        $this->assertContains('article', $entries[0]['targets']);
    }

    public function testAllReturnsTermMetaEntry(): void
    {
        new TermMeta('sujet', 'Sujet', [
            ['type' => 'text', 'id' => 'description_courte', 'label' => 'Description'],
        ]);

        $entries = Registry::all();

        $this->assertCount(1, $entries);
        $this->assertSame('term', $entries[0]['meta_type']);
        $this->assertContains('sujet', $entries[0]['targets']);
    }

    public function testAllReturnsUserMetaEntry(): void
    {
        new UserMeta('profil', 'Profil', [
            ['type' => 'text', 'id' => 'biographie', 'label' => 'Bio'],
        ]);

        $entries = Registry::all();

        $this->assertCount(1, $entries);
        $this->assertSame('user', $entries[0]['meta_type']);
    }

    public function testAllAccumulatesMultipleEntries(): void
    {
        new MetaBox('box_a', 'Box A', 'article', [
            ['type' => 'text', 'id' => 'champ_a', 'label' => 'A'],
        ]);
        new MetaBox('box_b', 'Box B', 'article', [
            ['type' => 'text', 'id' => 'champ_b', 'label' => 'B'],
        ]);

        $this->assertCount(2, Registry::all());
    }

    public function testAllIncludesFieldMap(): void
    {
        new MetaBox('details', 'Détails', 'article', [
            ['type' => 'text',   'id' => 'ref',  'label' => 'Référence'],
            ['type' => 'number', 'id' => 'prix', 'label' => 'Prix'],
        ]);

        $entry = Registry::all()[0];

        $this->assertArrayHasKey('ref', $entry['fields']);
        $this->assertArrayHasKey('prix', $entry['fields']);
    }

    // -------------------------------------------------------------------------
    // hasEntriesFor()
    // -------------------------------------------------------------------------

    public function testHasEntriesForReturnsFalseBeforeRegistration(): void
    {
        $this->assertFalse(Registry::hasEntriesFor('post'));
        $this->assertFalse(Registry::hasEntriesFor('term'));
        $this->assertFalse(Registry::hasEntriesFor('user'));
    }

    public function testHasEntriesForPostType(): void
    {
        new MetaBox('box', 'Box', 'article', [
            ['type' => 'text', 'id' => 'x', 'label' => 'X'],
        ]);

        $this->assertTrue(Registry::hasEntriesFor('post', 'article'));
        $this->assertFalse(Registry::hasEntriesFor('post', 'page'));
        $this->assertFalse(Registry::hasEntriesFor('term', 'article'));
    }

    public function testHasEntriesForTaxonomy(): void
    {
        new TermMeta('sujet', '', [
            ['type' => 'text', 'id' => 'x', 'label' => 'X'],
        ]);

        $this->assertTrue(Registry::hasEntriesFor('term', 'sujet'));
        $this->assertFalse(Registry::hasEntriesFor('term', 'category'));
    }

    public function testHasEntriesForUserWithoutTarget(): void
    {
        new UserMeta('profil', 'Profil', [
            ['type' => 'text', 'id' => 'x', 'label' => 'X'],
        ]);

        $this->assertTrue(Registry::hasEntriesFor('user'));
        $this->assertFalse(Registry::hasEntriesFor('post'));
    }

    // -------------------------------------------------------------------------
    // restFields()
    // -------------------------------------------------------------------------

    public function testRestFieldsEmptyWhenNoRestFlag(): void
    {
        new MetaBox('box', 'Box', 'article', [
            ['type' => 'text', 'id' => 'champ', 'label' => 'Champ', 'rest' => false],
        ]);

        $this->assertSame([], Registry::restFields());
    }

    public function testRestFieldsReturnsOnlyFlaggedFields(): void
    {
        new MetaBox('box_rest', 'Box', 'article', [
            ['type' => 'text', 'id' => 'expose',  'label' => 'Exposé',  'rest' => true],
            ['type' => 'text', 'id' => 'prive',   'label' => 'Privé',   'rest' => false],
        ]);

        $entries = Registry::restFields();

        $this->assertCount(1, $entries);
        $this->assertArrayHasKey('expose', $entries[0]['fields']);
        $this->assertArrayNotHasKey('prive', $entries[0]['fields']);
    }

    public function testRestFieldsIncludesMetaTypeAndTargets(): void
    {
        new MetaBox('box_meta', 'Box', 'article', [
            ['type' => 'text', 'id' => 'isbn', 'label' => 'ISBN', 'rest' => true],
        ]);

        $entry = Registry::restFields()[0];

        $this->assertSame('post', $entry['meta_type']);
        $this->assertContains('article', $entry['targets']);
    }

    // -------------------------------------------------------------------------
    // duplicates()
    // -------------------------------------------------------------------------

    public function testNoDuplicatesWithUniqueFields(): void
    {
        new MetaBox('box_1', 'Box 1', 'article', [
            ['type' => 'text', 'id' => 'champ_unique_1', 'label' => 'A'],
        ]);
        new MetaBox('box_2', 'Box 2', 'article', [
            ['type' => 'text', 'id' => 'champ_unique_2', 'label' => 'B'],
        ]);

        $this->assertSame([], Registry::duplicates());
    }

    public function testDetectsDuplicateFieldIdOnSamePostType(): void
    {
        new MetaBox('box_dup_1', 'Box 1', 'article', [
            ['type' => 'text', 'id' => 'champ_commun', 'label' => 'A'],
        ]);
        new MetaBox('box_dup_2', 'Box 2', 'article', [
            ['type' => 'text', 'id' => 'champ_commun', 'label' => 'A bis'],
        ]);

        $dups = Registry::duplicates();

        $this->assertArrayHasKey('champ_commun', $dups);
        $this->assertContains('box_dup_1', $dups['champ_commun']);
        $this->assertContains('box_dup_2', $dups['champ_commun']);
    }

    public function testNoDuplicateWhenSameFieldOnDifferentPostTypes(): void
    {
        register_cfdev_post_type(['livre', 'livres'], ['public' => true]);
        do_action('init');

        new MetaBox('box_art', 'Box Article', 'article', [
            ['type' => 'text', 'id' => 'champ_pareil', 'label' => 'A'],
        ]);
        new MetaBox('box_liv', 'Box Livre', 'livre', [
            ['type' => 'text', 'id' => 'champ_pareil', 'label' => 'A'],
        ]);

        $this->assertSame([], Registry::duplicates());
    }

    // -------------------------------------------------------------------------
    // reset()
    // -------------------------------------------------------------------------

    public function testResetClearsAllEntries(): void
    {
        new MetaBox('box_reset', 'Box', 'article', [
            ['type' => 'text', 'id' => 'x', 'label' => 'X'],
        ]);

        $this->assertCount(1, Registry::all());

        Registry::reset();

        $this->assertSame([], Registry::all());
    }
}
