<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Meta\UserMeta;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie la sauvegarde des champs depuis un layout Tabs ou Accordion
 * pour MetaBox, TermMeta et UserMeta.
 *
 * Dans un layout tabs/accordion, tous les champs plats de tous les onglets
 * sont postés ensemble dans $_POST['cfdev']. Chaque onglet est sauvegardé
 * indépendamment mais depuis les mêmes valeurs POST.
 */
class TabsSaveTest extends IntegrationTestCase
{
    private int $admin_id;
    private int $post_id;
    private int $term_id;
    private int $user_id;

    public function setUp(): void
    {
        parent::setUp();

        register_cfdev_post_type(['formation', 'formations'], ['public' => true]);
        register_cfdev_taxonomy(['domaine', 'domaines'], 'formation', ['public' => true]);
        do_action('init');

        Registry::reset();

        $this->admin_id = static::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_id);

        $this->post_id = static::factory()->post->create(['post_type' => 'formation']);
        $ins = wp_insert_term('Développement', 'domaine');
        if (is_wp_error($ins)) {
            throw new \RuntimeException($ins->get_error_message()); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
        $this->term_id = $ins['term_id'];
        $this->user_id = $this->admin_id;
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
    // MetaBox — Tabs
    // -------------------------------------------------------------------------

    public function testMetaboxTabsFieldsAreSaved(): void
    {
        $box = new MetaBox('fiche_formation', 'Fiche formation', 'formation', [
            'tabs', [
                'Général' => [
                    ['type' => 'text', 'id' => 'titre_tab',   'label' => 'Titre'],
                    ['type' => 'text', 'id' => 'duree_tab',   'label' => 'Durée'],
                ],
                'Détails' => [
                    ['type' => 'text', 'id' => 'objectif_tab', 'label' => 'Objectif'],
                ],
            ],
        ]);

        $this->postWith([
            'titre_tab'    => 'Symfony avancé',
            'duree_tab'    => '3 jours',
            'objectif_tab' => 'Maîtriser les bundles',
        ]);
        $box->savePost($this->post_id);

        $this->assertSame('Symfony avancé', get_post_meta($this->post_id, 'titre_tab', true));
        $this->assertSame('3 jours', get_post_meta($this->post_id, 'duree_tab', true));
        $this->assertSame('Maîtriser les bundles', get_post_meta($this->post_id, 'objectif_tab', true));
    }

    public function testMetaboxAccordionFieldsAreSaved(): void
    {
        $box = new MetaBox('faq_formation', 'FAQ', 'formation', [
            'accordion', [
                'Public visé' => [
                    ['type' => 'text', 'id' => 'prerequis_acc', 'label' => 'Prérequis'],
                ],
                'Programme' => [
                    ['type' => 'text', 'id' => 'contenu_acc', 'label' => 'Contenu'],
                ],
            ],
        ]);

        $this->postWith([
            'prerequis_acc' => 'PHP intermédiaire',
            'contenu_acc'   => 'Architecture, DI, Events',
        ]);
        $box->savePost($this->post_id);

        $this->assertSame('PHP intermédiaire', get_post_meta($this->post_id, 'prerequis_acc', true));
        $this->assertSame('Architecture, DI, Events', get_post_meta($this->post_id, 'contenu_acc', true));
    }

    public function testMetaboxTabsWithBundleSavesBundle(): void
    {
        $box = new MetaBox('modules_formation', 'Modules', 'formation', [
            'tabs', [
                'Modules' => [
                    ['bundle', 'modules_tab', [
                        ['type' => 'text', 'id' => 'nom_mod',   'label' => 'Nom'],
                        ['type' => 'text', 'id' => 'duree_mod', 'label' => 'Durée'],
                    ]],
                ],
            ],
        ]);

        $this->postWith([
            '_modules_tab' => [
                0 => ['nom_mod' => 'Module 1', 'duree_mod' => '1h'],
                1 => ['nom_mod' => 'Module 2', 'duree_mod' => '2h'],
            ],
        ]);
        $box->savePost($this->post_id);

        $decoded = json_decode(get_post_meta($this->post_id, '_modules_tab', true), true);

        $this->assertCount(2, $decoded);
        $this->assertSame('Module 1', $decoded[0]['nom_mod']);
        $this->assertSame('Module 2', $decoded[1]['nom_mod']);
    }

    // -------------------------------------------------------------------------
    // TermMeta — Tabs
    // -------------------------------------------------------------------------

    public function testTermmetaTabsFieldsAreSaved(): void
    {
        $tm = new TermMeta('domaine', 'Domaine', [
            'tabs', [
                'Description' => [
                    ['type' => 'text', 'id' => 'desc_domaine',  'label' => 'Description'],
                ],
                'Métadonnées' => [
                    ['type' => 'text', 'id' => 'source_domaine', 'label' => 'Source'],
                ],
            ],
        ]);

        $this->postWith([
            'desc_domaine'   => 'Technologies web',
            'source_domaine' => 'Référentiel ROME',
        ]);
        $tm->saveTerm($this->term_id);

        $this->assertSame('Technologies web', get_term_meta($this->term_id, 'desc_domaine', true));
        $this->assertSame('Référentiel ROME', get_term_meta($this->term_id, 'source_domaine', true));
    }

    public function testTermmetaAccordionFieldsAreSaved(): void
    {
        $tm = new TermMeta('domaine', 'Domaine', [
            'accordion', [
                'Info' => [
                    ['type' => 'text', 'id' => 'info_acc_term', 'label' => 'Info'],
                ],
                'Liens' => [
                    ['type' => 'text', 'id' => 'lien_acc_term', 'label' => 'Lien'],
                ],
            ],
        ]);

        $this->postWith([
            'info_acc_term' => 'Domaine très demandé',
            'lien_acc_term' => 'https://pole-emploi.fr',
        ]);
        $tm->saveTerm($this->term_id);

        $this->assertSame('Domaine très demandé', get_term_meta($this->term_id, 'info_acc_term', true));
        $this->assertSame('https://pole-emploi.fr', get_term_meta($this->term_id, 'lien_acc_term', true));
    }

    // -------------------------------------------------------------------------
    // UserMeta — Tabs
    // -------------------------------------------------------------------------

    public function testUsermetaTabsFieldsAreSaved(): void
    {
        $um = new UserMeta('profil', 'Profil', [
            'tabs', [
                'Contact' => [
                    ['type' => 'text', 'id' => 'tel_tabs',    'label' => 'Téléphone'],
                ],
                'Profil'  => [
                    ['type' => 'text', 'id' => 'github_tabs', 'label' => 'GitHub'],
                ],
            ],
        ]);

        $this->postWith([
            'tel_tabs'    => '+33612345678',
            'github_tabs' => 'github.com/johndoe',
        ]);
        $um->saveUser($this->user_id);

        $this->assertSame('+33612345678', get_user_meta($this->user_id, 'tel_tabs', true));
        $this->assertSame('github.com/johndoe', get_user_meta($this->user_id, 'github_tabs', true));
    }

    public function testUsermetaAccordionFieldsAreSaved(): void
    {
        $um = new UserMeta('profil', 'Profil', [
            'accordion', [
                'Disponibilité' => [
                    ['type' => 'text', 'id' => 'dispo_acc', 'label' => 'Disponibilité'],
                ],
            ],
        ]);

        $this->postWith(['dispo_acc' => 'Immédiate']);
        $um->saveUser($this->user_id);

        $this->assertSame('Immédiate', get_user_meta($this->user_id, 'dispo_acc', true));
    }

    // -------------------------------------------------------------------------
    // Mixed tabs : flat + bundle dans le même MetaBox
    // -------------------------------------------------------------------------

    public function testMetaboxMixedTabsSavesBothFlatAndBundle(): void
    {
        $box = new MetaBox('plan_formation', 'Plan', 'formation', [
            'tabs', [
                'Présentation' => [
                    ['type' => 'text', 'id' => 'resume_plan', 'label' => 'Résumé'],
                ],
                'Sessions' => [
                    ['bundle', 'sessions_plan', [
                        ['type' => 'text', 'id' => 'date_session', 'label' => 'Date'],
                        ['type' => 'text', 'id' => 'lieu_session', 'label' => 'Lieu'],
                    ]],
                ],
            ],
        ]);

        $this->postWith([
            'resume_plan'    => 'Formation certifiante',
            '_sessions_plan' => [
                0 => ['date_session' => '2026-09-01', 'lieu_session' => 'Paris'],
                1 => ['date_session' => '2026-10-01', 'lieu_session' => 'Lyon'],
            ],
        ]);
        $box->savePost($this->post_id);

        $this->assertSame('Formation certifiante', get_post_meta($this->post_id, 'resume_plan', true));

        $decoded = json_decode(get_post_meta($this->post_id, '_sessions_plan', true), true);
        $this->assertCount(2, $decoded);
        $this->assertSame('Paris', $decoded[0]['lieu_session']);
        $this->assertSame('Lyon', $decoded[1]['lieu_session']);
    }
}
