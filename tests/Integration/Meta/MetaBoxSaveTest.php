<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie que MetaBox::savePost() persiste les valeurs en base
 * et gère la validation via les vrais hooks WP et update_post_meta().
 */
class MetaBoxSaveTest extends IntegrationTestCase
{
    private int $admin_id;
    private int $post_id;

    public function setUp(): void
    {
        parent::setUp();
        Registry::reset();

        $this->admin_id = static::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_id);

        register_cfdev_post_type(['produit', 'produits'], ['public' => true]);
        do_action('init');

        $this->post_id = static::factory()->post->create(['post_type' => 'produit']);
    }

    public function tearDown(): void
    {
        $_POST = [];
        Registry::reset();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param list<array<string, mixed>> $fields */
    private function buildMetaBox(array $fields): MetaBox
    {
        return new MetaBox('infos', 'Informations', 'produit', $fields);
    }

    /** @param array<string, mixed> $cfdev_values */
    private function postWith(array $cfdev_values): void
    {
        $_POST['cfdev_nonce'] = wp_create_nonce('cfdev_meta');
        $_POST['cfdev']       = array_merge(['__activate' => ''], $cfdev_values);
    }

    // -------------------------------------------------------------------------
    // Sauvegarde simple
    // -------------------------------------------------------------------------

    public function testTextFieldValueIsSavedInPostMeta(): void
    {
        $box = $this->buildMetaBox([
            ['type' => 'text', 'id' => 'ref_produit', 'label' => 'Référence'],
        ]);

        $this->postWith(['ref_produit' => 'REF-001']);
        $box->savePost($this->post_id);

        $this->assertSame('REF-001', get_post_meta($this->post_id, 'ref_produit', true));
    }

    public function testMultipleFieldsAreAllSaved(): void
    {
        $box = $this->buildMetaBox([
            ['type' => 'text',   'id' => 'titre_produit',       'label' => 'Titre'],
            ['type' => 'text',   'id' => 'description_produit', 'label' => 'Description'],
            ['type' => 'number', 'id' => 'prix_produit',        'label' => 'Prix'],
        ]);

        $this->postWith([
            'titre_produit'       => 'Mon produit',
            'description_produit' => 'Super article',
            'prix_produit'        => '29',
        ]);
        $box->savePost($this->post_id);

        $this->assertSame('Mon produit', get_post_meta($this->post_id, 'titre_produit', true));
        $this->assertSame('Super article', get_post_meta($this->post_id, 'description_produit', true));
        $this->assertSame('29', get_post_meta($this->post_id, 'prix_produit', true));
    }

    public function testSaveOverwritesExistingMeta(): void
    {
        update_post_meta($this->post_id, 'note', 'ancienne valeur');

        $box = $this->buildMetaBox([
            ['type' => 'text', 'id' => 'note', 'label' => 'Note'],
        ]);

        $this->postWith(['note' => 'nouvelle valeur']);
        $box->savePost($this->post_id);

        $this->assertSame('nouvelle valeur', get_post_meta($this->post_id, 'note', true));
    }

    // -------------------------------------------------------------------------
    // Nonce et sécurité
    // -------------------------------------------------------------------------

    public function testSaveIsSkippedWithoutNonce(): void
    {
        $box = $this->buildMetaBox([
            ['type' => 'text', 'id' => 'champ_protege', 'label' => 'Champ'],
        ]);

        $_POST['cfdev'] = ['champ_protege' => 'valeur'];
        // Pas de cfdev_nonce

        $box->savePost($this->post_id);

        $this->assertSame('', get_post_meta($this->post_id, 'champ_protege', true));
    }

    public function testSaveIsSkippedWithInvalidNonce(): void
    {
        $box = $this->buildMetaBox([
            ['type' => 'text', 'id' => 'champ_nonce', 'label' => 'Champ'],
        ]);

        $_POST['cfdev_nonce'] = 'nonce_invalide';
        $_POST['cfdev']       = ['champ_nonce' => 'valeur'];

        $box->savePost($this->post_id);

        $this->assertSame('', get_post_meta($this->post_id, 'champ_nonce', true));
    }

    public function testSaveIsSkippedForWrongPostType(): void
    {
        $box = $this->buildMetaBox([
            ['type' => 'text', 'id' => 'champ_type', 'label' => 'Champ'],
        ]);

        $page_id = static::factory()->post->create(['post_type' => 'page']);

        $this->postWith(['champ_type' => 'valeur']);
        $box->savePost($page_id);

        $this->assertSame('', get_post_meta($page_id, 'champ_type', true));
    }

    public function testSaveIsSkippedForSubscriber(): void
    {
        $subscriber_id = static::factory()->user->create(['role' => 'subscriber']);
        wp_set_current_user($subscriber_id);

        $box = $this->buildMetaBox([
            ['type' => 'text', 'id' => 'champ_perm', 'label' => 'Champ'],
        ]);

        $this->postWith(['champ_perm' => 'valeur']);
        $box->savePost($this->post_id);

        $this->assertSame('', get_post_meta($this->post_id, 'champ_perm', true));
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function testRequiredFieldEmptyCreatesErrorbagTransient(): void
    {
        $box = $this->buildMetaBox([
            ['type' => 'text', 'id' => 'champ_requis', 'label' => 'Requis', 'required' => true],
        ]);

        $this->postWith(['champ_requis' => '']);
        $box->savePost($this->post_id);

        $errors = \Weblitzer\CFDev\Validation\ErrorBag::peek('post', $this->post_id);
        $this->assertArrayHasKey('champ_requis', $errors);
    }

    public function testValidRequiredFieldHasNoErrorbagEntry(): void
    {
        $box = $this->buildMetaBox([
            ['type' => 'text', 'id' => 'champ_valide', 'label' => 'Valide', 'required' => true],
        ]);

        $this->postWith(['champ_valide' => 'Contenu valide']);
        $box->savePost($this->post_id);

        $errors = \Weblitzer\CFDev\Validation\ErrorBag::peek('post', $this->post_id);
        $this->assertArrayNotHasKey('champ_valide', $errors);
    }
}
