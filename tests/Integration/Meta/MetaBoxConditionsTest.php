<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie les conditions onlyForId() et onlyForTemplate() sur MetaBox.
 * Ces conditions limitent la sauvegarde à un post ou un template spécifique.
 */
class MetaBoxConditionsTest extends IntegrationTestCase
{
    private int $admin_id;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin_id = static::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_id);

        register_cfdev_post_type(['page_speciale', 'pages speciales'], ['public' => true]);
        do_action('init');

        Registry::reset();
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
    // onlyForId()
    // -------------------------------------------------------------------------

    public function testOnlyForIdSavesWhenIdsMatch(): void
    {
        $post_id = static::factory()->post->create(['post_type' => 'page_speciale']);

        $box = (new MetaBox('hero', 'Hero', 'page_speciale', [
            ['type' => 'text', 'id' => 'titre_hero', 'label' => 'Titre'],
        ]))->onlyForId($post_id);

        $this->postWith(['titre_hero' => 'Bienvenue']);
        $box->savePost($post_id);

        $this->assertSame('Bienvenue', get_post_meta($post_id, 'titre_hero', true));
    }

    public function testOnlyForIdSkipsWhenIdsDiffer(): void
    {
        $post_id_a = static::factory()->post->create(['post_type' => 'page_speciale']);
        $post_id_b = static::factory()->post->create(['post_type' => 'page_speciale']);

        $box = (new MetaBox('hero_other', 'Hero', 'page_speciale', [
            ['type' => 'text', 'id' => 'texte_hero', 'label' => 'Texte'],
        ]))->onlyForId($post_id_a);

        $this->postWith(['texte_hero' => 'Ne doit pas être sauvé']);
        $box->savePost($post_id_b);

        $this->assertSame('', get_post_meta($post_id_b, 'texte_hero', true));
    }

    public function testOnlyForIdDoesNotAffectOtherPostIdsInRegistry(): void
    {
        $post_id = static::factory()->post->create(['post_type' => 'page_speciale']);

        $box = (new MetaBox('restricted', 'Restreint', 'page_speciale', [
            ['type' => 'text', 'id' => 'champ_restreint', 'label' => 'Champ'],
        ]))->onlyForId($post_id + 1);

        $this->postWith(['champ_restreint' => 'valeur']);
        $box->savePost($post_id);

        $this->assertSame('', get_post_meta($post_id, 'champ_restreint', true));
    }

    // -------------------------------------------------------------------------
    // onlyForTemplate()
    // -------------------------------------------------------------------------

    public function testOnlyForTemplateSkipsWhenTemplateDoesNotMatch(): void
    {
        $post_id = static::factory()->post->create(['post_type' => 'page_speciale']);
        // Pas de template affecté → get_page_template_slug retourne ''

        $box = (new MetaBox('template_box', 'Template', 'page_speciale', [
            ['type' => 'text', 'id' => 'champ_template', 'label' => 'Champ'],
        ]))->onlyForTemplate('template-landing.php');

        $this->postWith(['champ_template' => 'valeur']);
        $box->savePost($post_id);

        $this->assertSame('', get_post_meta($post_id, 'champ_template', true));
    }

    public function testOnlyForTemplateSavesWhenTemplateMatches(): void
    {
        $post_id = static::factory()->post->create(['post_type' => 'page_speciale']);
        update_post_meta($post_id, '_wp_page_template', 'template-landing.php');

        $box = (new MetaBox('template_match', 'Template', 'page_speciale', [
            ['type' => 'text', 'id' => 'champ_landing', 'label' => 'Champ'],
        ]))->onlyForTemplate('template-landing.php');

        $this->postWith(['champ_landing' => 'contenu landing']);
        $box->savePost($post_id);

        $this->assertSame('contenu landing', get_post_meta($post_id, 'champ_landing', true));
    }

    // -------------------------------------------------------------------------
    // Condition in Registry
    // -------------------------------------------------------------------------

    public function testOnlyForIdConditionAppearsInRegistry(): void
    {
        $post_id = static::factory()->post->create(['post_type' => 'page_speciale']);

        (new MetaBox('cond_reg', 'Cond', 'page_speciale', [
            ['type' => 'text', 'id' => 'y', 'label' => 'Y'],
        ]))->onlyForId($post_id);

        $entry = Registry::all()[0];

        $this->assertArrayHasKey('conditions', $entry);
        $this->assertSame($post_id, $entry['conditions']['post_id'] ?? null);
    }
}
