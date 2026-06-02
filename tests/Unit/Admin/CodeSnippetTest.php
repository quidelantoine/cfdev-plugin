<?php

namespace Weblitzer\CFDev\Tests\Unit\Admin;

use ReflectionMethod;
use Weblitzer\CFDev\Admin\DashboardPage;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

/**
 * Tests for DashboardPage::codeSnippet(), ::fieldLines(), ::fieldLinesRaw().
 *
 * All three are pure string-generation functions — no DB, no WP environment needed
 * beyond what CFDevTestCase already stubs (i18n functions).
 */
class CodeSnippetTest extends CFDevTestCase
{
    // ── Reflection helpers ────────────────────────────────────────────────────

    /**
     * @param  array<string,mixed> $field
     * @return list<string>
     */
    private static function callFieldLines(string $fid, array $field, string $src = '$group'): array
    {
        $m      = new ReflectionMethod(DashboardPage::class, 'fieldLines');
        $result = $m->invoke(null, $fid, $field, $src);
        return array_map('strval', is_array($result) ? array_values($result) : []);
    }

    /**
     * @param  array<string,mixed> $field
     * @return list<string>
     */
    private static function callFieldLinesRaw(string $fid, array $field, string $src = '$group'): array
    {
        $m      = new ReflectionMethod(DashboardPage::class, 'fieldLinesRaw');
        $result = $m->invoke(null, $fid, $field, $src);
        return array_map('strval', is_array($result) ? array_values($result) : []);
    }

    /** @param array<string,mixed> $entry */
    private static function callCodeSnippet(array $entry, bool $raw = false): string
    {
        $m      = new ReflectionMethod(DashboardPage::class, 'codeSnippet');
        $result = $m->invoke(null, $entry, $raw);
        return is_string($result) ? $result : '';
    }

    /** @param string[] $lines */
    private static function j(array $lines): string
    {
        return implode("\n", $lines);
    }

    /**
     * @param string[]             $targets
     * @param array<string,mixed>  $fields
     * @param array<string,mixed>  $bundles
     * @return array<string,mixed>
     */
    private function entry(
        string $meta_type = 'post',
        array $targets = ['post'],
        array $fields = [],
        array $bundles = []
    ): array {
        return [
            'id'        => 'test_group',
            'meta_type' => $meta_type,
            'targets'   => $targets,
            'layout'    => 'flat',
            'fields'    => $fields,
            'sections'  => [],
            'bundles'   => $bundles,
        ];
    }

    // ── fieldLines() — correct escape / WP functions used ────────────────────

    public function testImageUsesWpGetAttachmentImageAndEscFunctions(): void
    {
        $code = self::j(self::callFieldLines('cover', ['type' => 'image', 'label' => 'Cover']));

        $this->assertStringContainsString('wp_get_attachment_image', $code);
        $this->assertStringContainsString('esc_url', $code);
        $this->assertStringContainsString('esc_attr', $code);
        $this->assertStringContainsString("['sizes']", $code);
        $this->assertStringContainsString('thumbnail', $code);
        $this->assertStringContainsString('medium', $code);
        $this->assertStringContainsString('large', $code);
    }

