<?php

namespace Weblitzer\CFDev\Tests\Integration\PostType;

use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie les méthodes de la chaîne fluente sur PostType :
 *   addSupport(), addTaxonomy(), et combinaisons.
 */
class PostTypeChainTest extends IntegrationTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        do_action('init');
        Registry::reset();
    }

    public function tearDown(): void
    {
        Registry::reset();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // addSupport()
    // -------------------------------------------------------------------------

    public function testAddSupportAddsFeatureToPostType(): void
    {
        register_cfdev_post_type(['roman_ch', 'romans_ch'], ['public' => true])
            ?->addSupport('thumbnail');

        do_action('init');

        $this->assertTrue(post_type_supports('roman_ch', 'thumbnail'));
    }

    public function testAddSupportMultipleCalls(): void
    {
        register_cfdev_post_type(['poeme_ch', 'poemes_ch'], ['public' => true])
            ?->addSupport('excerpt')
            ?->addSupport('author');

        do_action('init');

        $this->assertTrue(post_type_supports('poeme_ch', 'excerpt'));
        $this->assertTrue(post_type_supports('poeme_ch', 'author'));
    }

    // -------------------------------------------------------------------------
    // addTaxonomy()
    // -------------------------------------------------------------------------

    public function testAddTaxonomyRegistersConnection(): void
    {
        register_cfdev_post_type(['recette_ch', 'recettes_ch'], ['public' => true])
            ?->addTaxonomy('categorie_ch');

        register_cfdev_taxonomy(['categorie_ch', 'categories_ch'], 'recette_ch', ['public' => true]);

        do_action('init');

        $this->assertTrue(taxonomy_exists('categorie_ch'));
        $this->assertContains('categorie_ch', get_object_taxonomies('recette_ch'));
    }

    // -------------------------------------------------------------------------
    // Chaîne complète : support + taxonomie + metabox
    // -------------------------------------------------------------------------

    public function testFullChainRegistersAllFeatures(): void
    {
        Registry::reset();

        $admin_id = static::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);

        $pt = register_cfdev_post_type(['album_ch', 'albums_ch'], ['public' => true]);
        if ($pt === null) {
            $this->fail('register_cfdev_post_type returned null');
        }
        $pt->addSupport('thumbnail')->addTaxonomy('style_ch');

        // addMetaBox() retourne l'instance PostType ; on crée la MetaBox séparément
        // pour garder la référence et tester savePost()
        $pt->addMetaBox('details_album_ch', 'Détails', [
            ['type' => 'text', 'id' => 'artiste_album_ch', 'label' => 'Artiste'],
        ]);

        register_cfdev_taxonomy(['style_ch', 'styles_ch'], 'album_ch', ['public' => true]);
        do_action('init');

        // Post type enregistré
        $this->assertTrue(post_type_exists('album_ch'));

        // Support thumbnail
        $this->assertTrue(post_type_supports('album_ch', 'thumbnail'));

        // Taxonomy liée
        $this->assertTrue(taxonomy_exists('style_ch'));
        $this->assertContains('style_ch', get_object_taxonomies('album_ch'));

        // MetaBox dans le Registry
        $ids = array_column(Registry::all(), 'id');
        $this->assertContains('details_album_ch', $ids);
    }
}
