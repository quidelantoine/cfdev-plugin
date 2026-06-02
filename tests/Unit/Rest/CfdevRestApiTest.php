<?php

namespace Weblitzer\CFDev\Tests\Unit\Rest;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Meta\UserMeta;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Rest\CfdevRestApi;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class CfdevRestApiTest extends CFDevTestCase
{
    private string $tmpDir;
    private CfdevRestApi $api;

    protected function setUp(): void
    {
        parent::setUp();
        Registry::reset();

        $this->tmpDir = sys_get_temp_dir() . '/cfdev-rest-' . uniqid();
        mkdir($this->tmpDir, 0755, true); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir

        // Common WP stubs
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(
            fn(string $s) => strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $s))
        );
        Functions\when('sanitize_key')->returnArg();
        Functions\when('wp_get_current_user')->alias(function (): \WP_User {
            $u        = new \WP_User();
            $u->roles = ['administrator'];
            return $u;
        });
        Functions\when('get_option')->justReturn(true);
        Functions\when('register_meta')->justReturn(true);
        Functions\when('add_action')->justReturn(true);

        // CacheStore stubs
        Functions\when('wp_upload_dir')->justReturn(['basedir' => $this->tmpDir]);
        Functions\when('trailingslashit')->alias(fn(string $s): string => rtrim($s, '/') . '/');
        Functions\when('wp_mkdir_p')->alias(
            fn(string $d): bool => is_dir($d) || mkdir($d, 0755, true) // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
        );
        Functions\when('sanitize_file_name')->returnArg();
        Functions\when('wp_json_encode')->alias('json_encode');

        $this->api = new CfdevRestApi();
    }

    protected function tearDown(): void
    {
        $this->removeDirRecursive($this->tmpDir);
        parent::tearDown();
    }

    private function removeDirRecursive(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->removeDirRecursive($path) : unlink($path); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
        }
        rmdir($dir); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_rmdir
    }

    /** @param array<string, mixed> $params */
    private function request(array $params): \WP_REST_Request
    {
        $request = new \WP_REST_Request('GET', '/test');
        foreach ($params as $key => $value) {
            $request->set_param((string) $key, $value);
        }
        return $request;
    }

    // =========================================================================
    // canReadPost
    // =========================================================================

    public function testCanReadPostReturnsTrueForPublishedPublicPost(): void
    {
        $post              = new \WP_Post();
        $post->ID          = 1;
        $post->post_type   = 'book';
        $post->post_status = 'publish';

        $post_type_obj         = new \WP_Post_Type();
        $post_type_obj->public = true;

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_type_object')->justReturn($post_type_obj);

        $result = $this->api->canReadPost($this->request(['id' => 1]));

        $this->assertTrue($result);
    }

    public function testCanReadPostReturns404WhenPostNotFound(): void
    {
        Functions\when('get_post')->justReturn(null);

        $result = $this->api->canReadPost($this->request(['id' => 99]));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(404, $result->data['status']);
    }

    public function testCanReadPostReturns401WhenNotLoggedInAndPostPrivate(): void
    {
        $post              = new \WP_Post();
        $post->ID          = 1;
        $post->post_type   = 'book';
        $post->post_status = 'private';

        $post_type_obj         = new \WP_Post_Type();
        $post_type_obj->public = true;

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_type_object')->justReturn($post_type_obj);
        Functions\when('current_user_can')->justReturn(false);
        Functions\when('is_user_logged_in')->justReturn(false);

        $result = $this->api->canReadPost($this->request(['id' => 1]));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(401, $result->data['status']);
    }

    public function testCanReadPostReturns403WhenLoggedInWithoutReadCapability(): void
    {
        $post              = new \WP_Post();
        $post->ID          = 1;
        $post->post_type   = 'book';
        $post->post_status = 'draft';

        $post_type_obj         = new \WP_Post_Type();
        $post_type_obj->public = false;

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_type_object')->justReturn($post_type_obj);
        Functions\when('current_user_can')->justReturn(false);
        Functions\when('is_user_logged_in')->justReturn(true);

        $result = $this->api->canReadPost($this->request(['id' => 1]));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(403, $result->data['status']);
    }

    public function testCanReadPostReturnsTrueWhenUserCanReadPost(): void
    {
        $post              = new \WP_Post();
        $post->ID          = 1;
        $post->post_type   = 'book';
        $post->post_status = 'private';

        $post_type_obj         = new \WP_Post_Type();
        $post_type_obj->public = true;

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_type_object')->justReturn($post_type_obj);
        Functions\when('current_user_can')->justReturn(true);

        $result = $this->api->canReadPost($this->request(['id' => 1]));

        $this->assertTrue($result);
    }

    // =========================================================================
    // canReadTerm
    // =========================================================================

    public function testCanReadTermReturns400ForInvalidTaxonomy(): void
    {
        Functions\when('get_taxonomy')->justReturn(false);

        $result = $this->api->canReadTerm($this->request(['taxonomy' => 'nope']));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(400, $result->data['status']);
    }

    public function testCanReadTermReturnsTrueForPublicTaxonomy(): void
    {
        $tax         = new \stdClass();
        $tax->public = true;

        Functions\when('get_taxonomy')->justReturn($tax);

        $result = $this->api->canReadTerm($this->request(['taxonomy' => 'genre']));

        $this->assertTrue($result);
    }

    public function testCanReadTermReturns401WhenNotLoggedInAndTaxonomyPrivate(): void
    {
        $tax         = new \stdClass();
        $tax->public = false;

        Functions\when('get_taxonomy')->justReturn($tax);
        Functions\when('current_user_can')->justReturn(false);
        Functions\when('is_user_logged_in')->justReturn(false);

        $result = $this->api->canReadTerm($this->request(['taxonomy' => 'secret']));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(401, $result->data['status']);
    }

    public function testCanReadTermReturns403WhenLoggedInWithoutManageTerms(): void
    {
        $tax         = new \stdClass();
        $tax->public = false;

        Functions\when('get_taxonomy')->justReturn($tax);
        Functions\when('current_user_can')->justReturn(false);
        Functions\when('is_user_logged_in')->justReturn(true);

        $result = $this->api->canReadTerm($this->request(['taxonomy' => 'secret']));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(403, $result->data['status']);
    }

    public function testCanReadTermReturnsTrueWhenUserCanManageTerms(): void
    {
        $tax         = new \stdClass();
        $tax->public = false;

        Functions\when('get_taxonomy')->justReturn($tax);
        Functions\when('current_user_can')->justReturn(true);

        $result = $this->api->canReadTerm($this->request(['taxonomy' => 'secret']));

        $this->assertTrue($result);
    }

    // =========================================================================
    // canReadUser
    // =========================================================================

    public function testCanReadUserReturns401WhenNotLoggedIn(): void
    {
        Functions\when('is_user_logged_in')->justReturn(false);

        $result = $this->api->canReadUser($this->request(['id' => 1]));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(401, $result->data['status']);
    }

    public function testCanReadUserReturnsTrueForOwnProfile(): void
    {
        Functions\when('is_user_logged_in')->justReturn(true);
        Functions\when('get_current_user_id')->justReturn(5);

        $result = $this->api->canReadUser($this->request(['id' => 5]));

        $this->assertTrue($result);
    }

    public function testCanReadUserReturnsTrueWhenCanEditUser(): void
    {
        Functions\when('is_user_logged_in')->justReturn(true);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('current_user_can')->justReturn(true);

        $result = $this->api->canReadUser($this->request(['id' => 5]));

        $this->assertTrue($result);
    }

    public function testCanReadUserReturns403WhenLoggedInButNotOwnerAndNoEditCap(): void
    {
        Functions\when('is_user_logged_in')->justReturn(true);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('current_user_can')->justReturn(false);

        $result = $this->api->canReadUser($this->request(['id' => 5]));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(403, $result->data['status']);
    }

    // =========================================================================
    // canReadOptions
    // =========================================================================

    public function testCanReadOptionsReturns401WhenNotLoggedIn(): void
    {
        Functions\when('is_user_logged_in')->justReturn(false);

        $result = $this->api->canReadOptions($this->request([]));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(401, $result->data['status']);
    }

    public function testCanReadOptionsReturns403WhenLoggedInWithoutManageOptions(): void
    {
        Functions\when('is_user_logged_in')->justReturn(true);
        Functions\when('current_user_can')->justReturn(false);

        $result = $this->api->canReadOptions($this->request([]));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(403, $result->data['status']);
    }

    public function testCanReadOptionsReturnsTrueWhenUserCanManageOptions(): void
    {
        Functions\when('is_user_logged_in')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);

        $result = $this->api->canReadOptions($this->request([]));

        $this->assertTrue($result);
    }

    // =========================================================================
    // handlePost — error paths
    // =========================================================================

    public function testHandlePostReturns404WhenPostNotFound(): void
    {
        Functions\when('get_post')->justReturn(null);

        $result = $this->api->handlePost($this->request(['id' => 99]));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(404, $result->data['status']);
    }

    public function testHandlePostReturns404WhenNoRestEntriesForPostType(): void
    {
        $post            = new \WP_Post();
        $post->ID        = 1;
        $post->post_type = 'book';

        Functions\when('get_post')->justReturn($post);

        // No MetaBox registered → restFields() is empty
        $result = $this->api->handlePost($this->request(['id' => 1]));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(404, $result->data['status']);
    }

    // =========================================================================
    // handleTerm — error paths
    // =========================================================================

    public function testHandleTermReturns404WhenTermNotFound(): void
    {
        Functions\when('get_term')->justReturn(null);

        $result = $this->api->handleTerm($this->request(['id' => 99, 'taxonomy' => 'genre']));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(404, $result->data['status']);
    }

    public function testHandleTermReturns404WhenNoRestEntriesForTaxonomy(): void
    {
        $term           = new \WP_Term();
        $term->term_id  = 1;
        $term->taxonomy = 'genre';

        Functions\when('get_term')->justReturn($term);

        $result = $this->api->handleTerm($this->request(['id' => 1, 'taxonomy' => 'genre']));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(404, $result->data['status']);
    }

    // =========================================================================
    // handleUser — error paths
    // =========================================================================

    public function testHandleUserReturns404WhenUserNotFound(): void
    {
        Functions\when('get_userdata')->justReturn(false);

        $result = $this->api->handleUser($this->request(['id' => 99]));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(404, $result->data['status']);
    }

    public function testHandleUserReturns404WhenNoRestEntriesForUsers(): void
    {
        $user     = new \WP_User();
        $user->ID = 1;

        Functions\when('get_userdata')->justReturn($user);

        $result = $this->api->handleUser($this->request(['id' => 1]));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(404, $result->data['status']);
    }

    // =========================================================================
    // handlePost — success + filterGroups
    // =========================================================================

    public function testHandlePostReturnsOnlyRestFlaggedFields(): void
    {
        new MetaBox('details', 'Détails', 'book', [
            ['type' => 'text', 'id' => '_pub',     'name' => 'Public',  'rest' => true],
            ['type' => 'text', 'id' => '_private', 'name' => 'Private'],
        ]);

        $post            = new \WP_Post();
        $post->ID        = 42;
        $post->post_type = 'book';

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_type')->justReturn('book');
        Functions\when('get_post_meta')->alias(
            fn(int $id, string $key, bool $single) => match ($key) {
                '_pub'     => 'hello',
                '_private' => 'secret',
                default    => '',
            }
        );

        $result = $this->api->handlePost($this->request(['id' => 42]));

        $this->assertInstanceOf(\WP_REST_Response::class, $result);
        $data   = $result->data;
        $group  = $data['groups']['details'];

        $this->assertArrayHasKey('_pub', $group);
        $this->assertArrayNotHasKey('_private', $group);
        $this->assertSame('hello', $group['_pub']);
    }

    public function testHandlePostResponseContainsIdAndGroups(): void
    {
        new MetaBox('details', 'Détails', 'book', [
            ['type' => 'text', 'id' => '_title', 'name' => 'Title', 'rest' => true],
        ]);

        $post            = new \WP_Post();
        $post->ID        = 7;
        $post->post_type = 'book';

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_type')->justReturn('book');
        Functions\when('get_post_meta')->justReturn('My Title');

        $result = $this->api->handlePost($this->request(['id' => 7]));

        $this->assertInstanceOf(\WP_REST_Response::class, $result);
        $this->assertSame(7, $result->data['id']);
        $this->assertArrayHasKey('groups', $result->data);
    }

    public function testHandlePostGroupIsEmptyWhenCacheHasNoDataForGroup(): void
    {
        new MetaBox('details', 'Détails', 'book', [
            ['type' => 'text', 'id' => '_title', 'name' => 'Title', 'rest' => true],
        ]);

        $post            = new \WP_Post();
        $post->ID        = 1;
        $post->post_type = 'book';

        Functions\when('get_post')->justReturn($post);
        Functions\when('get_post_type')->justReturn('book');
        Functions\when('get_post_meta')->justReturn('');

        $result = $this->api->handlePost($this->request(['id' => 1]));

        $this->assertInstanceOf(\WP_REST_Response::class, $result);
        $this->assertArrayHasKey('details', $result->data['groups']);
        $this->assertSame('', $result->data['groups']['details']['_title']);
    }

    // =========================================================================
    // handleTerm — success
    // =========================================================================

    public function testHandleTermReturnsOnlyRestFlaggedFields(): void
    {
        new TermMeta('genre', '', [
            ['type' => 'text', 'id' => '_color',   'name' => 'Color',   'rest' => true],
            ['type' => 'text', 'id' => '_internal', 'name' => 'Internal'],
        ]);

        $term           = new \WP_Term();
        $term->term_id  = 3;
        $term->taxonomy = 'genre';

        Functions\when('get_term')->justReturn($term);
        Functions\when('get_term_meta')->alias(
            fn(int $id, string $key, bool $single) => match ($key) {
                '_color'    => '#ff0000',
                '_internal' => 'hidden',
                default     => '',
            }
        );

        $result = $this->api->handleTerm($this->request(['id' => 3, 'taxonomy' => 'genre']));

        $this->assertInstanceOf(\WP_REST_Response::class, $result);
        $group = $result->data['groups']['genre'];

        $this->assertArrayHasKey('_color', $group);
        $this->assertArrayNotHasKey('_internal', $group);
        $this->assertSame('#ff0000', $group['_color']);
        $this->assertSame('genre', $result->data['taxonomy']);
    }

    // =========================================================================
    // handleUser — success
    // =========================================================================

    public function testHandleUserReturnsOnlyRestFlaggedFields(): void
    {
        new UserMeta('profile', 'Profil', [
            ['type' => 'text', 'id' => '_bio',     'name' => 'Bio',     'rest' => true],
            ['type' => 'text', 'id' => '_private', 'name' => 'Private'],
        ]);

        $user     = new \WP_User();
        $user->ID = 5;

        Functions\when('get_userdata')->justReturn($user);
        Functions\when('get_user_meta')->alias(
            fn(int $id, string $key, bool $single) => match ($key) {
                '_bio'     => 'Developer',
                '_private' => 'secret',
                default    => '',
            }
        );

        $result = $this->api->handleUser($this->request(['id' => 5]));

        $this->assertInstanceOf(\WP_REST_Response::class, $result);
        $group = $result->data['groups']['profile'];

        $this->assertArrayHasKey('_bio', $group);
        $this->assertArrayNotHasKey('_private', $group);
        $this->assertSame('Developer', $group['_bio']);
    }

    public function testHandleUserFiltersGroupsByRequestedUserRoles(): void
    {
        // Group visible only to 'editor' role
        $um = new UserMeta('editor_section', 'Editor Section', [
            ['type' => 'text', 'id' => '_notes', 'name' => 'Notes', 'rest' => true],
        ]);
        $um->onlyForRole('editor');

        // Requested user is an editor; current user (setUp) is administrator
        $user        = new \WP_User();
        $user->ID    = 7;
        $user->roles = ['editor'];

        Functions\when('get_userdata')->justReturn($user);
        Functions\when('get_user_meta')->justReturn('some note');

        $result = $this->api->handleUser($this->request(['id' => 7]));

        $this->assertInstanceOf(\WP_REST_Response::class, $result);
        // The group must be present because the *requested* user is an editor
        $this->assertArrayHasKey('editor_section', $result->data['groups']);
    }
}
