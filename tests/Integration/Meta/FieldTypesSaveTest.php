<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use Weblitzer\CFDev\Field;
use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie que chaque type de champ (select, checkbox, toggle, number,
 * checkboxes, wysiwyg, textarea, email, url) se sauvegarde correctement
 * en post meta via MetaBox::savePost().
 *
 * Règles générales :
 *  - checkbox/toggle non cochée → '-1'
 *  - checkboxes vide → '-1' (tableau vide → chaîne sentinelle)
 *  - number → chaîne numérique
 *  - wysiwyg → HTML filtré (wp_kses_post)
 *  - select/text/textarea/email/url → chaîne brute
 */
class FieldTypesSaveTest extends IntegrationTestCase
{
    private int $admin_id;
    private int $post_id;

    public function setUp(): void
    {
        parent::setUp();
        Registry::reset();

        $this->admin_id = static::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_id);

        register_cfdev_post_type(['typtest', 'typtests'], ['public' => true]);
        do_action('init');

        $this->post_id = static::factory()->post->create(['post_type' => 'typtest']);
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
    // Select
    // -------------------------------------------------------------------------

    public function testSelectFieldSavesChosenOption(): void
    {
        $box = new MetaBox('info_typ', 'Info', 'typtest', [
            ['type' => 'select', 'id' => 'couleur', 'label' => 'Couleur', 'options' => ['rouge' => 'Rouge', 'bleu' => 'Bleu']],
        ]);

        $this->postWith(['couleur' => 'bleu']);
        $box->savePost($this->post_id);

        $this->assertSame('bleu', get_post_meta($this->post_id, 'couleur', true));
    }

    // -------------------------------------------------------------------------
    // Checkbox (single) — checked vs unchecked
    // -------------------------------------------------------------------------

    public function testCheckboxSavesValueWhenChecked(): void
    {
        $box = new MetaBox('info_typ', 'Info', 'typtest', [
            ['type' => 'checkbox', 'id' => 'accepte', 'label' => 'Accepte'],
        ]);

        $this->postWith(['accepte' => '1']);
        $box->savePost($this->post_id);

        $this->assertSame('1', get_post_meta($this->post_id, 'accepte', true));
    }

    public function testCheckboxSavesSentinelWhenUnchecked(): void
    {
        $box = new MetaBox('info_typ', 'Info', 'typtest', [
            ['type' => 'checkbox', 'id' => 'accepte_off', 'label' => 'Accepte'],
        ]);

        $this->postWith([]); // champ absent du POST = non coché
        $box->savePost($this->post_id);

        $this->assertSame('-1', get_post_meta($this->post_id, 'accepte_off', true));
    }

    // -------------------------------------------------------------------------
    // Toggle — same sentinel logic as Checkbox
    // -------------------------------------------------------------------------

    public function testToggleSavesValueWhenOn(): void
    {
        $box = new MetaBox('info_typ', 'Info', 'typtest', [
            ['type' => 'toggle', 'id' => 'actif', 'label' => 'Actif'],
        ]);

        $this->postWith(['actif' => '1']);
        $box->savePost($this->post_id);

        $this->assertSame('1', get_post_meta($this->post_id, 'actif', true));
    }

    public function testToggleSavesSentinelWhenOff(): void
    {
        $box = new MetaBox('info_typ', 'Info', 'typtest', [
            ['type' => 'toggle', 'id' => 'actif_off', 'label' => 'Actif'],
        ]);

        $this->postWith([]);
        $box->savePost($this->post_id);

        $this->assertSame('-1', get_post_meta($this->post_id, 'actif_off', true));
    }

    // -------------------------------------------------------------------------
    // Checkboxes (multiple) — tableau de valeurs
    // -------------------------------------------------------------------------

    public function testCheckboxesSavesSelectedValuesAsJson(): void
    {
        $box = new MetaBox('info_typ', 'Info', 'typtest', [
            ['type' => 'checkboxes', 'id' => 'langues', 'label' => 'Langues', 'options' => ['fr' => 'Français', 'en' => 'Anglais', 'de' => 'Allemand']],
        ]);

        $this->postWith(['langues' => ['fr', 'en']]);
        $box->savePost($this->post_id);

        $decoded = Field::decodeMetaValue(get_post_meta($this->post_id, 'langues', true));
        $this->assertIsArray($decoded);
        $this->assertSame(['fr', 'en'], $decoded);
    }

    public function testCheckboxesSavesSentinelWhenNoneSelected(): void
    {
        $box = new MetaBox('info_typ', 'Info', 'typtest', [
            ['type' => 'checkboxes', 'id' => 'langues_off', 'label' => 'Langues', 'options' => ['fr' => 'Français']],
        ]);

        $this->postWith([]);
        $box->savePost($this->post_id);

        $this->assertSame('-1', get_post_meta($this->post_id, 'langues_off', true));
    }

