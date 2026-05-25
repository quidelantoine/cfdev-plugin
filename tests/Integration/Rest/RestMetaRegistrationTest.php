<?php

namespace Weblitzer\CFDev\Tests\Integration\Rest;

use Weblitzer\CFDev\Admin\RestPage;
use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Meta\UserMeta;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie que les champs marqués rest:true sont enregistrés dans l'API REST
 * standard de WordPress via register_meta(), et que l'option OPTION_REST=0
 * désactive globalement cette registration.
 *
 * Distinct de CfdevRestApiTest qui teste l'endpoint custom /cfdev/v1/.
 * Ici on teste que les champs apparaissent sous /wp/v2/{type}/{id}[meta].
 *
 * Utilise le slug 'restitem' (unique à cette suite) pour éviter toute
 * contamination par les hooks init accumulés d'autres suites de tests.
 */
class RestMetaRegistrationTest extends IntegrationTestCase
{
    private int $admin_id;

    public function setUp(): void
    {
        parent::setUp();

        // Force re-initialization so this CPT's routes get registered from scratch.
        global $wp_rest_server;
        $wp_rest_server = null;

        // 'custom-fields' support is required for WP REST to include a 'meta' key.
        register_cfdev_post_type(['restitem', 'restitems'], ['public' => true, 'show_in_rest' => true, 'supports' => ['title', 'editor', 'custom-fields']]);
        register_cfdev_taxonomy(['resttag', 'resttags'], 'restitem', ['public' => true, 'show_in_rest' => true]);
        do_action('init');

        Registry::reset();

        $this->admin_id = static::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_id);
    }

    public function tearDown(): void
    {
        delete_option(RestPage::OPTION_REST);
        Registry::reset();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // register_meta() — champ rest:true
    // -------------------------------------------------------------------------

    public function testRestTrueFieldIsRegisteredInWpMeta(): void
    {
        new MetaBox('fiche', 'Fiche', 'restitem', [
            ['type' => 'text', 'id' => 'resume_rest', 'label' => 'Résumé', 'rest' => true],
        ]);

        do_action('rest_api_init');

        $keys = get_registered_meta_keys('post', 'restitem');
        $this->assertArrayHasKey('resume_rest', $keys);
        $this->assertTrue($keys['resume_rest']['show_in_rest']);
    }

    public function testRestFalseFieldIsNotRegistered(): void
    {
        new MetaBox('fiche', 'Fiche', 'restitem', [
            ['type' => 'text', 'id' => 'champ_prive', 'label' => 'Privé', 'rest' => false],
        ]);

        do_action('rest_api_init');

        $keys = get_registered_meta_keys('post', 'restitem');
        $this->assertArrayNotHasKey('champ_prive', $keys);
    }

    public function testMixedFieldsOnlyRestTrueRegistered(): void
    {
        new MetaBox('fiche', 'Fiche', 'restitem', [
            ['type' => 'text', 'id' => 'expose_rest',  'label' => 'Exposé', 'rest' => true],
            ['type' => 'text', 'id' => 'prive_rest',   'label' => 'Privé',  'rest' => false],
        ]);

        do_action('rest_api_init');

        $keys = get_registered_meta_keys('post', 'restitem');
        $this->assertArrayHasKey('expose_rest', $keys);
        $this->assertArrayNotHasKey('prive_rest', $keys);
    }

    // -------------------------------------------------------------------------
    // Option OPTION_REST = 0 — désactive toute registration
    // -------------------------------------------------------------------------

    public function testRestRegistrationSkippedWhenOptionDisabled(): void
    {
        update_option(RestPage::OPTION_REST, '0');

        $box = new MetaBox('fiche', 'Fiche', 'restitem', [
            ['type' => 'text', 'id' => 'champ_bloque', 'label' => 'Bloqué', 'rest' => true],
        ]);

        $box->doRegisterRestMeta('post', 'restitem');

        $keys = get_registered_meta_keys('post', 'restitem');
        $this->assertArrayNotHasKey('champ_bloque', $keys);
    }

    public function testRestRegistrationWorksWhenOptionEnabled(): void
    {
        update_option(RestPage::OPTION_REST, '1');

        $box = new MetaBox('fiche', 'Fiche', 'restitem', [
            ['type' => 'text', 'id' => 'champ_actif', 'label' => 'Actif', 'rest' => true],
        ]);

        $box->doRegisterRestMeta('post', 'restitem');

        $keys = get_registered_meta_keys('post', 'restitem');
        $this->assertArrayHasKey('champ_actif', $keys);
    }

    // -------------------------------------------------------------------------
    // Bundle rest:true → ID du bundle enregistré
    // -------------------------------------------------------------------------

    public function testBundleWithRestTrueRegistersBundleId(): void
    {
        $box = new MetaBox('modules', 'Modules', 'restitem', [
            'bundle', 'items_rest', [
                ['type' => 'text', 'id' => 'titre_item', 'label' => 'Titre'],
            ],
            ['rest' => true],
        ]);

        $box->doRegisterRestMeta('post', 'restitem');

        $keys = get_registered_meta_keys('post', 'restitem');
        $this->assertArrayHasKey('_items_rest', $keys);
    }

    // -------------------------------------------------------------------------
    // TermMeta rest:true
    // -------------------------------------------------------------------------

    public function testTermMetaRestTrueRegistered(): void
    {
        $tm = new TermMeta('resttag', 'Tag', [
            ['type' => 'text', 'id' => 'note_resttag', 'label' => 'Note', 'rest' => true],
        ]);

        $tm->doRegisterRestMeta('term', 'resttag');

        $keys = get_registered_meta_keys('term', 'resttag');
        $this->assertArrayHasKey('note_resttag', $keys);
    }

    // -------------------------------------------------------------------------
    // UserMeta rest:true
    // -------------------------------------------------------------------------

    public function testUserMetaRestTrueRegistered(): void
    {
        $um = new UserMeta('profil', 'Profil', [
            ['type' => 'text', 'id' => 'bio_rest_user', 'label' => 'Bio', 'rest' => true],
        ]);

        $um->doRegisterRestMeta('user');

        $keys = get_registered_meta_keys('user');
        $this->assertArrayHasKey('bio_rest_user', $keys);
    }

    // -------------------------------------------------------------------------
    // Champ rest:true visible dans la réponse WP REST standard
    // -------------------------------------------------------------------------

    public function testRestFieldAppearsInWpRestResponse(): void
    {
        new MetaBox('fiche', 'Fiche', 'restitem', [
            ['type' => 'text', 'id' => 'synopsis_rest', 'label' => 'Synopsis', 'rest' => true],
        ]);

        do_action('rest_api_init');

        $post_id = static::factory()->post->create(['post_type' => 'restitem', 'post_status' => 'publish']);
        update_post_meta($post_id, 'synopsis_rest', 'Un résumé complet.');

        $request  = new \WP_REST_Request('GET', '/wp/v2/restitem/' . $post_id);
        $response = rest_do_request($request);
        $data     = $response->get_data();

        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('synopsis_rest', $data['meta']);
        $this->assertSame('Un résumé complet.', $data['meta']['synopsis_rest']);
    }

    public function testRestFalseFieldAbsentFromWpRestResponse(): void
    {
        new MetaBox('fiche', 'Fiche', 'restitem', [
            ['type' => 'text', 'id' => 'synopsis_prive', 'label' => 'Synopsis', 'rest' => false],
        ]);

        do_action('rest_api_init');

        $post_id = static::factory()->post->create(['post_type' => 'restitem', 'post_status' => 'publish']);
        update_post_meta($post_id, 'synopsis_prive', 'secret');

        $request  = new \WP_REST_Request('GET', '/wp/v2/restitem/' . $post_id);
        $response = rest_do_request($request);
        $data     = $response->get_data();

        $meta = $data['meta'] ?? [];
        $this->assertArrayNotHasKey('synopsis_prive', $meta);
    }
}
