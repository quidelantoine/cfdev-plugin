<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use Weblitzer\CFDev\Field;
use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Meta\UserMeta;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Vérifie la persistance des champs en mode repeatable.
 *
 * Un champ repeatable poste un tableau de valeurs sous cfdev[field_id][].
 * Field::save() encode le tableau en JSON (ASCII-safe), et
 * Field::decodeMetaValue() le redécode à la lecture.
 */
class RepeatableFieldsTest extends IntegrationTestCase
{
    private int $admin_id;
    private int $post_id;
    private int $term_id;
    private int $user_id;

    public function setUp(): void
    {
        parent::setUp();

        register_cfdev_post_type(['projet', 'projets'], ['public' => true]);
        register_cfdev_taxonomy(['techno', 'technos'], 'projet', ['public' => true]);
        do_action('init');

        Registry::reset();

        $this->admin_id = static::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($this->admin_id);

        $this->post_id = static::factory()->post->create(['post_type' => 'projet']);
        $ins = wp_insert_term('Frontend', 'techno');
        if (is_wp_error($ins)) {
            throw new \RuntimeException($ins->get_error_message()); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
        $this->term_id = $ins['term_id'];
        $this->user_id = $this->admin_id;
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
    // MetaBox — champ repeatable
    // -------------------------------------------------------------------------

    public function testRepeatableTextFieldSavesAsJsonArray(): void
    {
        $box = new MetaBox('liens_projet', 'Liens', 'projet', [
            ['type' => 'text', 'id' => 'urls_projet', 'label' => 'URLs', 'repeatable' => true],
        ]);

        $this->postWith([
            'urls_projet' => ['https://github.com', 'https://npmjs.com', 'https://packagist.org'],
        ]);
        $box->savePost($this->post_id);

        $raw = get_post_meta($this->post_id, 'urls_projet', true);
        $this->assertNotEmpty($raw);

        $decoded = json_decode($raw, true);
        $this->assertIsArray($decoded);
        $this->assertCount(3, $decoded);
        $this->assertSame('https://github.com', $decoded[0]);
    }

    public function testRepeatableFieldDecodedByDecodeMetaValue(): void
    {
        $box = new MetaBox('liens_projet', 'Liens', 'projet', [
            ['type' => 'text', 'id' => 'tags_projet', 'label' => 'Tags', 'repeatable' => true],
        ]);

        $this->postWith([
            'tags_projet' => ['PHP', 'Docker', 'CI'],
        ]);
        $box->savePost($this->post_id);

        $raw     = get_post_meta($this->post_id, 'tags_projet', true);
        $decoded = Field::decodeMetaValue($raw);

        $this->assertIsArray($decoded);
        $this->assertSame(['PHP', 'Docker', 'CI'], $decoded);
    }

    public function testRepeatableSingleValueIsStoredAsArray(): void
    {
        $box = new MetaBox('liens_projet', 'Liens', 'projet', [
            ['type' => 'text', 'id' => 'keyword_projet', 'label' => 'Keyword', 'repeatable' => true],
        ]);

        $this->postWith(['keyword_projet' => ['SEO']]);
        $box->savePost($this->post_id);

        $decoded = Field::decodeMetaValue(get_post_meta($this->post_id, 'keyword_projet', true));

        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertSame('SEO', $decoded[0]);
    }

    public function testRepeatableFieldOverwriteReplacesValues(): void
    {
        $box = new MetaBox('liens_projet', 'Liens', 'projet', [
            ['type' => 'text', 'id' => 'langs_projet', 'label' => 'Langages', 'repeatable' => true],
        ]);

        $this->postWith(['langs_projet' => ['PHP', 'JS', 'Go']]);
        $box->savePost($this->post_id);

        $this->postWith(['langs_projet' => ['Rust']]);
        $box->savePost($this->post_id);

        $decoded = Field::decodeMetaValue(get_post_meta($this->post_id, 'langs_projet', true));

        $this->assertCount(1, $decoded);
        $this->assertSame('Rust', $decoded[0]);
    }

    public function testNonRepeatableFieldSavesAsPlainString(): void
    {
        $box = new MetaBox('infos_projet', 'Infos', 'projet', [
            ['type' => 'text', 'id' => 'titre_projet', 'label' => 'Titre'],
        ]);

        $this->postWith(['titre_projet' => 'Mon super projet']);
        $box->savePost($this->post_id);

        $raw = get_post_meta($this->post_id, 'titre_projet', true);

        // Un champ non-repeatable est stocké comme chaîne brute, pas comme JSON
        $this->assertSame('Mon super projet', $raw);
        $this->assertStringNotContainsString('[', $raw);
    }

    // -------------------------------------------------------------------------
    // TermMeta — repeatable
    // -------------------------------------------------------------------------

    public function testRepeatableFieldInTermMeta(): void
    {
        $tm = new TermMeta('techno', 'Techno', [
            ['type' => 'text', 'id' => 'frameworks', 'label' => 'Frameworks', 'repeatable' => true],
        ]);

        $_POST['cfdev_nonce'] = wp_create_nonce('cfdev_meta');
        $_POST['cfdev']       = ['__activate' => '', 'frameworks' => ['React', 'Vue', 'Svelte']];
        $tm->saveTerm($this->term_id);

        $decoded = Field::decodeMetaValue(get_term_meta($this->term_id, 'frameworks', true));

        $this->assertIsArray($decoded);
        $this->assertSame(['React', 'Vue', 'Svelte'], $decoded);
    }

    // -------------------------------------------------------------------------
    // UserMeta — repeatable
    // -------------------------------------------------------------------------

    public function testRepeatableFieldInUserMeta(): void
    {
        $um = new UserMeta('profil', 'Profil', [
            ['type' => 'text', 'id' => 'roles_user', 'label' => 'Rôles', 'repeatable' => true],
        ]);

        $this->postWith(['roles_user' => ['Admin', 'Reviewer', 'Editor']]);
        $um->saveUser($this->user_id);

        $decoded = Field::decodeMetaValue(get_user_meta($this->user_id, 'roles_user', true));

        $this->assertIsArray($decoded);
        $this->assertSame(['Admin', 'Reviewer', 'Editor'], $decoded);
    }

    // -------------------------------------------------------------------------
    // Repeatable + décoder une valeur non-JSON ne plante pas
    // -------------------------------------------------------------------------

    public function testDecodeMetaValueReturnsPlainStringUnchanged(): void
    {
        $this->assertSame('bonjour', Field::decodeMetaValue('bonjour'));
    }

    public function testDecodeMetaValueReturnsEmptyStringUnchanged(): void
    {
        $this->assertSame('', Field::decodeMetaValue(''));
    }

    public function testDecodeMetaValueDecodesJsonArray(): void
    {
        $json    = '["a","b","c"]';
        $decoded = Field::decodeMetaValue($json);

        $this->assertSame(['a', 'b', 'c'], $decoded);
    }
}
