<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie que TermMeta::saveTerm() persiste correctement les Bundle en JSON.
 * Le bundle ID est toujours préfixé par '_' via Bundle::buildId().
 */
class TermMetaBundleTest extends IntegrationTestCase
{
    private int $term_id;

    public function setUp(): void
    {
        parent::setUp();

        register_cfdev_taxonomy(['style', 'styles'], 'post', ['public' => true]);
        do_action('init');

        Registry::reset();

        $result = wp_insert_term('Impressionnisme', 'style');
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

    /** @param array<string, mixed> $values */
    private function postWith(array $values): void
    {
        $_POST['cfdev_nonce'] = wp_create_nonce('cfdev_meta');
        $_POST['cfdev']       = array_merge(['__activate' => ''], $values);
    }

    // -------------------------------------------------------------------------
    // Bundle direct
    // -------------------------------------------------------------------------

    public function testBundleIsSavedAsJsonInTermMeta(): void
    {
        $tm = new TermMeta('style', 'Style', [
            'bundle', 'artistes', [
                ['type' => 'text', 'id' => 'nom',    'label' => 'Nom'],
                ['type' => 'text', 'id' => 'epoque', 'label' => 'Époque'],
            ],
        ]);

        $this->postWith([
            '_artistes' => [
                0 => ['nom' => 'Monet',  'epoque' => '1840-1926'],
                1 => ['nom' => 'Renoir', 'epoque' => '1841-1919'],
            ],
        ]);
        $tm->saveTerm($this->term_id);

        $raw = get_term_meta($this->term_id, '_artistes', true);
        $this->assertNotEmpty($raw);

        $decoded = json_decode($raw, true);
        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
    }

    public function testBundleValuesAreRetrievableAfterSave(): void
    {
        $tm = new TermMeta('style', 'Style', [
            'bundle', 'caracteristiques', [
                ['type' => 'text', 'id' => 'technique', 'label' => 'Technique'],
                ['type' => 'text', 'id' => 'periode',   'label' => 'Période'],
            ],
        ]);

        $this->postWith([
            '_caracteristiques' => [
                0 => ['technique' => 'Peinture à l\'huile', 'periode' => 'XIXe'],
            ],
        ]);
        $tm->saveTerm($this->term_id);

        $decoded = json_decode(get_term_meta($this->term_id, '_caracteristiques', true), true);

        $this->assertSame('Peinture à l\'huile', $decoded[0]['technique']);
        $this->assertSame('XIXe', $decoded[0]['periode']);
    }

    public function testBundleSaveOverwritesExistingData(): void
    {
        $tm = new TermMeta('style', 'Style', [
            'bundle', 'exemples', [
                ['type' => 'text', 'id' => 'oeuvre', 'label' => 'Œuvre'],
            ],
        ]);

        $this->postWith(['_exemples' => [0 => ['oeuvre' => 'Nymphéas']]]);
        $tm->saveTerm($this->term_id);

        $this->postWith(['_exemples' => [0 => ['oeuvre' => 'Le Déjeuner sur l\'herbe']]]);
        $tm->saveTerm($this->term_id);

        $decoded = json_decode(get_term_meta($this->term_id, '_exemples', true), true);

        $this->assertCount(1, $decoded);
        $this->assertSame('Le Déjeuner sur l\'herbe', $decoded[0]['oeuvre']);
    }

    public function testBundleSaveSkippedWithoutNonce(): void
    {
        $tm = new TermMeta('style', 'Style', [
            'bundle', 'refs', [
                ['type' => 'text', 'id' => 'lien', 'label' => 'Lien'],
            ],
        ]);

        $_POST['cfdev'] = ['_refs' => [0 => ['lien' => 'https://example.com']]];

        $tm->saveTerm($this->term_id);

        $this->assertSame('', get_term_meta($this->term_id, '_refs', true));
    }

    // -------------------------------------------------------------------------
    // Bundle dans un onglet Tabs
    // -------------------------------------------------------------------------

    public function testBundleInsideTabsIsSaved(): void
    {
        $tm = new TermMeta('style', 'Style', [
            'tabs', [
                'Œuvres' => [
                    ['bundle', 'oeuvres_tab', [
                        ['type' => 'text', 'id' => 'titre_oeuvre', 'label' => 'Titre'],
                        ['type' => 'text', 'id' => 'annee_oeuvre', 'label' => 'Année'],
                    ]],
                ],
            ],
        ]);

        $this->postWith([
            '_oeuvres_tab' => [
                0 => ['titre_oeuvre' => 'Impression, soleil levant', 'annee_oeuvre' => '1872'],
                1 => ['titre_oeuvre' => 'La Grenouillère',           'annee_oeuvre' => '1869'],
            ],
        ]);
        $tm->saveTerm($this->term_id);

        $decoded = json_decode(get_term_meta($this->term_id, '_oeuvres_tab', true), true);

        $this->assertCount(2, $decoded);
        $this->assertSame('Impression, soleil levant', $decoded[0]['titre_oeuvre']);
        $this->assertSame('La Grenouillère', $decoded[1]['titre_oeuvre']);
    }
}
