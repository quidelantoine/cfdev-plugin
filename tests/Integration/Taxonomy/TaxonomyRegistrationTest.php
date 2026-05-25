<?php

namespace Weblitzer\CFDev\Tests\Integration\Taxonomy;

use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie que register_cfdev_taxonomy() enregistre la taxonomie dans WP
 * et que les termes peuvent être créés, retrouvés, et associés à des posts.
 */
class TaxonomyRegistrationTest extends IntegrationTestCase
{
    public function testTaxonomyIsRegisteredAfterInit(): void
    {
        register_cfdev_taxonomy(['genre', 'genres'], 'post', ['public' => true]);

        do_action('init');

        $this->assertTrue(taxonomy_exists('genre'));
    }

    public function testTaxonomyIsAttachedToPostType(): void
    {
        register_cfdev_post_type(['roman', 'romans'], ['public' => true]);
        register_cfdev_taxonomy(['categorie_roman', 'categories roman'], 'roman', ['public' => true]);

        do_action('init');

        $taxonomies = get_object_taxonomies('roman');
        $this->assertContains('categorie_roman', $taxonomies);
    }

    public function testCanInsertTermInRegisteredTaxonomy(): void
    {
        register_cfdev_taxonomy(['couleur', 'couleurs'], 'post', ['public' => true]);

        do_action('init');

        $result = wp_insert_term('Rouge', 'couleur');

        if (is_wp_error($result)) {
            $this->fail($result->get_error_message());
        }
        $this->assertGreaterThan(0, $result['term_id']);
    }

    public function testTermCanBeRetrievedBySlug(): void
    {
        register_cfdev_taxonomy(['style', 'styles'], 'post', ['public' => true]);

        do_action('init');

        wp_insert_term('Science Fiction', 'style');

        $term = get_term_by('slug', 'science-fiction', 'style');

        $this->assertInstanceOf(\WP_Term::class, $term);
        $this->assertSame('Science Fiction', $term->name);
    }

    public function testPostCanBeAssignedATerm(): void
    {
        register_cfdev_post_type(['album', 'albums'], ['public' => true]);
        register_cfdev_taxonomy(['format_album', 'formats album'], 'album', ['public' => true]);

        do_action('init');

        $post_id = static::factory()->post->create(['post_type' => 'album']);
        $term = wp_insert_term('Vinyle', 'format_album');

        if (is_wp_error($term)) {
            $this->fail($term->get_error_message());
        }

        wp_set_post_terms($post_id, [$term['term_id']], 'format_album');

        $assigned = wp_get_post_terms($post_id, 'format_album', ['fields' => 'names']);

        $this->assertIsArray($assigned);
        $this->assertContains('Vinyle', $assigned);
    }

    public function testExistingTaxonomyIsAttachedWithoutReRegistration(): void
    {
        register_cfdev_post_type(['fiche', 'fiches'], ['public' => true]);
        register_cfdev_taxonomy(['tag_fiche', 'tags fiche'], 'fiche', ['public' => true]);
        // Deuxième appel avec le même slug → attach uniquement, pas d'erreur WP
        register_cfdev_taxonomy(['tag_fiche', 'tags fiche'], 'post', ['public' => true]);

        do_action('init');

        $this->assertTrue(taxonomy_exists('tag_fiche'));
        $this->assertContains('tag_fiche', get_object_taxonomies('fiche'));
        $this->assertContains('tag_fiche', get_object_taxonomies('post'));
    }
}
