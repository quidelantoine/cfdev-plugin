<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use Weblitzer\CFDev\Meta\UserMeta;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie que UserMeta::saveUser() persiste les valeurs en base via update_user_meta().
 */
class UserMetaSaveTest extends IntegrationTestCase
{
    private int $user_id;

    public function setUp(): void
    {
        parent::setUp();
        Registry::reset();

        $this->user_id = static::factory()->user->create(['role' => 'editor']);
        wp_set_current_user($this->user_id);
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
    private function buildUserMeta(array $fields): UserMeta
    {
        return new UserMeta('profil', 'Profil étendu', $fields);
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

    public function testTextFieldIsSavedInUserMeta(): void
    {
        $um = $this->buildUserMeta([
            ['type' => 'text', 'id' => 'pseudo', 'label' => 'Pseudo'],
        ]);

        $this->postWith(['pseudo' => 'john_doe']);
        $um->saveUser($this->user_id);

        $this->assertSame('john_doe', get_user_meta($this->user_id, 'pseudo', true));
    }

    public function testMultipleFieldsAreAllSaved(): void
    {
        $um = $this->buildUserMeta([
            ['type' => 'text', 'id' => 'ville',  'label' => 'Ville'],
            ['type' => 'text', 'id' => 'twitter', 'label' => 'Twitter'],
        ]);

        $this->postWith([
            'ville'   => 'Paris',
            'twitter' => '@johndoe',
        ]);
        $um->saveUser($this->user_id);

        $this->assertSame('Paris', get_user_meta($this->user_id, 'ville', true));
        $this->assertSame('@johndoe', get_user_meta($this->user_id, 'twitter', true));
    }

    public function testSaveIsSkippedWithoutNonce(): void
    {
        $um = $this->buildUserMeta([
            ['type' => 'text', 'id' => 'bio', 'label' => 'Bio'],
        ]);

        $_POST['cfdev'] = ['bio' => 'contenu'];

        $um->saveUser($this->user_id);

        $this->assertSame('', get_user_meta($this->user_id, 'bio', true));
    }

    public function testSaveOverwritesExistingUserMeta(): void
    {
        update_user_meta($this->user_id, 'poste', 'Stagiaire');

        $um = $this->buildUserMeta([
            ['type' => 'text', 'id' => 'poste', 'label' => 'Poste'],
        ]);

        $this->postWith(['poste' => 'Développeur senior']);
        $um->saveUser($this->user_id);

        $this->assertSame('Développeur senior', get_user_meta($this->user_id, 'poste', true));
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function testRequiredFieldEmptyPushesErrorbag(): void
    {
        $um = $this->buildUserMeta([
            ['type' => 'text', 'id' => 'nom_display', 'label' => 'Nom affiché', 'required' => true],
        ]);

        $this->postWith(['nom_display' => '']);
        $um->saveUser($this->user_id);

        $errors = \Weblitzer\CFDev\Validation\ErrorBag::peek('user', $this->user_id);
        $this->assertArrayHasKey('nom_display', $errors);
    }

    public function testSaveIsSkippedWithInvalidNonce(): void
    {
        $um = $this->buildUserMeta([
            ['type' => 'text', 'id' => 'champ_nonce_user', 'label' => 'Champ'],
        ]);

        $_POST['cfdev_nonce'] = 'nonce_invalide';
        $_POST['cfdev']       = ['champ_nonce_user' => 'valeur'];

        $um->saveUser($this->user_id);

        $this->assertSame('', get_user_meta($this->user_id, 'champ_nonce_user', true));
    }
}
