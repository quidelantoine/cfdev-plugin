<?php

namespace Weblitzer\CFDev\Tests\Integration\PostType;

use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie que register_cfdev_post_type() enregistre réellement le CPT dans WP.
 */
class PostTypeRegistrationTest extends IntegrationTestCase
{
    public function testPostTypeIsRegisteredAfterInit(): void
    {
        register_cfdev_post_type(['livre', 'livres'], ['public' => true]);

        do_action('init');

        $this->assertTrue(post_type_exists('livre'));
    }

    public function testRegisteredPostTypeHasCorrectLabels(): void
    {
        register_cfdev_post_type(['film', 'films'], ['public' => true]);

        do_action('init');

        $post_type_object = get_post_type_object('film');

        $this->assertNotNull($post_type_object);
        $this->assertSame('Films', $post_type_object->label);
    }

    public function testPostCanBeCreatedForRegisteredType(): void
    {
        register_cfdev_post_type(['article', 'articles'], ['public' => true]);

        do_action('init');

        $post_id = static::factory()->post->create(['post_type' => 'article', 'post_title' => 'Test']);

        $this->assertGreaterThan(0, $post_id);
        $this->assertSame('article', get_post_type($post_id));
    }
}
