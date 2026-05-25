<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use Weblitzer\CFDev\Meta\UserMeta;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie que UserMeta::saveUser() persiste correctement les Bundle en JSON.
 */
class UserMetaBundleTest extends IntegrationTestCase
{
    private int $user_id;

    public function setUp(): void
    {
        parent::setUp();

        do_action('init');
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

    /** @param array<string, mixed> $values */
    private function postWith(array $values): void
    {
        $_POST['cfdev_nonce'] = wp_create_nonce('cfdev_meta');
        $_POST['cfdev']       = array_merge(['__activate' => ''], $values);
    }

    // -------------------------------------------------------------------------
    // Bundle direct
    // -------------------------------------------------------------------------

    public function testBundleIsSavedAsJsonInUserMeta(): void
    {
        $um = new UserMeta('profil', 'Profil', [
            'bundle', 'diplomes', [
                ['type' => 'text', 'id' => 'intitule', 'label' => 'Intitulé'],
                ['type' => 'text', 'id' => 'annee_dip', 'label' => 'Année'],
            ],
        ]);

        $this->postWith([
            '_diplomes' => [
                0 => ['intitule' => 'Master informatique', 'annee_dip' => '2020'],
                1 => ['intitule' => 'Licence web',         'annee_dip' => '2018'],
            ],
        ]);
        $um->saveUser($this->user_id);

        $raw = get_user_meta($this->user_id, '_diplomes', true);
        $this->assertNotEmpty($raw);

        $decoded = json_decode($raw, true);
        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
    }

    public function testBundleValuesAreRetrievableAfterSave(): void
    {
        $um = new UserMeta('profil', 'Profil', [
            'bundle', 'experiences', [
                ['type' => 'text', 'id' => 'entreprise', 'label' => 'Entreprise'],
                ['type' => 'text', 'id' => 'poste_exp',  'label' => 'Poste'],
            ],
        ]);

        $this->postWith([
            '_experiences' => [
                0 => ['entreprise' => 'Acme Corp', 'poste_exp' => 'Dev senior'],
            ],
        ]);
        $um->saveUser($this->user_id);

        $decoded = json_decode(get_user_meta($this->user_id, '_experiences', true), true);

        $this->assertSame('Acme Corp', $decoded[0]['entreprise']);
        $this->assertSame('Dev senior', $decoded[0]['poste_exp']);
    }

    public function testBundleSaveOverwritesExistingData(): void
    {
        $um = new UserMeta('profil', 'Profil', [
            'bundle', 'langues', [
                ['type' => 'text', 'id' => 'langue', 'label' => 'Langue'],
            ],
        ]);

        $this->postWith(['_langues' => [0 => ['langue' => 'Français'], 1 => ['langue' => 'Anglais']]]);
        $um->saveUser($this->user_id);

        $this->postWith(['_langues' => [0 => ['langue' => 'Espagnol']]]);
        $um->saveUser($this->user_id);

        $decoded = json_decode(get_user_meta($this->user_id, '_langues', true), true);

        $this->assertCount(1, $decoded);
        $this->assertSame('Espagnol', $decoded[0]['langue']);
    }

    public function testBundleSaveSkippedWithoutNonce(): void
    {
        $um = new UserMeta('profil', 'Profil', [
            'bundle', 'certifs', [
                ['type' => 'text', 'id' => 'nom_certif', 'label' => 'Certif'],
            ],
        ]);

        $_POST['cfdev'] = ['_certifs' => [0 => ['nom_certif' => 'AWS']]];

        $um->saveUser($this->user_id);

        $this->assertSame('', get_user_meta($this->user_id, '_certifs', true));
    }

    // -------------------------------------------------------------------------
    // Bundle dans un onglet Tabs
    // -------------------------------------------------------------------------

    public function testBundleInsideTabsIsSaved(): void
    {
        $um = new UserMeta('profil', 'Profil', [
            'tabs', [
                'Compétences' => [
                    ['bundle', 'competences_tab', [
                        ['type' => 'text', 'id' => 'techno',    'label' => 'Techno'],
                        ['type' => 'text', 'id' => 'niveau_cp', 'label' => 'Niveau'],
                    ]],
                ],
            ],
        ]);

        $this->postWith([
            '_competences_tab' => [
                0 => ['techno' => 'PHP',        'niveau_cp' => 'Expert'],
                1 => ['techno' => 'JavaScript', 'niveau_cp' => 'Avancé'],
            ],
        ]);
        $um->saveUser($this->user_id);

        $decoded = json_decode(get_user_meta($this->user_id, '_competences_tab', true), true);

        $this->assertCount(2, $decoded);
        $this->assertSame('PHP', $decoded[0]['techno']);
        $this->assertSame('JavaScript', $decoded[1]['techno']);
    }
}