    public function testEmailValidatesWithIsEmailAfterSanitize(): void
    {
        $code = self::j(self::callFieldLines('email', ['type' => 'email', 'label' => 'Email']));

        $this->assertStringContainsString('sanitize_email', $code);
        $this->assertStringContainsString('is_email', $code);
        $this->assertStringContainsString('esc_attr', $code);
        $this->assertStringContainsString('mailto:', $code);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dateTypeProvider')]
    public function testDateTypesUseWpDateNotDateI18n(string $type): void
    {
        $code = self::j(self::callFieldLines('d', ['type' => $type, 'label' => 'Date']));

        $this->assertStringContainsString('wp_date', $code);
        $this->assertStringNotContainsString('date_i18n', $code);
    }

    /** @return array<string,array{string}> */
    public static function dateTypeProvider(): array
    {
        return [
            'date'     => ['date'],
            'datetime' => ['datetime'],
            'time'     => ['time'],
        ];
    }

    public function testPostCheckboxesUsesAbsintAndNoFoundRows(): void
    {
        $code = self::j(self::callFieldLines('posts', ['type' => 'post_checkboxes', 'label' => 'Posts']));

        $this->assertStringContainsString("array_map('absint'", $code);
        $this->assertStringContainsString('no_found_rows', $code);
        $this->assertStringNotContainsString("array_map('intval'", $code);
    }

    public function testTermCheckboxesGuardsGetTermLinkWithIsWpError(): void
    {
        $code = self::j(self::callFieldLines('cats', ['type' => 'term_checkboxes', 'label' => 'Cats']));

        $this->assertStringContainsString("array_map('absint'", $code);
        $this->assertStringContainsString('is_wp_error', $code);
        $this->assertStringContainsString('get_term_link', $code);
        $this->assertStringNotContainsString("array_map('intval'", $code);
    }

    public function testTermSelectGuardsGetTermLinkWithIsWpError(): void
    {
        $code = self::j(self::callFieldLines('cat', ['type' => 'term_select', 'label' => 'Cat']));

        $this->assertStringContainsString('absint', $code);
        $this->assertStringContainsString('is_wp_error', $code);
        $this->assertStringContainsString('get_term_link', $code);
        $this->assertStringNotContainsString('intval', $code);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idFieldTypeProvider')]
    public function testIdFieldsUseAbsintNotIntval(string $type): void
    {
        $code = self::j(self::callFieldLines('id_f', ['type' => $type, 'label' => 'ID']));

        $this->assertStringContainsString('absint', $code);
        $this->assertStringNotContainsString('intval', $code);
    }

    /** @return array<string,array{string}> */
    public static function idFieldTypeProvider(): array
    {
        return [
            'post_select' => ['post_select'],
            'user_select' => ['user_select'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('boolFieldTypeProvider')]
    public function testBoolFieldsGenerateBothIfAndElseBranches(string $type): void
    {
        $code = self::j(self::callFieldLines('flag', ['type' => $type, 'label' => 'Flag']));

        $this->assertStringContainsString('} else {', $code);
        $this->assertStringContainsString('! empty', $code);
    }

    /** @return array<string,array{string}> */
    public static function boolFieldTypeProvider(): array
    {
        return [
            'toggle'  => ['toggle'],
            'yesno'   => ['yesno'],
            'checkbox' => ['checkbox'],
        ];
    }

    public function testWysiwygUsesWpKsesPostNotEscHtml(): void
    {
        $code = self::j(self::callFieldLines('body', ['type' => 'wysiwyg', 'label' => 'Body']));

        $this->assertStringContainsString('wp_kses_post', $code);
        $this->assertStringNotContainsString('esc_html', $code);
    }

    public function testTextareaUsesNl2brAndEscHtml(): void
    {
        $code = self::j(self::callFieldLines('note', ['type' => 'textarea', 'label' => 'Note']));

        $this->assertStringContainsString('nl2br', $code);
        $this->assertStringContainsString('esc_html', $code);
    }

    public function testLinkIncludesTargetAndRelNoopener(): void
    {
        $code = self::j(self::callFieldLines('lnk', ['type' => 'link', 'label' => 'Link']));

        $this->assertStringContainsString('target="_blank"', $code);
        $this->assertStringContainsString('noopener', $code);
        $this->assertStringContainsString('noreferrer', $code);
        $this->assertStringContainsString('esc_url', $code);
        $this->assertStringContainsString('esc_html', $code);
    }

    public function testUnknownTypeFallsBackToEscHtml(): void
    {
        $code = self::j(self::callFieldLines('x', ['type' => 'unknown_xyz', 'label' => 'X']));

        $this->assertStringContainsString('esc_html', $code);
    }

    public function testBundleSrcIsForwardedIntoGeneratedCode(): void
    {
        $lines = self::callFieldLines('title', ['type' => 'text', 'label' => 'Title'], '$row');
        $code  = self::j($lines);

        $this->assertStringContainsString("\$row['title']", $code);
        $this->assertStringNotContainsString("\$group['title']", $code);
    }

    // ── fieldLinesRaw() — no echo, no HTML, direct access only ───────────────

    #[\PHPUnit\Framework\Attributes\DataProvider('rawTypeProvider')]
    public function testRawContainsNoEchoOrHtmlTags(string $type): void
    {
        $code = self::j(self::callFieldLinesRaw('f', ['type' => $type, 'label' => 'L']));

        $this->assertStringNotContainsString('echo', $code);
        $this->assertStringNotContainsString('<a ', $code);
        $this->assertStringNotContainsString('<img', $code);
    }

    /** @return array<string,array{string}> */
    public static function rawTypeProvider(): array
    {
        return [
            'text'        => ['text'],
            'image'       => ['image'],
            'gallery'     => ['gallery'],
            'file'        => ['file'],
            'link'        => ['link'],
            'post_select' => ['post_select'],
            'term_select' => ['term_select'],
            'user_select' => ['user_select'],
            'toggle'      => ['toggle'],
            'email'       => ['email'],
            'date'        => ['date'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('rawIdTypeProvider')]
    public function testRawIdFieldsUseAbsint(string $type): void
    {
        $code = self::j(self::callFieldLinesRaw('id', ['type' => $type, 'label' => 'ID']));

        $this->assertStringContainsString('absint', $code);
        $this->assertStringNotContainsString('intval', $code);
    }

    /** @return array<string,array{string}> */
    public static function rawIdTypeProvider(): array
    {
        return [
            'post_select' => ['post_select'],
            'term_select' => ['term_select'],
            'user_select' => ['user_select'],
        ];
    }

    public function testRawToggleExposesDirectBoolExpression(): void
    {
        $lines = self::callFieldLinesRaw('active', ['type' => 'toggle', 'label' => 'Active']);

        $this->assertStringContainsString('! empty', self::j($lines));
        $this->assertStringNotContainsString(' = ', $lines[1]);
    }

    public function testRawImageDocumentsSizesInComment(): void
    {
        $code = self::j(self::callFieldLinesRaw('img', ['type' => 'image', 'label' => 'Image']));

        $this->assertStringContainsString("['sizes']", $code);
        $this->assertStringContainsString('thumbnail', $code);
        $this->assertStringContainsString('medium', $code);
    }

    public function testRawLinkDocumentsUrlTextAndTarget(): void
    {
        $code = self::j(self::callFieldLinesRaw('lnk', ['type' => 'link', 'label' => 'Link']));

        $this->assertStringContainsString("['url']", $code);
        $this->assertStringContainsString("['text']", $code);
        $this->assertStringContainsString("['target']", $code);
    }

    // ── codeSnippet() — header and structure ─────────────────────────────────

    public function testSnippetPostTypeGeneratesCorrectCacheCall(): void
    {
        $code = self::callCodeSnippet($this->entry('post'));

        $this->assertStringContainsString('post($post->ID)', $code);
        $this->assertStringContainsString('CacheManager', $code);
        $this->assertStringContainsString("\$group = \$data['groups']['test_group']", $code);
    }

    public function testSnippetTermTypeIncludesTaxonomyInCall(): void
    {
        $code = self::callCodeSnippet($this->entry('term', ['category']));

        $this->assertStringContainsString("term(\$term->term_id, 'category')", $code);
    }

    public function testSnippetUserTypeGeneratesCorrectCacheCall(): void
    {
        $code = self::callCodeSnippet($this->entry('user', []));

        $this->assertStringContainsString('user($user->ID)', $code);
    }

    public function testSnippetRawIsShorterThanDisplay(): void
    {
        $entry = $this->entry('post', ['post'], [
            'title' => ['type' => 'text', 'label' => 'Title'],
            'body'  => ['type' => 'wysiwyg', 'label' => 'Body'],
        ]);

        $this->assertLessThan(
            strlen(self::callCodeSnippet($entry, false)),
            strlen(self::callCodeSnippet($entry, true))
        );
    }

    public function testSnippetBundleWrapsFieldsInForeachRows(): void
    {
        $entry = $this->entry('post', ['post'], [], [
            'chapters' => [
                'fields' => ['heading' => ['type' => 'text', 'label' => 'Heading']],
            ],
        ]);
        $code = self::callCodeSnippet($entry);

        $this->assertStringContainsString('foreach ($rows as $row)', $code);
        $this->assertStringContainsString("\$rows = \$group['chapters']", $code);
    }

    public function testSnippetGroupIdAppearsInHeader(): void
    {
        $entry       = $this->entry();
        $entry['id'] = 'my_books_group';
        $code        = self::callCodeSnippet($entry);

        $this->assertStringContainsString("'my_books_group'", $code);
    }

    public function testSnippetBundleFieldUsesRowAsSrc(): void
    {
        $entry = $this->entry('post', ['post'], [], [
            'items' => ['fields' => ['label' => ['type' => 'text', 'label' => 'Label']]],
        ]);
        $code = self::callCodeSnippet($entry);

        $this->assertStringContainsString("\$row['label']", $code);
        $this->assertStringNotContainsString("\$group['label']", $code);
    }

    // ── codeSnippet() — option meta type ─────────────────────────────────────

    public function testOptionSnippetUsesGetOptionForFlatField(): void
    {
        $entry = $this->entry('option', ['site_settings'], ['api_key' => ['type' => 'text', 'label' => 'API Key']]);
        $code  = self::callCodeSnippet($entry);

        $this->assertStringContainsString('get_option', $code);
        $this->assertStringNotContainsString('CacheManager', $code);
        $this->assertStringNotContainsString('$group', $code);
    }

    public function testOptionSnippetBuildOptsArrayForMultipleFlatFields(): void
    {
        $entry = $this->entry('option', ['site_settings'], [
            'api_key'  => ['type' => 'text', 'label' => 'API Key'],
            'site_url' => ['type' => 'url',  'label' => 'Site URL'],
        ]);
        $code = self::callCodeSnippet($entry);

        $this->assertStringContainsString('$opts', $code);
        $this->assertStringContainsString("get_option('api_key')", $code);
        $this->assertStringContainsString("get_option('site_url')", $code);
        $this->assertStringContainsString("\$opts['api_key']", $code);
        $this->assertStringContainsString("\$opts['site_url']", $code);
    }

    public function testOptionSnippetFieldValueReadFromOptsArray(): void
    {
        $entry = $this->entry('option', ['settings'], ['title' => ['type' => 'text', 'label' => 'Title']]);
        $code  = self::callCodeSnippet($entry);

        $this->assertStringContainsString("\$opts['title']", $code);
    }

    public function testOptionBundleSnippetUsesDecodeMetaValue(): void
    {
        $entry = $this->entry('option', ['settings'], [], [
            '_rows' => ['fields' => ['name' => ['type' => 'text', 'label' => 'Name']]],
        ]);
        $code = self::callCodeSnippet($entry);

        $this->assertStringContainsString('Field::decodeMetaValue', $code);
        $this->assertStringContainsString("get_option('_rows')", $code);
    }

    public function testOptionBundleSnippetWrapsFieldsInForeach(): void
    {
        $entry = $this->entry('option', ['settings'], [], [
            '_rows' => ['fields' => ['name' => ['type' => 'text', 'label' => 'Name']]],
        ]);
        $code = self::callCodeSnippet($entry);

        $this->assertStringContainsString('foreach ($rows as $row)', $code);
    }

    public function testOptionBundleSnippetBundleFieldUsesRowAsSrc(): void
    {
        $entry = $this->entry('option', ['settings'], [], [
            '_rows' => ['fields' => ['label' => ['type' => 'text', 'label' => 'Label']]],
        ]);
        $code = self::callCodeSnippet($entry);

        $this->assertStringContainsString("\$row['label']", $code);
        $this->assertStringNotContainsString("\$opts['label']", $code);
    }

    public function testOptionSnippetWithSectionsAndBundleUsesDecodeMetaValue(): void
    {
        $entry              = $this->entry('option', ['settings']);
        $entry['bundles']   = ['_slides' => ['fields' => ['title' => ['type' => 'text', 'label' => 'Title']]]];
        $entry['sections']  = [
            ['title' => 'Slides', 'bundle_id' => '_slides', 'fields' => []],
        ];
        $code = self::callCodeSnippet($entry);

        $this->assertStringContainsString('Field::decodeMetaValue', $code);
        $this->assertStringContainsString("get_option('_slides')", $code);
        $this->assertStringContainsString('foreach ($rows as $row)', $code);
        $this->assertStringContainsString('// Slides', $code);
    }

    public function testOptionSnippetDoesNotContainCacheManagerForAnyLayout(): void
    {
        $entry = $this->entry('option', ['settings'], ['k' => ['type' => 'text', 'label' => 'K']]);
        $code  = self::callCodeSnippet($entry);

        $this->assertStringNotContainsString('CacheManager', $code);
        $this->assertStringNotContainsString('CacheResolver', $code);
    }

    public function testOptionRawSnippetContainsNoEchoOrHtml(): void
    {
        $entry = $this->entry('option', ['settings'], ['title' => ['type' => 'text', 'label' => 'Title']]);
        $code  = self::callCodeSnippet($entry, true);

        $this->assertStringNotContainsString('echo', $code);
        $this->assertStringNotContainsString('<a ', $code);
    }
}
