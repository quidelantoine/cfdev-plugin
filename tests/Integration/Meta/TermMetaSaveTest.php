<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie que TermMeta::saveTerm() persiste les valeurs en base via update_term_meta().
 */
class TermMetaSaveTest extends IntegrationTestCase
{
    private int $term_id;

    public function setUp(): void
    {
        parent::setUp();
        Registry::reset();

        // Enregistre la taxonomie et crée un terme de test
        register_cfdev_taxonomy(['marque', 'marques'], 'post', ['public' => true]);
        do_action('init');

        $result = wp_insert_term('Apple', 'marque');
        if (is_wp_error($result)) {
            throw new \RuntimeException($result->get_error_message()); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
        $this->term_id = $result['term_id'];
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
    private function buildTermMeta(array $fields): TermMeta
    {
        return new TermMeta('marque', 'Détails marque', $fields);
    }

    /** @param array<string, mixed> $cfdev_values */
    private function postWith(array $cfdev_values): void
    {
        $_POST['cfdev_nonce'] = wp_create_nonce('cfdev_meta');
        $_POST['cfdev']       = array_merge(['__activate' => ''], $cfdev_values);
    }

    // -------------------------------------------------------------------------
    // Sauvegarde
    // -------------------------------------------------------------------------

    public function testTextFieldIsSavedInTermMeta(): void
    {
        $tm = $this->buildTermMeta([
            ['type' => 'text', 'id' => 'slogan', 'label' => 'Slogan'],
        ]);

        $this->postWith(['slogan' => 'Think Different']);
        $tm->saveTerm($this->term_id);

        $this->assertSame('Think Different', get_term_meta($this->term_id, 'slogan', true));
    }

    public function testMultipleFieldsAreAllSaved(): void
    {
        $tm = $this->buildTermMeta([
            ['type' => 'text', 'id' => 'pays_origine',  'label' => 'Pays'],
            ['type' => 'text', 'id' => 'annee_creation', 'label' => 'Année'],
        ]);

        $this->postWith([
            'pays_origine'   => 'USA',
            'annee_creation' => '1976',
        ]);
        $tm->saveTerm($this->term_id);

        $this->assertSame('USA', get_term_meta($this->term_id, 'pays_origine', true));
        $this->assertSame('1976', get_term_meta($this->term_id, 'annee_creation', true));
    }

    public function testSaveIsSkippedWithoutNonce(): void
    {
        $tm = $this->buildTermMeta([
            ['type' => 'text', 'id' => 'champ_protege', 'label' => 'Champ'],
        ]);

        $_POST['cfdev'] = ['champ_protege' => 'valeur'];

        $tm->saveTerm($this->term_id);

        $this->assertSame('', get_term_meta($this->term_id, 'champ_protege', true));
    }

    public function testSaveOverwritesExistingTermMeta(): void
    {
        update_term_meta($this->term_id, 'note', 'ancienne');

        $tm = $this->buildTermMeta([
            ['type' => 'text', 'id' => 'note', 'label' => 'Note'],
        ]);

        $this->postWith(['note' => 'nouvelle']);
        $tm->saveTerm($this->term_id);

        $this->assertSame('nouvelle', get_term_meta($this->term_id, 'note', true));
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function testRequiredFieldEmptyPushesErrorbag(): void
    {
        $tm = $this->buildTermMeta([
            ['type' => 'text', 'id' => 'champ_requis', 'label' => 'Requis', 'required' => true],
        ]);

        $this->postWith(['champ_requis' => '']);
        $tm->saveTerm($this->term_id);

        $errors = \Weblitzer\CFDev\Validation\ErrorBag::peek('term', $this->term_id);
        $this->assertArrayHasKey('champ_requis', $errors);
    }

    public function testSaveIsSkippedWithInvalidNonce(): void
    {
        $tm = $this->buildTermMeta([
            ['type' => 'text', 'id' => 'champ_nonce_term', 'label' => 'Champ'],
        ]);

        $_POST['cfdev_nonce'] = 'nonce_invalide';
        $_POST['cfdev']       = ['champ_nonce_term' => 'valeur'];

        $tm->saveTerm($this->term_id);

        $this->assertSame('', get_term_meta($this->term_id, 'champ_nonce_term', true));
    }
}
