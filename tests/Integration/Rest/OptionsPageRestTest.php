<?php

namespace Weblitzer\CFDev\Tests\Integration\Rest;

use Weblitzer\CFDev\Admin\RestPage;
use Weblitzer\CFDev\OptionsPage;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Rest\CfdevRestApi;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;
use WP_REST_Request;

/**
 * Vérifie la registration REST des champs d'OptionsPage et le endpoint
 * GET /cfdev/v1/options/{page_id}.
 *
 * Deux surfaces REST :
 *   1. register_setting() → /wp/v2/settings (WP natif) — testé via
 *      get_registered_settings() et requête GET /wp/v2/settings.
 *   2. CfdevRestApi::handleOptions() → /cfdev/v1/options/{page_id} — endpoint
 *      custom qui lit get_option() et passe par CacheResolver.
 */
class OptionsPageRestTest extends IntegrationTestCase
{
    private int $admin_id;

    public function setUp(): void
    {
        parent::setUp();
        Registry::reset();

        global $wp_rest_server;
        $wp_rest_server = null;

        $this->admin_id = static::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_id);

        (new CfdevRestApi())->register();
        do_action('rest_api_init');
    }

    public function tearDown(): void
    {
        delete_option(RestPage::OPTION_REST);
        delete_option(RestPage::OPTION_API);
        Registry::reset();
        parent::tearDown();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** @param array<mixed> $fields */
    private function buildPage(string $id, array $fields): OptionsPage
    {
        return new OptionsPage($id, ucfirst(str_replace('_', ' ', $id)), $fields);
    }

    // ─── register_setting() via registerRestOptions() ────────────────────────

    public function testRestTrueFieldIsRegisteredInWpSettings(): void
    {
        $page = $this->buildPage('brand', [
            ['type' => 'text', 'id' => 'brand_color_rest', 'label' => 'Couleur', 'rest' => true],
        ]);

        $page->registerRestOptions();

        $settings = get_registered_settings();
        $this->assertArrayHasKey('brand_color_rest', $settings);
        $this->assertTrue($settings['brand_color_rest']['show_in_rest']);
    }

    public function testRestFalseFieldIsNotRegistered(): void
    {
        $page = $this->buildPage('brand', [
            ['type' => 'text', 'id' => 'brand_secret_norest', 'label' => 'Secret', 'rest' => false],
        ]);

        $page->registerRestOptions();

        $settings = get_registered_settings();
        $this->assertArrayNotHasKey('brand_secret_norest', $settings);
    }

    public function testOnlyRestTrueFieldsAreRegistered(): void
    {
        $page = $this->buildPage('mixed', [
            ['type' => 'text', 'id' => 'mixed_pub_rest',  'label' => 'Public',  'rest' => true],
            ['type' => 'text', 'id' => 'mixed_priv_rest', 'label' => 'Privé',   'rest' => false],
        ]);

        $page->registerRestOptions();

        $settings = get_registered_settings();
        $this->assertArrayHasKey('mixed_pub_rest', $settings);
        $this->assertArrayNotHasKey('mixed_priv_rest', $settings);
    }

    public function testBundleWithRestTrueIsRegisteredAsString(): void
    {
        $page = $this->buildPage('social', [
            'bundle', 'social_links', [
                ['type' => 'text', 'id' => 'link_url',   'label' => 'URL'],
                ['type' => 'text', 'id' => 'link_label', 'label' => 'Libellé'],
            ],
            ['rest' => true],
        ]);

        $page->registerRestOptions();

        $settings = get_registered_settings();
        $this->assertArrayHasKey('_social_links', $settings);
        $this->assertSame('string', $settings['_social_links']['type']);
    }

    public function testRestRegistrationSkippedWhenGlobalOptionDisabled(): void
    {
        update_option(RestPage::OPTION_REST, '0');

        $page = $this->buildPage('blocked', [
            ['type' => 'text', 'id' => 'blocked_rest_opt', 'label' => 'Bloqué', 'rest' => true],
        ]);

        $page->registerRestOptions();

        $settings = get_registered_settings();
        $this->assertArrayNotHasKey('blocked_rest_opt', $settings);
    }

    // ─── /wp/v2/settings — valeur lisible via REST natif ─────────────────────

    public function testRestFieldValueAppearsInWpV2Settings(): void
    {
        $page = $this->buildPage('brand', [
            ['type' => 'text', 'id' => 'brand_name_wpv2', 'label' => 'Marque', 'rest' => true],
        ]);

        $page->registerRestOptions();
        update_option('brand_name_wpv2', 'CFDev Agency');

        $request  = new WP_REST_Request('GET', '/wp/v2/settings');
        $response = rest_do_request($request);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertArrayHasKey('brand_name_wpv2', $data);
        $this->assertSame('CFDev Agency', $data['brand_name_wpv2']);
    }

    public function testRestFalseFieldAbsentFromWpV2Settings(): void
    {
        $page = $this->buildPage('brand', [
            ['type' => 'text', 'id' => 'brand_hidden_wpv2', 'label' => 'Caché', 'rest' => false],
        ]);

        $page->registerRestOptions();
        update_option('brand_hidden_wpv2', 'secret');

        $request  = new WP_REST_Request('GET', '/wp/v2/settings');
        $response = rest_do_request($request);

        $data = $response->get_data();
        $this->assertArrayNotHasKey('brand_hidden_wpv2', $data);
    }

    // ─── /cfdev/v1/options/{page_id} ──────────────────────────────────────────

    public function testHandleOptionsReturnsFieldValues(): void
    {
        $page = $this->buildPage('app_settings', [
            ['type' => 'text', 'id' => 'app_version_opt', 'label' => 'Version', 'rest' => true],
        ]);

        do_action('rest_api_init');

        update_option('app_version_opt', '2.0.0');

        $request = new WP_REST_Request('GET', '/cfdev/v1/options/app_settings');
        $request->set_param('page_id', 'app_settings');

        $api      = new CfdevRestApi();
        $response = $api->handleOptions($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $data = $response->get_data();
        $this->assertSame('app_settings', $data['page']);
        $this->assertArrayHasKey('app_settings', $data['groups']);
        $this->assertArrayHasKey('app_version_opt', $data['groups']['app_settings']);
        $this->assertSame('2.0.0', $data['groups']['app_settings']['app_version_opt']);
    }

    public function testHandleOptionsReturns404ForUnknownPageId(): void
    {
        // Aucune OptionsPage enregistrée → 404
        $request = new WP_REST_Request('GET', '/cfdev/v1/options/unknown_page');
        $request->set_param('page_id', 'unknown_page');

        $api      = new CfdevRestApi();
        $response = $api->handleOptions($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertSame('cfdev_not_found', $response->get_error_code());
    }

    public function testHandleOptionsReturns404WhenPageHasNoRestFields(): void
    {
        // Page enregistrée mais aucun champ rest:true → restFields() vide → 404
        new OptionsPage('private_page', 'Private', [
            ['type' => 'text', 'id' => 'priv_opt', 'label' => 'Privé', 'rest' => false],
        ]);

        $request = new WP_REST_Request('GET', '/cfdev/v1/options/private_page');
        $request->set_param('page_id', 'private_page');

        $api      = new CfdevRestApi();
        $response = $api->handleOptions($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertSame('cfdev_not_found', $response->get_error_code());
    }

    public function testHandleOptionsBundleReturnsDecodedRows(): void
    {
        $page = $this->buildPage('services_rest', [
            'bundle', 'srv_list', [
                ['type' => 'text', 'id' => 'srv_name',  'label' => 'Nom'],
                ['type' => 'text', 'id' => 'srv_price', 'label' => 'Prix'],
            ],
            ['rest' => true],
        ]);

        do_action('rest_api_init');

        $rows = [
            ['srv_name' => 'Développement', 'srv_price' => '5000'],
            ['srv_name' => 'Maintenance',   'srv_price' => '200'],
        ];
        update_option('_srv_list', wp_json_encode($rows));

        $request = new WP_REST_Request('GET', '/cfdev/v1/options/services_rest');
        $request->set_param('page_id', 'services_rest');

        $api      = new CfdevRestApi();
        $response = $api->handleOptions($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);

        $data  = $response->get_data();
        $group = $data['groups']['services_rest'] ?? [];

        $this->assertArrayHasKey('_srv_list', $group);
        $this->assertIsArray($group['_srv_list']);
        $this->assertCount(2, $group['_srv_list']);
    }

    public function testHandleOptionsOnlyExposesRestTrueFields(): void
    {
        $page = $this->buildPage('partial_rest', [
            ['type' => 'text', 'id' => 'partial_pub',  'label' => 'Public', 'rest' => true],
            ['type' => 'text', 'id' => 'partial_priv', 'label' => 'Privé',  'rest' => false],
        ]);

        do_action('rest_api_init');

        update_option('partial_pub', 'valeur publique');
        update_option('partial_priv', 'valeur secrète');

        $request = new WP_REST_Request('GET', '/cfdev/v1/options/partial_rest');
        $request->set_param('page_id', 'partial_rest');

        $api      = new CfdevRestApi();
        $response = $api->handleOptions($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);

        $group = $response->get_data()['groups']['partial_rest'] ?? [];

        $this->assertArrayHasKey('partial_pub', $group);
        $this->assertArrayNotHasKey('partial_priv', $group);
    }
}
