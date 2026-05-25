<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie MetaBox::addColumn() et addColumnContent() avec une vraie BDD WP.
 */
class MetaBoxColumnTest extends IntegrationTestCase
{
    private int $post_id;

    public function setUp(): void
    {
        parent::setUp();
        Registry::reset();

        register_cfdev_post_type(['article', 'articles'], ['public' => true]);
        do_action('init');

        $this->post_id = static::factory()->post->create(['post_type' => 'article']);
    }

    public function tearDown(): void
    {
        Registry::reset();
        parent::tearDown();
    }

    private function buildMetaBox(): MetaBox
    {
        return new MetaBox('infos', 'Informations', 'article', [
            ['type' => 'text', 'id' => 'auteur', 'label' => 'Auteur', 'show_admin_column' => true],
            ['type' => 'text', 'id' => 'isbn',   'label' => 'ISBN'],
        ]);
    }

    // -------------------------------------------------------------------------
    // addColumn
    // -------------------------------------------------------------------------

    public function testAddColumnAddsHeaderForShowAdminColumnField(): void
    {
        $box    = $this->buildMetaBox();
        $result = $box->addColumn(['title' => 'Titre', 'date' => 'Date']);

        $this->assertArrayHasKey('auteur', $result);
        $this->assertSame('Auteur', $result['auteur']);
    }

    public function testAddColumnSkipsFieldWithoutShowAdminColumn(): void
    {
        $box    = $this->buildMetaBox();
        $result = $box->addColumn(['title' => 'Titre', 'date' => 'Date']);

        $this->assertArrayNotHasKey('isbn', $result);
    }

    public function testAddColumnMovesDateToLastPosition(): void
    {
        $box    = $this->buildMetaBox();
        $result = $box->addColumn(['title' => 'Titre', 'date' => 'Date']);
        $keys   = array_keys($result);

        $this->assertSame('date', end($keys));
    }

    // -------------------------------------------------------------------------
    // addColumnContent
    // -------------------------------------------------------------------------

    public function testAddColumnContentEchoesTextMetaValue(): void
    {
        $box = $this->buildMetaBox();
        update_post_meta($this->post_id, 'auteur', 'Victor Hugo');

        ob_start();
        $box->addColumnContent('auteur', $this->post_id);
        $output = ob_get_clean();

        $this->assertSame('Victor Hugo', $output);
    }

    public function testAddColumnContentEchoesImplodedValueForRepeatableField(): void
    {
        $box = new MetaBox('repet', 'Répétable', 'article', [
            ['type' => 'text', 'id' => 'tags', 'label' => 'Tags', 'show_admin_column' => true, 'repeatable' => true],
        ]);

        // decodeMetaValue décodera le JSON en tableau PHP
        update_post_meta($this->post_id, 'tags', wp_json_encode(['PHP', 'WordPress']));

        ob_start();
        $box->addColumnContent('tags', $this->post_id);
        $output = ob_get_clean();

        $this->assertSame('PHP, WordPress', $output);
    }

    public function testAddColumnContentEchoesNothingForUnknownColumn(): void
    {
        $box = $this->buildMetaBox();

        ob_start();
        $box->addColumnContent('colonne_inconnue', $this->post_id);
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }
}
