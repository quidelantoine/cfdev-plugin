<?php

namespace Weblitzer\CFDev\Tests\Integration\Meta;

use PHPUnit\Framework\Attributes\DataProvider;
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
    // DataProvider — tous les types string-based supportant repeatable
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array{string, array<string, mixed>, list<string>, list<string>|null}>
     * null expected = values are transformed (timestamps), only count is checked
     */
    public static function repeatableTypesProvider(): array
    {
        return [
            'textarea' => ['textarea', [],                                      ['Premier paragraphe', 'Second'],   ['Premier paragraphe', 'Second']],
            'number'   => ['number',   [],                                      ['42', '99'],                       ['42', '99']],
            'email'    => ['email',    [],                                      ['a@example.com', 'b@ex.com'],      ['a@example.com', 'b@ex.com']],
            'tel'      => ['tel',      [],                                      ['+33600000001', '+33600000002'],   ['+33600000001', '+33600000002']],
            'url'      => ['url',      [],                                      ['https://a.com', 'https://b.com'], ['https://a.com', 'https://b.com']],
            'color'    => ['color',    [],                                      ['#ff0000', '#00ff00'],             ['#ff0000', '#00ff00']],
            'range'    => ['range',    [],                                      ['25', '75'],                       ['25', '75']],
            'select'   => ['select',   ['options' => ['a' => 'A', 'b' => 'B']], ['a', 'b'],                        ['a', 'b']],
            // date/time/datetime convert posted strings to timestamps — check count only
            'date'     => ['date',     [],                                      ['01/15/2024', '06/30/2024'],       null],
            'time'     => ['time',     [],                                      ['14:30', '08:00'],                 null],
            'datetime' => ['datetime', [],                                      ['01/15/2024 14:30', '06/30/2024 08:00'], null],
        ];
    }

    /**
     * @param array<string, mixed> $extra_args
     * @param list<string>         $values
     * @param list<string>|null    $expected null = values are transformed, check count only
     */
    #[DataProvider('repeatableTypesProvider')]
    public function testRepeatableTypeSavesAsJsonArrayInMetaBox(string $type, array $extra_args, array $values, ?array $expected): void
    {
        $field_id = 'rep_' . $type;
        $box = new MetaBox('test_box', 'Test', 'projet', [
            array_merge(['type' => $type, 'id' => $field_id, 'label' => $type, 'repeatable' => true], $extra_args),
        ]);

        $this->postWith([$field_id => $values]);
        $box->savePost($this->post_id);

        $decoded = Field::decodeMetaValue(get_post_meta($this->post_id, $field_id, true));

        $this->assertIsArray($decoded);
        if ($expected !== null) {
            $this->assertSame($expected, $decoded);
        } else {
            $this->assertCount(count($values), $decoded);
        }
    }

    // -------------------------------------------------------------------------
    // term_select / user_select repeatable — IDs réels
    // -------------------------------------------------------------------------

    public function testRepeatableTermSelectSavesTermIdsAsJsonArray(): void
    {
        $ins_b = wp_insert_term('Backend', 'techno');
        if (is_wp_error($ins_b)) {
            throw new \RuntimeException($ins_b->get_error_message()); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
        $term_b = $ins_b['term_id'];

        $box = new MetaBox('meta_techno', 'Techno', 'projet', [
            ['type' => 'term_select', 'id' => 'techno_ids', 'label' => 'Technos',
                'args' => ['taxonomy' => 'techno'], 'repeatable' => true],
        ]);

        $this->postWith(['techno_ids' => [(string) $this->term_id, (string) $term_b]]);
        $box->savePost($this->post_id);

        $decoded = Field::decodeMetaValue(get_post_meta($this->post_id, 'techno_ids', true));

        $this->assertIsArray($decoded);
        $this->assertSame([(string) $this->term_id, (string) $term_b], $decoded);
    }

    public function testRepeatableUserSelectSavesUserIdsAsJsonArray(): void
    {
        $user_b = static::factory()->user->create(['role' => 'editor']);

        $box = new MetaBox('meta_users', 'Users', 'projet', [
            ['type' => 'user_select', 'id' => 'reviewer_ids', 'label' => 'Reviewers',
                'args' => ['show_option_none' => '—'], 'repeatable' => true],
        ]);

        $this->postWith(['reviewer_ids' => [(string) $this->user_id, (string) $user_b]]);
        $box->savePost($this->post_id);

        $decoded = Field::decodeMetaValue(get_post_meta($this->post_id, 'reviewer_ids', true));

        $this->assertIsArray($decoded);
        $this->assertSame([(string) $this->user_id, (string) $user_b], $decoded);
    }

    // -------------------------------------------------------------------------
    // image repeatable — IDs de vrais attachments
    // -------------------------------------------------------------------------

    public function testRepeatableImageSavesAttachmentIdsAsJsonArray(): void
    {
        $att_a = static::factory()->post->create([
            'post_type'      => 'attachment',
            'post_mime_type' => 'image/jpeg',
            'post_title'     => 'Image A',
            'post_status'    => 'inherit',
        ]);
        $att_b = static::factory()->post->create([
            'post_type'      => 'attachment',
            'post_mime_type' => 'image/jpeg',
            'post_title'     => 'Image B',
            'post_status'    => 'inherit',
        ]);

        $box = new MetaBox('medias', 'Médias', 'projet', [
            ['type' => 'image', 'id' => 'photos', 'label' => 'Photos', 'repeatable' => true],
        ]);

        $this->postWith(['photos' => [(string) $att_a, (string) $att_b]]);
        $box->savePost($this->post_id);

        $decoded = Field::decodeMetaValue(get_post_meta($this->post_id, 'photos', true));

        $this->assertIsArray($decoded);
        $this->assertSame([(string) $att_a, (string) $att_b], $decoded);
    }

    public function testRepeatableImageOverwriteReplacesAttachmentIds(): void
    {
        $att_a = static::factory()->post->create(['post_type' => 'attachment', 'post_mime_type' => 'image/jpeg', 'post_status' => 'inherit']);
        $att_b = static::factory()->post->create(['post_type' => 'attachment', 'post_mime_type' => 'image/jpeg', 'post_status' => 'inherit']);
        $att_c = static::factory()->post->create(['post_type' => 'attachment', 'post_mime_type' => 'image/jpeg', 'post_status' => 'inherit']);

        $box = new MetaBox('medias', 'Médias', 'projet', [
            ['type' => 'image', 'id' => 'photos_ow', 'label' => 'Photos', 'repeatable' => true],
        ]);

        $this->postWith(['photos_ow' => [(string) $att_a, (string) $att_b]]);
        $box->savePost($this->post_id);

        $this->postWith(['photos_ow' => [(string) $att_c]]);
        $box->savePost($this->post_id);

        $decoded = Field::decodeMetaValue(get_post_meta($this->post_id, 'photos_ow', true));

        $this->assertCount(1, $decoded);
        $this->assertSame((string) $att_c, $decoded[0]);
    }

    // -------------------------------------------------------------------------
    // post_select repeatable — IDs de vrais posts
    // -------------------------------------------------------------------------

    public function testRepeatablePostSelectSavesPostIdsAsJsonArray(): void
    {
        $id_a = static::factory()->post->create(['post_title' => 'Article A', 'post_status' => 'publish']);
        $id_b = static::factory()->post->create(['post_title' => 'Article B', 'post_status' => 'publish']);

        $box = new MetaBox('relations', 'Relations', 'projet', [
            ['type' => 'post_select', 'id' => 'articles_liés', 'label' => 'Articles liés', 'repeatable' => true],
        ]);

        $this->postWith(['articles_liés' => [(string) $id_a, (string) $id_b]]);
        $box->savePost($this->post_id);

        $decoded = Field::decodeMetaValue(get_post_meta($this->post_id, 'articles_liés', true));

        $this->assertIsArray($decoded);
        $this->assertSame([(string) $id_a, (string) $id_b], $decoded);
    }

    public function testRepeatablePostSelectOverwriteReplacesIds(): void
    {
        $id_a = static::factory()->post->create(['post_status' => 'publish']);
        $id_b = static::factory()->post->create(['post_status' => 'publish']);
        $id_c = static::factory()->post->create(['post_status' => 'publish']);

        $box = new MetaBox('relations', 'Relations', 'projet', [
            ['type' => 'post_select', 'id' => 'related_posts', 'label' => 'Related', 'repeatable' => true],
        ]);

        $this->postWith(['related_posts' => [(string) $id_a, (string) $id_b]]);
        $box->savePost($this->post_id);

        $this->postWith(['related_posts' => [(string) $id_c]]);
        $box->savePost($this->post_id);

        $decoded = Field::decodeMetaValue(get_post_meta($this->post_id, 'related_posts', true));

        $this->assertCount(1, $decoded);
        $this->assertSame((string) $id_c, $decoded[0]);
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
