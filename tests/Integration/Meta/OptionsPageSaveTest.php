<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use ReflectionMethod;
use Weblitzer\CFDev\OptionsPage;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;
use Weblitzer\CFDev\Validation\ErrorBag;

/**
 * Vérifie que OptionsPage::save() persiste les valeurs dans wp_options
 * et que la validation crée les entrées ErrorBag attendues.
 *
 * Les tests de sauvegarde appellent save() directement — même pattern
 * que MetaBoxSaveTest appelle savePost() directement.
 * Les tests d'autorisation appellent saveOptions() et attendent WPDieException.
 * La validation est exercée via reflection sur validateFields().
 */
class OptionsPageSaveTest extends IntegrationTestCase
{
    private int $admin_id;

    public function setUp(): void
    {
        parent::setUp();
        Registry::reset();

        $this->admin_id = static::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_id);
    }

    public function tearDown(): void
    {
        $_POST = [];
        Registry::reset();
        parent::tearDown();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** @param array<mixed> $fields */
    private function buildPage(string $id, array $fields): OptionsPage
    {
        return new OptionsPage($id, ucfirst(str_replace('_', ' ', $id)), $fields);
    }

    // ─── Champs plats ─────────────────────────────────────────────────────────

    public function testFlatTextFieldIsSavedInWpOptions(): void
    {
        $page = $this->buildPage('site_info', [
            ['type' => 'text', 'id' => 'site_tagline_opt', 'label' => 'Tagline'],
        ]);

        $page->save(0, ['site_tagline_opt' => 'Just another WP site']);

        $this->assertSame('Just another WP site', get_option('site_tagline_opt'));
    }

    public function testMultipleFlatFieldsAreSaved(): void
    {
        $page = $this->buildPage('contact', [
            ['type' => 'text',  'id' => 'contact_email_opt', 'label' => 'Email'],
            ['type' => 'text',  'id' => 'contact_phone_opt', 'label' => 'Téléphone'],
        ]);

        $page->save(0, [
            'contact_email_opt' => 'hello@example.com',
            'contact_phone_opt' => '+33 1 23 45 67 89',
        ]);

        $this->assertSame('hello@example.com', get_option('contact_email_opt'));
        $this->assertSame('+33 1 23 45 67 89', get_option('contact_phone_opt'));
    }

    public function testSaveOverwritesExistingOption(): void
    {
        update_option('page_title_opt', 'ancien titre');

        $page = $this->buildPage('seo', [
            ['type' => 'text', 'id' => 'page_title_opt', 'label' => 'Titre'],
        ]);

        $page->save(0, ['page_title_opt' => 'nouveau titre']);

        $this->assertSame('nouveau titre', get_option('page_title_opt'));
    }

    public function testMissingValueSavesEmptyString(): void
    {
        update_option('meta_desc_opt', 'ancienne description');

        $page = $this->buildPage('seo', [
            ['type' => 'text', 'id' => 'meta_desc_opt', 'label' => 'Description'],
        ]);

        $page->save(0, []); // clé absente → chaîne vide

        $this->assertSame('', get_option('meta_desc_opt'));
    }

    // ─── Bundle ───────────────────────────────────────────────────────────────

    public function testBundleDataIsSavedAsJsonInWpOptions(): void
    {
        $page = $this->buildPage('team', [
            'bundle', 'team_members', [
                ['type' => 'text', 'id' => 'member_name',  'label' => 'Nom'],
                ['type' => 'text', 'id' => 'member_role',  'label' => 'Rôle'],
            ],
        ]);

        // Bundle::buildId() préfixe avec '_'
        $page->save(0, [
            '_team_members' => [
                0 => ['member_name' => 'Alice', 'member_role' => 'Dev'],
                1 => ['member_name' => 'Bob',   'member_role' => 'Design'],
            ],
        ]);

        $raw = get_option('_team_members');
        $this->assertNotEmpty($raw);
        $this->assertJson($raw);
    }

    public function testBundleRowsAreRetrievableAfterDecode(): void
    {
        $page = $this->buildPage('faq', [
            'bundle', 'faq_items', [
                ['type' => 'text', 'id' => 'faq_question', 'label' => 'Question'],
                ['type' => 'text', 'id' => 'faq_answer',   'label' => 'Réponse'],
            ],
        ]);

        $page->save(0, [
            '_faq_items' => [
                0 => ['faq_question' => 'Qu\'est-ce que CFDev ?', 'faq_answer' => 'Un plugin WP.'],
            ],
        ]);

        $decoded = \Weblitzer\CFDev\Field::decodeMetaValue(get_option('_faq_items'));

        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertSame('Qu\'est-ce que CFDev ?', $decoded[0]['faq_question']);
        $this->assertSame('Un plugin WP.', $decoded[0]['faq_answer']);
    }

    public function testBundleSaveIsSkippedWhenBundleKeyAbsent(): void
    {
        $page = $this->buildPage('slides', [
            'bundle', 'slide_items', [
                ['type' => 'text', 'id' => 'slide_title', 'label' => 'Titre'],
            ],
        ]);

        $page->save(0, []); // clé bundle absente → pas de sauvegarde

        $this->assertFalse(get_option('_slide_items'));
    }

    // ─── Tabs — champs plats + bundle ─────────────────────────────────────────

    public function testTabsLayoutSavesFlatFieldsAndBundleSeparately(): void
    {
        $page = $this->buildPage('config', [
            'tabs',
            [
                'Général'  => [
                    ['type' => 'text', 'id' => 'config_site_name', 'label' => 'Nom du site'],
                ],
                'Services' => [
                    ['bundle', 'config_services', [
                        ['type' => 'text', 'id' => 'service_title', 'label' => 'Titre'],
                    ]],
                ],
            ],
        ]);

        $page->save(0, [
            'config_site_name' => 'Mon Site',
            '_config_services' => [
                0 => ['service_title' => 'Développement web'],
            ],
        ]);

        $this->assertSame('Mon Site', get_option('config_site_name'));

        $decoded = \Weblitzer\CFDev\Field::decodeMetaValue(get_option('_config_services'));
        $this->assertCount(1, $decoded);
        $this->assertSame('Développement web', $decoded[0]['service_title']);
    }

    // ─── saveOptions() — gardes auth et nonce ─────────────────────────────────

    public function testSaveOptionsWpDiesForSubscriber(): void
    {
        $subscriber_id = static::factory()->user->create(['role' => 'subscriber']);
        wp_set_current_user($subscriber_id);

        $page = $this->buildPage('secured', [
            ['type' => 'text', 'id' => 'secure_opt', 'label' => 'Option'],
        ]);

        $_POST['cfdev_options_nonce'] = wp_create_nonce('cfdev_options_secured');
        $_POST['cfdev'] = ['secure_opt' => 'valeur'];

        $this->expectException(\WPDieException::class);
        $page->saveOptions();
    }

    public function testSaveOptionsWpDiesForInvalidNonce(): void
    {
        $page = $this->buildPage('secured', [
            ['type' => 'text', 'id' => 'secure_opt2', 'label' => 'Option'],
        ]);

        $_POST['cfdev_options_nonce'] = 'nonce_invalide';
        $_POST['cfdev'] = ['secure_opt2' => 'valeur'];

        $this->expectException(\WPDieException::class);
        $page->saveOptions();
    }

    public function testSaveOptionsWpDiesWhenNonceIsMissing(): void
    {
        $page = $this->buildPage('secured', [
            ['type' => 'text', 'id' => 'secure_opt3', 'label' => 'Option'],
        ]);

        $_POST['cfdev'] = ['secure_opt3' => 'valeur']; // pas de nonce

        $this->expectException(\WPDieException::class);
        $page->saveOptions();
    }

    // ─── Validation via reflection ────────────────────────────────────────────

    public function testRequiredFieldEmptyReturnsValidationError(): void
    {
        $page = $this->buildPage('config', [
            ['type' => 'text', 'id' => 'req_opt_field', 'label' => 'Requis', 'required' => true],
        ]);

        $m      = new ReflectionMethod($page, 'validateFields');
        $errors = $m->invoke($page, ['req_opt_field' => '']);

        $this->assertArrayHasKey('req_opt_field', $errors);
    }

    public function testRequiredFieldFilledHasNoError(): void
    {
        $page = $this->buildPage('config', [
            ['type' => 'text', 'id' => 'req_opt_full', 'label' => 'Requis', 'required' => true],
        ]);

        $m      = new ReflectionMethod($page, 'validateFields');
        $errors = $m->invoke($page, ['req_opt_full' => 'Une valeur']);

        $this->assertArrayNotHasKey('req_opt_full', $errors);
    }

    public function testBundleRequiredFieldReturnsValidationError(): void
    {
        $page = $this->buildPage('slides', [
            'bundle', 'val_slides', [
                ['type' => 'text', 'id' => 'slide_req', 'label' => 'Titre', 'required' => true],
            ],
        ]);

        $m      = new ReflectionMethod($page, 'validateFields');
        $errors = $m->invoke($page, [
            '_val_slides' => [
                0 => ['slide_req' => ''],
            ],
        ]);

        // Clé dot notation bundle.row.field
        $this->assertNotEmpty($errors);
        $first_key = array_key_first($errors);
        $this->assertStringContainsString('slide_req', $first_key);
    }

    // ─── ErrorBag — cycle complet pour meta_type option ──────────────────────

    public function testErrorBagPushAndPeekForOptionType(): void
    {
        $errors = ['api_key_opt' => ['label' => 'API Key', 'errors' => ['Champ requis.']]];

        ErrorBag::push('option', 0, $errors);
        $result = ErrorBag::peek('option', 0);

        $this->assertArrayHasKey('api_key_opt', $result);
    }

    public function testErrorBagLoadAndForFieldForOptionType(): void
    {
        $errors = ['api_key_loaded' => ['label' => 'API Key', 'errors' => ['Format invalide.']]];

        ErrorBag::push('option', 0, $errors);
        ErrorBag::load('option', 0);

        $fieldErrors = ErrorBag::forField('api_key_loaded');
        $this->assertSame(['Format invalide.'], $fieldErrors);
    }

    public function testErrorBagOptionIsolatedFromPost(): void
    {
        ErrorBag::push('option', 0, ['f' => ['label' => 'F', 'errors' => ['option_err']]]);
        ErrorBag::push('post', 1, ['f' => ['label' => 'F', 'errors' => ['post_err']]]);

        $this->assertSame('option_err', ErrorBag::peek('option', 0)['f']['errors'][0]);
        $this->assertSame('post_err', ErrorBag::peek('post', 1)['f']['errors'][0]);
    }
}
