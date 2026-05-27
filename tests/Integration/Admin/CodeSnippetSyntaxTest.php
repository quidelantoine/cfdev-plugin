<?php

namespace Weblitzer\CFDev\Tests\Integration\Admin;

use ReflectionMethod;
use Weblitzer\CFDev\Admin\DashboardPage;
use Weblitzer\CFDev\Tests\Integration\IntegrationTestCase;

/**
 * Integration tests for DashboardPage code-snippet generation.
 *
 * Unit tests (CodeSnippetTest) verify content (security functions, structure).
 * These tests verify that every generated snippet is syntactically valid PHP —
 * something only a real PHP parser can confirm.
 */
class CodeSnippetSyntaxTest extends IntegrationTestCase
{
    // ── Helpers ──────────────────────────────────────────────────────────────

    /** @param array<string,mixed> $entry */
    private static function snippet(array $entry, bool $raw = false): string
    {
        $m      = new ReflectionMethod(DashboardPage::class, 'codeSnippet');
        $result = $m->invoke(null, $entry, $raw);
        return is_string($result) ? $result : '';
    }

    private static function assertValidPhp(string $code): void
    {
        $tmp = tempnam(get_temp_dir(), 'cfdev_snip_') . '.php'; // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_tempnam
        file_put_contents($tmp, $code); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
        exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $out, $status); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
        unlink($tmp); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
        self::assertSame(0, $status, "PHP syntax error in generated snippet:\n" . implode("\n", $out));
    }

    /**
     * @param string[]            $targets
     * @param array<string,mixed> $fields
     * @param array<string,mixed> $bundles
     * @return array<string,mixed>
     */
    private function entry(
        string $meta_type = 'post',
        array $targets = ['post'],
        array $fields = [],
        array $bundles = []
    ): array {
        return [
            'id'        => 'syntax_group',
            'meta_type' => $meta_type,
            'targets'   => $targets,
            'layout'    => 'flat',
            'fields'    => $fields,
            'sections'  => [],
            'bundles'   => $bundles,
        ];
    }

    /** @return array<string,mixed> */
    private function allFields(): array
    {
        return [
            'txt'   => ['type' => 'text',            'label' => 'Text'],
            'num'   => ['type' => 'number',           'label' => 'Number'],
            'rng'   => ['type' => 'range',            'label' => 'Range'],
            'area'  => ['type' => 'textarea',         'label' => 'Textarea'],
            'ed'    => ['type' => 'wysiwyg',          'label' => 'Wysiwyg'],
            'img'   => ['type' => 'image',            'label' => 'Image'],
            'gal'   => ['type' => 'gallery',          'label' => 'Gallery'],
            'fil'   => ['type' => 'file',             'label' => 'File'],
            'lnk'   => ['type' => 'link',             'label' => 'Link'],
            'eml'   => ['type' => 'email',            'label' => 'Email'],
            'dt'    => ['type' => 'date',             'label' => 'Date'],
            'dtt'   => ['type' => 'datetime',         'label' => 'Datetime'],
            'tm'    => ['type' => 'time',             'label' => 'Time'],
            'tog'   => ['type' => 'toggle',           'label' => 'Toggle'],
            'yn'    => ['type' => 'yesno',            'label' => 'Yesno'],
            'chk'   => ['type' => 'checkbox',         'label' => 'Checkbox'],
            'sel'   => ['type' => 'select',           'label' => 'Select'],
            'col'   => ['type' => 'color',            'label' => 'Color'],
            'psel'  => ['type' => 'post_select',      'label' => 'Post select'],
            'tsel'  => ['type' => 'term_select',      'label' => 'Term select'],
            'usel'  => ['type' => 'user_select',      'label' => 'User select'],
            'pchk'  => ['type' => 'post_checkboxes',  'label' => 'Post checkboxes'],
            'tchk'  => ['type' => 'term_checkboxes',  'label' => 'Term checkboxes'],
            'uchk'  => ['type' => 'user_checkboxes',  'label' => 'User checkboxes'],
        ];
    }

    // ── Display mode — valid PHP ──────────────────────────────────────────────

    public function testAllFieldTypesDisplayModeIsValidPhp(): void
    {
        self::assertValidPhp(
            self::snippet($this->entry('post', ['post'], $this->allFields()))
        );
    }

    public function testTermMetaDisplayModeIsValidPhp(): void
    {
        self::assertValidPhp(
            self::snippet($this->entry('term', ['category'], $this->allFields()))
        );
    }

    public function testUserMetaDisplayModeIsValidPhp(): void
    {
        self::assertValidPhp(
            self::snippet($this->entry('user', [], $this->allFields()))
        );
    }

    // ── Raw mode — valid PHP ──────────────────────────────────────────────────

    public function testAllFieldTypesRawModeIsValidPhp(): void
    {
        self::assertValidPhp(
            self::snippet($this->entry('post', ['post'], $this->allFields()), true)
        );
    }

    public function testTermMetaRawModeIsValidPhp(): void
    {
        self::assertValidPhp(
            self::snippet($this->entry('term', ['category'], $this->allFields()), true)
        );
    }

    public function testUserMetaRawModeIsValidPhp(): void
    {
        self::assertValidPhp(
            self::snippet($this->entry('user', [], $this->allFields()), true)
        );
    }

    // ── Bundles — valid PHP ───────────────────────────────────────────────────

    public function testBundleDisplayModeIsValidPhp(): void
    {
        $entry = $this->entry('post', ['post'], [], [
            'chapters' => [
                'fields' => [
                    'title'  => ['type' => 'text',    'label' => 'Title'],
                    'cover'  => ['type' => 'image',   'label' => 'Cover'],
                    'active' => ['type' => 'toggle',  'label' => 'Active'],
                    'lnk'    => ['type' => 'link',    'label' => 'Link'],
                    'eml'    => ['type' => 'email',   'label' => 'Email'],
                ],
            ],
        ]);
        self::assertValidPhp(self::snippet($entry));
    }

    public function testBundleRawModeIsValidPhp(): void
    {
        $entry = $this->entry('post', ['post'], [], [
            'items' => [
                'fields' => [
                    'label' => ['type' => 'text',         'label' => 'Label'],
                    'img'   => ['type' => 'image',        'label' => 'Image'],
                    'psel'  => ['type' => 'post_select',  'label' => 'Post'],
                    'tog'   => ['type' => 'toggle',       'label' => 'Active'],
                ],
            ],
        ]);
        self::assertValidPhp(self::snippet($entry, true));
    }

    public function testMultipleBundlesIsValidPhp(): void
    {
        $entry = $this->entry('post', ['post'], [
            'intro' => ['type' => 'wysiwyg', 'label' => 'Intro'],
        ], [
            'slides' => [
                'fields' => ['img' => ['type' => 'image', 'label' => 'Image']],
            ],
            'faqs'   => [
                'fields' => [
                    'q' => ['type' => 'text',    'label' => 'Question'],
                    'a' => ['type' => 'wysiwyg', 'label' => 'Answer'],
                ],
            ],
        ]);
        self::assertValidPhp(self::snippet($entry));
        self::assertValidPhp(self::snippet($entry, true));
    }
}
