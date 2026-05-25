<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie que MetaBox::savePost() persiste correctement les données Bundle
 * (répéteur de lignes) en JSON dans post_meta.
 */
class MetaBoxBundleTest extends IntegrationTestCase
{
    private int $admin_id;
    private int $post_id;

    public function setUp(): void
    {
        parent::setUp();

        $this->admin_id = static::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_id);

        register_cfdev_post_type(['programme', 'programmes'], ['public' => true]);
        do_action('init');

        Registry::reset();

        $this->post_id = static::factory()->post->create(['post_type' => 'programme']);
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
    // Sauvegarde bundle
    // -------------------------------------------------------------------------

    public function testBundleDataIsSavedAsJsonInPostMeta(): void
    {
        $box = new MetaBox('planning', 'Planning', 'programme', [
            'bundle', 'seances', [
                ['type' => 'text', 'id' => 'lieu',  'label' => 'Lieu'],
                ['type' => 'text', 'id' => 'heure', 'label' => 'Heure'],
            ],
        ]);

        // Bundle::buildId() prepends '_', so the POST key and meta key are '_seances'
        $this->postWith([
            '_seances' => [
                0 => ['lieu' => 'Salle A', 'heure' => '10h00'],
                1 => ['lieu' => 'Salle B', 'heure' => '14h00'],
            ],
        ]);
        $box->savePost($this->post_id);

        $raw = get_post_meta($this->post_id, '_seances', true);
        $this->assertNotEmpty($raw);

        $decoded = json_decode($raw, true);
        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
    }

    public function testBundleValuesAreRetrievableAfterSave(): void
    {
        $box = new MetaBox('equipe', 'Équipe', 'programme', [
            'bundle', 'membres', [
                ['type' => 'text', 'id' => 'nom',   'label' => 'Nom'],
                ['type' => 'text', 'id' => 'poste', 'label' => 'Poste'],
            ],
        ]);

        $rows = [
            0 => ['nom' => 'Alice', 'poste' => 'Directrice'],
            1 => ['nom' => 'Bob',   'poste' => 'Développeur'],
        ];

        $this->postWith(['_membres' => $rows]);
        $box->savePost($this->post_id);

        $raw     = get_post_meta($this->post_id, '_membres', true);
        $decoded = json_decode($raw, true);

        $this->assertSame('Alice', $decoded[0]['nom']);
        $this->assertSame('Directrice', $decoded[0]['poste']);
        $this->assertSame('Bob', $decoded[1]['nom']);
    }

    public function testBundleSaveOverwritesExistingData(): void
    {
        $box = new MetaBox('conf', 'Conférences', 'programme', [
            'bundle', 'conferences', [
                ['type' => 'text', 'id' => 'sujet', 'label' => 'Sujet'],
            ],
        ]);

        $this->postWith(['_conferences' => [0 => ['sujet' => 'PHP']]]);
        $box->savePost($this->post_id);

        $this->postWith(['_conferences' => [0 => ['sujet' => 'Laravel']]]);
        $box->savePost($this->post_id);

        $decoded = json_decode(get_post_meta($this->post_id, '_conferences', true), true);

        $this->assertCount(1, $decoded);
        $this->assertSame('Laravel', $decoded[0]['sujet']);
    }

    public function testEmptyBundleSavesEmptyJson(): void
    {
        $box = new MetaBox('vide', 'Vide', 'programme', [
            'bundle', 'sessions', [
                ['type' => 'text', 'id' => 'titre_session', 'label' => 'Titre'],
            ],
        ]);

        $this->postWith(['_sessions' => []]);
        $box->savePost($this->post_id);

        $raw = get_post_meta($this->post_id, '_sessions', true);
        // Soit vide, soit JSON tableau vide
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            $this->assertIsArray($decoded);
            $this->assertEmpty($decoded);
        } else {
            $this->assertSame('', $raw);
        }
    }
}
