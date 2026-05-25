<?php

namespace Weblitzer\CFDev\Tests\Integration\PostType;

use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie le chaînage fluent du point d'entrée public :
 *   register_cfdev_post_type(['livre', 'livres'])
 *       ->addMetaBox('details', 'Détails', $fields)
 *       ->onlyForId($id)
 *
 * C'est le vrai chemin emprunté par les utilisateurs du plugin.
 */
class FluentApiTest extends IntegrationTestCase
{
    private int $admin_id;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin_id = static::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_id);

        // init seulement pour la disponibilité WP des CPTs — pas de Registry::reset()
        // car addMetaBox() enregistre immédiatement (avant init), et un reset après
        // le ferait disparaître du Registry.
        do_action('init');
    }

    public function tearDown(): void
    {
        $_POST = [];
        Registry::reset();
        parent::tearDown();
    }

    /** @param array<string, mixed> $values */
    private function postWith(array $values): void
    {
        $_POST['cfdev_nonce'] = wp_create_nonce('cfdev_meta');
        $_POST['cfdev']       = array_merge(['__activate' => ''], $values);
    }

    // -------------------------------------------------------------------------
    // Enregistrement du CPT et du MetaBox via chaînage
    // -------------------------------------------------------------------------

    public function testPostTypeIsRegisteredAfterInit(): void
    {
        register_cfdev_post_type(['roman', 'romans'], ['public' => true]);
        do_action('init');

        $this->assertTrue(post_type_exists('roman'));
    }

    public function testMetaboxRegisteredViaChainAppearsInRegistry(): void
    {
        // Reset avant le chaînage : addMetaBox() s'enregistre immédiatement
        Registry::reset();

        register_cfdev_post_type(['roman', 'romans'], ['public' => true])
            ?->addMetaBox('fiche_roman', 'Fiche', [
                ['type' => 'text', 'id' => 'auteur_roman', 'label' => 'Auteur'],
                ['type' => 'text', 'id' => 'genre_roman',  'label' => 'Genre'],
            ]);

        $ids = array_column(Registry::all(), 'id');

        $this->assertContains('fiche_roman', $ids);
    }

    public function testMetaboxChainSavesFieldsInPostMeta(): void
    {
        // addMetaBox() accroche save_post immédiatement — pas besoin de Registry
        register_cfdev_post_type(['roman', 'romans'], ['public' => true])
            ?->addMetaBox('fiche_roman', 'Fiche', [
                ['type' => 'text', 'id' => 'auteur_roman', 'label' => 'Auteur'],
                ['type' => 'text', 'id' => 'isbn_roman',   'label' => 'ISBN'],
            ]);

        $post_id = static::factory()->post->create(['post_type' => 'roman']);

        $this->postWith([
            'auteur_roman' => 'Victor Hugo',
            'isbn_roman'   => '978-2-07-036024-5',
        ]);

        do_action('save_post', $post_id);

        $this->assertSame('Victor Hugo', get_post_meta($post_id, 'auteur_roman', true));
        $this->assertSame('978-2-07-036024-5', get_post_meta($post_id, 'isbn_roman', true));
    }

    public function testMultipleMetaboxesChainedAreAllRegistered(): void
    {
        Registry::reset();

        register_cfdev_post_type(['roman', 'romans'], ['public' => true])
            ?->addMetaBox('fiche_roman', 'Fiche', [
                ['type' => 'text', 'id' => 'titre_r', 'label' => 'Titre'],
            ])
            ?->addMetaBox('media_roman', 'Médias', [
                ['type' => 'text', 'id' => 'couverture_r', 'label' => 'Couverture'],
            ]);

        $ids = array_column(Registry::all(), 'id');

        $this->assertContains('fiche_roman', $ids);
        $this->assertContains('media_roman', $ids);
    }

    // -------------------------------------------------------------------------
    // onlyForId() via chaînage
    // -------------------------------------------------------------------------

    public function testOnlyForIdViaChainSavesForMatchingPost(): void
    {
        register_cfdev_post_type(['film', 'films'], ['public' => true]);
        do_action('init');

        $post_id = static::factory()->post->create(['post_type' => 'film']);

        // On instancie le MetaBox directement (le chaînage crée la même chose)
        $box = (new \Weblitzer\CFDev\Meta\MetaBox('hero_film', 'Hero', 'film', [
            ['type' => 'text', 'id' => 'tagline_film', 'label' => 'Tagline'],
        ]))->onlyForId($post_id);

        $this->postWith(['tagline_film' => 'Le film de l\'année']);
        $box->savePost($post_id);

        $this->assertSame('Le film de l\'année', get_post_meta($post_id, 'tagline_film', true));
    }

    public function testOnlyForIdViaChainSkipsForOtherPost(): void
    {
        register_cfdev_post_type(['film', 'films'], ['public' => true]);
        do_action('init');

        $post_a = static::factory()->post->create(['post_type' => 'film']);
        $post_b = static::factory()->post->create(['post_type' => 'film']);

        $box = (new \Weblitzer\CFDev\Meta\MetaBox('hero_other_film', 'Hero', 'film', [
            ['type' => 'text', 'id' => 'tagline_other', 'label' => 'Tagline'],
        ]))->onlyForId($post_a);

        $this->postWith(['tagline_other' => 'Ne doit pas être sauvé']);
        $box->savePost($post_b);

        $this->assertSame('', get_post_meta($post_b, 'tagline_other', true));
    }

    // -------------------------------------------------------------------------
    // Taxonomy + TermMeta via fonctions publiques
    // -------------------------------------------------------------------------

    public function testTaxonomyAndTermMetaRegisteredViaPublicApi(): void
    {
        register_cfdev_post_type(['roman', 'romans'], ['public' => true])
            ?->addTaxonomy('genre_roman');

        register_cfdev_taxonomy(['genre_roman', 'genres roman'], 'roman', ['public' => true]);
        do_action('init');

        $this->assertTrue(taxonomy_exists('genre_roman'));

        $result = wp_insert_term('Policier', 'genre_roman');
        if (is_wp_error($result)) {
            $this->fail($result->get_error_message());
        }
        $term_id = $result['term_id'];

        $tm = new \Weblitzer\CFDev\Meta\TermMeta('genre_roman', 'Genre', [
            ['type' => 'text', 'id' => 'desc_genre_roman', 'label' => 'Description'],
        ]);

        $_POST['cfdev_nonce'] = wp_create_nonce('cfdev_meta');
        $_POST['cfdev']       = ['__activate' => '', 'desc_genre_roman' => 'Romans policiers'];
        $tm->saveTerm($term_id);

        $this->assertSame('Romans policiers', get_term_meta($term_id, 'desc_genre_roman', true));
    }
}