    // -------------------------------------------------------------------------
    // Number
    // -------------------------------------------------------------------------

    public function testNumberFieldSavesNumericString(): void
    {
        $box = new MetaBox('info_typ', 'Info', 'typtest', [
            ['type' => 'number', 'id' => 'prix', 'label' => 'Prix'],
        ]);

        $this->postWith(['prix' => '42']);
        $box->savePost($this->post_id);

        $this->assertSame('42', get_post_meta($this->post_id, 'prix', true));
    }

    public function testNumberFieldSavesZero(): void
    {
        $box = new MetaBox('info_typ', 'Info', 'typtest', [
            ['type' => 'number', 'id' => 'stock', 'label' => 'Stock'],
        ]);

        $this->postWith(['stock' => '0']);
        $box->savePost($this->post_id);

        $this->assertSame('0', get_post_meta($this->post_id, 'stock', true));
    }

    // -------------------------------------------------------------------------
    // Textarea
    // -------------------------------------------------------------------------

    public function testTextareaFieldSavesMultilineText(): void
    {
        $box = new MetaBox('info_typ', 'Info', 'typtest', [
            ['type' => 'textarea', 'id' => 'notes', 'label' => 'Notes'],
        ]);

        $this->postWith(['notes' => "Ligne 1\nLigne 2"]);
        $box->savePost($this->post_id);

        $this->assertSame("Ligne 1\nLigne 2", get_post_meta($this->post_id, 'notes', true));
    }

    // -------------------------------------------------------------------------
    // Email
    // -------------------------------------------------------------------------

    public function testEmailFieldSavesEmailString(): void
    {
        $box = new MetaBox('info_typ', 'Info', 'typtest', [
            ['type' => 'email', 'id' => 'contact_email', 'label' => 'Email'],
        ]);

        $this->postWith(['contact_email' => 'test@example.com']);
        $box->savePost($this->post_id);

        $this->assertSame('test@example.com', get_post_meta($this->post_id, 'contact_email', true));
    }

    // -------------------------------------------------------------------------
    // URL
    // -------------------------------------------------------------------------

    public function testUrlFieldSavesUrlString(): void
    {
        $box = new MetaBox('info_typ', 'Info', 'typtest', [
            ['type' => 'url', 'id' => 'site_web', 'label' => 'Site'],
        ]);

        $this->postWith(['site_web' => 'https://example.com']);
        $box->savePost($this->post_id);

        $this->assertSame('https://example.com', get_post_meta($this->post_id, 'site_web', true));
    }

    // -------------------------------------------------------------------------
    // Wysiwyg — HTML autorisé conservé, scripts strippés
    // -------------------------------------------------------------------------

    public function testWysiwygFieldSavesHtmlContent(): void
    {
        $box = new MetaBox('info_typ', 'Info', 'typtest', [
            ['type' => 'wysiwyg', 'id' => 'contenu_riche', 'label' => 'Contenu'],
        ]);

        $this->postWith(['contenu_riche' => '<p>Bonjour <strong>monde</strong></p>']);
        $box->savePost($this->post_id);

        $saved = get_post_meta($this->post_id, 'contenu_riche', true);
        $this->assertStringContainsString('<p>', $saved);
        $this->assertStringContainsString('<strong>monde</strong>', $saved);
    }

    // -------------------------------------------------------------------------
    // Plusieurs types dans la même MetaBox
    // -------------------------------------------------------------------------

    public function testMixedFieldTypesInSameMetabox(): void
    {
        $box = new MetaBox('fiche_typ', 'Fiche', 'typtest', [
            ['type' => 'text',     'id' => 'titre_mix',   'label' => 'Titre'],
            ['type' => 'number',   'id' => 'ordre_mix',   'label' => 'Ordre'],
            ['type' => 'checkbox', 'id' => 'vedette_mix', 'label' => 'Vedette'],
            ['type' => 'select',   'id' => 'statut_mix',  'label' => 'Statut', 'options' => ['actif' => 'Actif', 'archive' => 'Archivé']],
        ]);

        $this->postWith([
            'titre_mix'   => 'Mon titre',
            'ordre_mix'   => '3',
            'vedette_mix' => '1',
            'statut_mix'  => 'actif',
        ]);
        $box->savePost($this->post_id);

        $this->assertSame('Mon titre', get_post_meta($this->post_id, 'titre_mix', true));
        $this->assertSame('3', get_post_meta($this->post_id, 'ordre_mix', true));
        $this->assertSame('1', get_post_meta($this->post_id, 'vedette_mix', true));
        $this->assertSame('actif', get_post_meta($this->post_id, 'statut_mix', true));
    }
}
