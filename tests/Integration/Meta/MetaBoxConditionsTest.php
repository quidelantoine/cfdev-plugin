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

    // -------------------------------------------------------------------------
    // onlyWhen()
    // -------------------------------------------------------------------------

    public function testOnlyWhenSavesWhenCallableReturnsTrue(): void
    {
        $post_id = static::factory()->post->create(['post_type' => 'page_speciale']);

        $box = (new MetaBox('when_true', 'When', 'page_speciale', [
            ['type' => 'text', 'id' => 'champ_when', 'label' => 'Champ'],
        ]))->onlyWhen(fn(\WP_Post $p) => true);

        $this->postWith(['champ_when' => 'sauvé']);
        $box->savePost($post_id);

        $this->assertSame('sauvé', get_post_meta($post_id, 'champ_when', true));
    }

    public function testOnlyWhenSkipsWhenCallableReturnsFalse(): void
    {
        $post_id = static::factory()->post->create(['post_type' => 'page_speciale']);

        $box = (new MetaBox('when_false', 'When', 'page_speciale', [
            ['type' => 'text', 'id' => 'champ_when_f', 'label' => 'Champ'],
        ]))->onlyWhen(fn(\WP_Post $p) => false);

        $this->postWith(['champ_when_f' => 'ne doit pas être sauvé']);
        $box->savePost($post_id);

        $this->assertSame('', get_post_meta($post_id, 'champ_when_f', true));
    }

    public function testOnlyWhenReceivesCorrectPost(): void
    {
        $post_id    = static::factory()->post->create(['post_type' => 'page_speciale']);
        $received   = null;

        $box = (new MetaBox('when_post', 'When', 'page_speciale', [
            ['type' => 'text', 'id' => 'champ_post_check', 'label' => 'Champ'],
        ]))->onlyWhen(function (\WP_Post $p) use (&$received): bool {
            $received = $p->ID;
            return true;
        });

        $this->postWith(['champ_post_check' => 'val']);
        $box->savePost($post_id);

        $this->assertSame($post_id, $received);
    }

    public function testOnlyWhenStackedWithTemplateCondition(): void
    {
        $post_id = static::factory()->post->create(['post_type' => 'page_speciale']);
        update_post_meta($post_id, '_wp_page_template', 'template-landing.php');

        // template matches but callable returns false → skip
        $box = (new MetaBox('when_stack', 'Stack', 'page_speciale', [
            ['type' => 'text', 'id' => 'champ_stack', 'label' => 'Champ'],
        ]))
            ->onlyForTemplate('template-landing.php')
            ->onlyWhen(fn(\WP_Post $p) => false);

        $this->postWith(['champ_stack' => 'ne doit pas être sauvé']);
        $box->savePost($post_id);

        $this->assertSame('', get_post_meta($post_id, 'champ_stack', true));
    }

    public function testOnlyWhenConditionAppearsInRegistry(): void
    {
        (new MetaBox('when_reg', 'Cond', 'page_speciale', [
            ['type' => 'text', 'id' => 'z', 'label' => 'Z'],
        ]))->onlyWhen(fn(\WP_Post $p) => true);

        $entry = Registry::all()[0];

        $this->assertArrayHasKey('conditions', $entry);
        $this->assertArrayHasKey('callable_conditions', $entry['conditions']);
        // callable_conditions stores labels (strings), not closures
        $this->assertSame(['fn()'], $entry['conditions']['callable_conditions']);
    }

    public function testOnlyWhenLabelAppearsInRegistryConditions(): void
    {
        (new MetaBox('when_label', 'Cond', 'page_speciale', [
            ['type' => 'text', 'id' => 'zz', 'label' => 'ZZ'],
        ]))->onlyWhen(fn(\WP_Post $p) => true, 'Admins uniquement');

        $entry = Registry::all()[0];

        $this->assertSame(['Admins uniquement'], $entry['conditions']['callable_conditions']);
    }

    public function testMultipleOnlyWhenLabelsStoredInOrder(): void
    {
        (new MetaBox('when_multi', 'Cond', 'page_speciale', [
            ['type' => 'text', 'id' => 'zzz', 'label' => 'ZZZ'],
        ]))
            ->onlyWhen(fn(\WP_Post $p) => true, 'Label A')
            ->onlyWhen(fn(\WP_Post $p) => true, 'Label B');

        $entry = Registry::all()[0];

        $this->assertSame(['Label A', 'Label B'], $entry['conditions']['callable_conditions']);
    }
}
