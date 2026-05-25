<?php

namespace Weblitzer\CFDev\Tests\Integration\Rest;

use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Rest\CfdevRestApi;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;
use WP_REST_Request;

/**
 * Vérifie les handlers REST de CfdevRestApi avec le vrai WP REST Server.
 */
class CfdevRestApiTest extends IntegrationTestCase
{
    private int $admin_id;

    public function setUp(): void
    {
        parent::setUp();
        Registry::reset();

        $this->admin_id = static::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_id);

        register_cfdev_post_type(['oeuvre', 'oeuvres'], ['public' => true]);
        register_cfdev_taxonomy(['auteur', 'auteurs'], 'oeuvre', ['public' => true]);
        do_action('init');

        (new CfdevRestApi())->register();
        do_action('rest_api_init');
    }

    public function tearDown(): void
    {
        Registry::reset();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildMetaBoxWithRestField(): MetaBox
    {
        return new MetaBox('detail_oeuvre', 'Détail', 'oeuvre', [
            ['type' => 'text', 'id' => 'isbn', 'label' => 'ISBN', 'rest' => true],
            ['type' => 'text', 'id' => 'editeur', 'label' => 'Éditeur', 'rest' => false],
        ]);
    }

    // -------------------------------------------------------------------------
    // handlePost
    // -------------------------------------------------------------------------

    public function testPostHandlerReturnsMetaForRestFieldsOnly(): void
    {
        $this->buildMetaBoxWithRestField();
        do_action('rest_api_init');

        $post_id = static::factory()->post->create(['post_type' => 'oeuvre', 'post_status' => 'publish']);
        update_post_meta($post_id, 'isbn', '978-2-07-036822-8');
        update_post_meta($post_id, 'editeur', 'Gallimard');

        $request = new WP_REST_Request('GET', '/cfdev/v1/post/' . $post_id);
        $request->set_param('id', $post_id);

        $api      = new CfdevRestApi();
        $response = $api->handlePost($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());

        $data   = $response->get_data();
        $groups = $data['groups']['detail_oeuvre'] ?? [];

        $this->assertArrayHasKey('isbn', $groups);
        $this->assertArrayNotHasKey('editeur', $groups);
    }

    public function testPostHandlerReturns404ForNonexistentPost(): void
    {
        $this->buildMetaBoxWithRestField();

        $request = new WP_REST_Request('GET', '/cfdev/v1/post/99999');
        $request->set_param('id', 99999);

        $api      = new CfdevRestApi();
        $response = $api->handlePost($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertSame('cfdev_not_found', $response->get_error_code());
    }

    public function testPostHandlerReturns404WhenNoRestFieldsRegistered(): void
    {
        // Aucun meta box enregistré
        $post_id = static::factory()->post->create(['post_type' => 'oeuvre', 'post_status' => 'publish']);

        $request = new WP_REST_Request('GET', '/cfdev/v1/post/' . $post_id);
        $request->set_param('id', $post_id);

        $api      = new CfdevRestApi();
        $response = $api->handlePost($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertSame('cfdev_not_found', $response->get_error_code());
    }

    // -------------------------------------------------------------------------
    // handleTerm
    // -------------------------------------------------------------------------

    public function testTermHandlerReturnsMetaForRestFields(): void
    {
        new \Weblitzer\CFDev\Meta\TermMeta('auteur', 'Auteur', [
            ['type' => 'text', 'id' => 'nationalite', 'label' => 'Nationalité', 'rest' => true],
        ]);
        do_action('rest_api_init');

        $result = wp_insert_term('Victor Hugo', 'auteur');
        if (is_wp_error($result)) {
            $this->fail($result->get_error_message());
        }
        $term_id = $result['term_id'];
        update_term_meta($term_id, 'nationalite', 'Française');

        $request = new WP_REST_Request('GET', '/cfdev/v1/term/auteur/' . $term_id);
        $request->set_param('taxonomy', 'auteur');
        $request->set_param('id', $term_id);

        $api      = new CfdevRestApi();
        $response = $api->handleTerm($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data   = $response->get_data();
        $groups = $data['groups'] ?? [];

        $found = false;
        foreach ($groups as $group) {
            if (isset($group['nationalite'])) {
                $found = true;
            }
        }
        $this->assertTrue($found, 'Le champ nationalite devrait être présent dans les groupes REST.');
    }

    public function testTermHandlerReturns404ForNonexistentTerm(): void
    {
        new \Weblitzer\CFDev\Meta\TermMeta('auteur', 'Auteur', [
            ['type' => 'text', 'id' => 'bio_auteur', 'label' => 'Bio', 'rest' => true],
        ]);

        $request = new WP_REST_Request('GET', '/cfdev/v1/term/auteur/99999');
        $request->set_param('taxonomy', 'auteur');
        $request->set_param('id', 99999);

        $api      = new CfdevRestApi();
        $response = $api->handleTerm($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertSame('cfdev_not_found', $response->get_error_code());
    }

    // -------------------------------------------------------------------------
    // handleUser
    // -------------------------------------------------------------------------

    public function testUserHandlerReturnsMetaForRestFields(): void
    {
        new \Weblitzer\CFDev\Meta\UserMeta('profil_rest', 'Profil', [
            ['type' => 'text', 'id' => 'metier', 'label' => 'Métier', 'rest' => true],
        ]);
        do_action('rest_api_init');

        update_user_meta($this->admin_id, 'metier', 'Développeur');

        $request = new WP_REST_Request('GET', '/cfdev/v1/user/' . $this->admin_id);
        $request->set_param('id', $this->admin_id);

        $api      = new CfdevRestApi();
        $response = $api->handleUser($request);

        $this->assertInstanceOf(\WP_REST_Response::class, $response);
        $data   = $response->get_data();
        $groups = $data['groups'] ?? [];

        $found = false;
        foreach ($groups as $group) {
            if (isset($group['metier'])) {
                $found = true;
            }
        }
        $this->assertTrue($found, 'Le champ metier devrait être présent dans les groupes REST.');
    }

    public function testUserHandlerReturns404ForNonexistentUser(): void
    {
        new \Weblitzer\CFDev\Meta\UserMeta('profil_404', 'Profil', [
            ['type' => 'text', 'id' => 'champ_u', 'label' => 'Champ', 'rest' => true],
        ]);

        $request = new WP_REST_Request('GET', '/cfdev/v1/user/99999');
        $request->set_param('id', 99999);

        $api      = new CfdevRestApi();
        $response = $api->handleUser($request);

        $this->assertInstanceOf(\WP_Error::class, $response);
        $this->assertSame('cfdev_not_found', $response->get_error_code());
    }

    // -------------------------------------------------------------------------
    // Permissions
    // -------------------------------------------------------------------------

    public function testPublicPostIsReadableWithoutLogin(): void
    {
        $this->buildMetaBoxWithRestField();

        $post_id = static::factory()->post->create([
            'post_type'   => 'oeuvre',
            'post_status' => 'publish',
        ]);

        wp_set_current_user(0); // non connecté

        $request = new WP_REST_Request('GET', '/cfdev/v1/post/' . $post_id);
        $request->set_param('id', $post_id);

        $api    = new CfdevRestApi();
        $result = $api->canReadPost($request);

        $this->assertTrue($result);
    }

    public function testDraftPostRequiresAuthentication(): void
    {
        $this->buildMetaBoxWithRestField();

        $post_id = static::factory()->post->create([
            'post_type'   => 'oeuvre',
            'post_status' => 'draft',
        ]);

        wp_set_current_user(0); // non connecté

        $request = new WP_REST_Request('GET', '/cfdev/v1/post/' . $post_id);
        $request->set_param('id', $post_id);

        $api    = new CfdevRestApi();
        $result = $api->canReadPost($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(401, $result->get_error_data()['status']);
    }

    public function testUserRouteRequiresAuthentication(): void
    {
        wp_set_current_user(0); // non connecté

        $request = new WP_REST_Request('GET', '/cfdev/v1/user/1');
        $request->set_param('id', 1);

        $api    = new CfdevRestApi();
        $result = $api->canReadUser($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(401, $result->get_error_data()['status']);
    }

    public function testUserCanReadOwnProfile(): void
    {
        $request = new WP_REST_Request('GET', '/cfdev/v1/user/' . $this->admin_id);
        $request->set_param('id', $this->admin_id);

        $api    = new CfdevRestApi();
        $result = $api->canReadUser($request);

        $this->assertTrue($result);
    }
}
