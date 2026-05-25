<?php

namespace Weblitzer\CFDev\Tests\Integration\PostType;

use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie qu'une MetaBox déclarée pour plusieurs post types sauvegarde
 * les meta sur chacun d'eux, et ignore les types non déclarés.
 */
class MultiPostTypeMetaBoxTest extends IntegrationTestCase
{
    private int $admin_id;
    private int $post_id_a;
    private int $post_id_b;
    private int $post_id_other;

    public function setUp(): void
    {
        parent::setUp();
        Registry::reset();

        $this->admin_id = static::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_id);

        register_cfdev_post_type(['mptype_a', 'mptype_as'], ['public' => true]);
        register_cfdev_post_type(['mptype_b', 'mptype_bs'], ['public' => true]);
        register_cfdev_post_type(['mptype_c', 'mptype_cs'], ['public' => true]);
        do_action('init');

        $this->post_id_a     = static::factory()->post->create(['post_type' => 'mptype_a']);
        $this->post_id_b     = static::factory()->post->create(['post_type' => 'mptype_b']);
        $this->post_id_other = static::factory()->post->create(['post_type' => 'mptype_c']);
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
    // MetaBox partagée entre plusieurs post types
    // -------------------------------------------------------------------------

    public function testSharedMetaboxSavesOnFirstPostType(): void
    {
        $box = new MetaBox('shared_box', 'Shared', ['mptype_a', 'mptype_b'], [
            ['type' => 'text', 'id' => 'champ_partage', 'label' => 'Champ'],
        ]);

        $this->postWith(['champ_partage' => 'valeur A']);
        $box->savePost($this->post_id_a);

        $this->assertSame('valeur A', get_post_meta($this->post_id_a, 'champ_partage', true));
    }

    public function testSharedMetaboxSavesOnSecondPostType(): void
    {
        $box = new MetaBox('shared_box', 'Shared', ['mptype_a', 'mptype_b'], [
            ['type' => 'text', 'id' => 'champ_partage', 'label' => 'Champ'],
        ]);

        $this->postWith(['champ_partage' => 'valeur B']);
        $box->savePost($this->post_id_b);

        $this->assertSame('valeur B', get_post_meta($this->post_id_b, 'champ_partage', true));
    }

    public function testSharedMetaboxDoesNotSaveOnUnregisteredPostType(): void
    {
        $box = new MetaBox('shared_box', 'Shared', ['mptype_a', 'mptype_b'], [
            ['type' => 'text', 'id' => 'champ_partage', 'label' => 'Champ'],
        ]);

        $this->postWith(['champ_partage' => 'ne doit pas être sauvé']);
        $box->savePost($this->post_id_other);

        $this->assertSame('', get_post_meta($this->post_id_other, 'champ_partage', true));
    }

    public function testSharedMetaboxSavesIndependentlyPerPost(): void
    {
        $box = new MetaBox('shared_box', 'Shared', ['mptype_a', 'mptype_b'], [
            ['type' => 'text', 'id' => 'note_post', 'label' => 'Note'],
        ]);

        $this->postWith(['note_post' => 'note pour A']);
        $box->savePost($this->post_id_a);

        $this->postWith(['note_post' => 'note pour B']);
        $box->savePost($this->post_id_b);

        $this->assertSame('note pour A', get_post_meta($this->post_id_a, 'note_post', true));
        $this->assertSame('note pour B', get_post_meta($this->post_id_b, 'note_post', true));
    }

    // -------------------------------------------------------------------------
    // MetaBox pour un seul type (chaîne simple, pas tableau) — contrôle
    // -------------------------------------------------------------------------

    public function testSinglePostTypeAsStringRegistersCorrectly(): void
    {
        $box = new MetaBox('single_box', 'Single', 'mptype_a', [
            ['type' => 'text', 'id' => 'champ_solo', 'label' => 'Solo'],
        ]);

        $this->postWith(['champ_solo' => 'solo value']);
        $box->savePost($this->post_id_a);

        $this->assertSame('solo value', get_post_meta($this->post_id_a, 'champ_solo', true));
    }

    public function testSinglePostTypeDoesNotSaveOnOtherType(): void
    {
        $box = new MetaBox('single_box', 'Single', 'mptype_a', [
            ['type' => 'text', 'id' => 'champ_solo2', 'label' => 'Solo'],
        ]);

        $this->postWith(['champ_solo2' => 'ne doit pas']);
        $box->savePost($this->post_id_b);

        $this->assertSame('', get_post_meta($this->post_id_b, 'champ_solo2', true));
    }
}
