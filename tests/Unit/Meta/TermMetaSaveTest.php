<?php

namespace Weblitzer\CFDev\Tests\Unit\Meta;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Fields\Heading;
use Weblitzer\CFDev\Fields\Text;
use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class TermMetaSaveTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sanitize_title')->alias(function (string $s): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $s), '-'));
        });
    }

    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    private function makeTermMeta(): TermMeta
    {
        return new TermMeta('genre', '', []);
    }

    private function makeTextField(string $name): Text
    {
        return new Text(['type' => 'text', 'name' => $name, 'label' => ucfirst($name), 'underscore' => false], 'genre');
    }

    // -------------------------------------------------------------------------
    // saveTerm() — early returns
    // -------------------------------------------------------------------------

    public function testSaveTermReturnsEarlyWhenNonceIsMissing(): void
    {
        $_POST = [];
        $this->makeTermMeta()->saveTerm(1);
        $this->addToAssertionCount(1);
    }

    public function testSaveTermReturnsEarlyOnInvalidNonce(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(false);

        $_POST = ['cfdev_nonce' => 'bad_nonce'];
        $this->makeTermMeta()->saveTerm(1);
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // saveTerm() — field loop
    // -------------------------------------------------------------------------

    public function testSaveTermCallsUpdateTermMetaForEachField(): void
    {
        $saved = [];
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('update_term_meta')->alias(function (int $id, string $key, mixed $val) use (&$saved): bool {
            $saved[$key] = $val;
            return true;
        });

        $tm = $this->makeTermMeta();
        $f1 = $this->makeTextField('title');
        $f1->meta_type = 'term';
        $tm->fields[$f1->id] = $f1;

        $f2 = $this->makeTextField('description');
        $f2->meta_type = 'term';
        $tm->fields[$f2->id] = $f2;

        $tm->data = $tm->fields;

        $_POST = [
            'cfdev_nonce' => 'valid',
            'cfdev'       => [$f1->id => 'Action', $f2->id => 'Movies about action'],
        ];

        $tm->saveTerm(1);

        $this->assertArrayHasKey($f1->id, $saved);
        $this->assertArrayHasKey($f2->id, $saved);
        $this->assertSame('Action', $saved[$f1->id]);
        $this->assertSame('Movies about action', $saved[$f2->id]);
    }

    public function testSaveTermSkipsHeadingFields(): void
    {
        $called = false;
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('update_term_meta')->alias(function () use (&$called): bool {
            $called = true;
            return true;
        });

        $tm      = $this->makeTermMeta();
        $heading = new Heading(['type' => 'heading', 'name' => 'section', 'label' => 'Section'], 'genre');
        $tm->fields[$heading->id] = $heading;
        $tm->data = $tm->fields;

        $_POST = ['cfdev_nonce' => 'valid', 'cfdev' => []];
        $tm->saveTerm(1);

        $this->assertFalse($called);
    }

    public function testSaveTermSanitizesStringValueViaSanitizeTextField(): void
    {
        $sanitized = null;
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('sanitize_text_field')->alias(function (string $v) use (&$sanitized): string {
            $sanitized = $v;
            return $v;
        });
        Functions\when('update_term_meta')->justReturn(true);

        $tm = $this->makeTermMeta();
        $f  = $this->makeTextField('title');
        $f->meta_type = 'term';
        $tm->fields[$f->id] = $f;
        $tm->data = $tm->fields;

        $_POST = ['cfdev_nonce' => 'valid', 'cfdev' => [$f->id => 'Genre Name']];
        $tm->saveTerm(1);

        $this->assertSame('Genre Name', $sanitized);
    }

    public function testSaveTermUsesEmptyStringForMissingValue(): void
    {
        $stored = 'NOT_SET';
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('update_term_meta')->alias(function (int $id, string $key, mixed $val) use (&$stored): bool {
            $stored = $val;
            return true;
        });

        $tm = $this->makeTermMeta();
        $f  = $this->makeTextField('title');
        $f->meta_type = 'term';
        $tm->fields[$f->id] = $f;
        $tm->data = $tm->fields;

        $_POST = ['cfdev_nonce' => 'valid', 'cfdev' => []]; // field absent → ''

        $tm->saveTerm(1);

        $this->assertSame('', $stored);
    }

    // -------------------------------------------------------------------------
    // saveTerm() — validation
    // -------------------------------------------------------------------------

    public function testSaveTermPushesValidationErrorsWhenFieldFails(): void
    {
        $pushed = false;
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('get_transient')->justReturn([]);
        Functions\when('set_transient')->alias(function () use (&$pushed): bool {
            $pushed = true;
            return true;
        });
        Functions\when('update_term_meta')->justReturn(true);

        $tm = $this->makeTermMeta();
        $f  = new Text(
            ['type' => 'text', 'name' => 'title', 'label' => 'Title', 'underscore' => false, 'required' => true],
            'genre'
        );
        $f->meta_type = 'term';
        $tm->fields[$f->id] = $f;
        $tm->data = $tm->fields;

        $_POST = ['cfdev_nonce' => 'valid', 'cfdev' => [$f->id => '']]; // empty → Required fails

        $tm->saveTerm(1);

        $this->assertTrue($pushed);
    }

    // -------------------------------------------------------------------------
    // saveTerm() — Bundle dispatch
    // -------------------------------------------------------------------------

    public function testSaveTermBundlePathCallsBundleSave(): void
    {
        $saved = [];
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('delete_term_meta')->justReturn(true);
        Functions\when('update_term_meta')->alias(function (int $id, string $key, mixed $val) use (&$saved): bool {
            $saved[$key] = $val;
            return true;
        });

        $tm = new TermMeta('genre', '', ['bundle', 'details', [
            ['type' => 'text', 'name' => 'name', 'label' => 'Name'],
        ]]);
        /** @var \Weblitzer\CFDev\Fields\Bundle $bundle */
        $bundle = $tm->data;
        $field  = array_values($bundle->fields)[0];

        $_POST = [
            'cfdev_nonce' => 'valid',
            'cfdev'       => [$bundle->id => [[$field->id => 'Alice']]],
        ];

        $tm->saveTerm(5);

        $this->assertArrayHasKey($bundle->id, $saved);
    }

    public function testSaveTermBundleMissingKeyDoesNotCallSave(): void
    {
        $called = false;
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('update_term_meta')->alias(function () use (&$called): bool {
            $called = true;
            return true;
        });

        $tm = new TermMeta('genre', '', ['bundle', 'details', [
            ['type' => 'text', 'name' => 'name', 'label' => 'Name'],
        ]]);

        $_POST = ['cfdev_nonce' => 'valid', 'cfdev' => []]; // bundle key absent

        $tm->saveTerm(5);

        $this->assertFalse($called);
    }

    // -------------------------------------------------------------------------
    // saveTerm() — Tabs flat dispatch
    // -------------------------------------------------------------------------

    public function testSaveTermTabsFlatPathSavesEachTabField(): void
    {
        $saved = [];
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('update_term_meta')->alias(function (int $id, string $key, mixed $val) use (&$saved): bool {
            $saved[$key] = $val;
            return true;
        });

        $tm = new TermMeta('genre', '', ['tabs', [
            'General' => [['type' => 'text', 'name' => 'title', 'label' => 'Title']],
        ]]);
        $field = array_values($tm->fields)[0];

        $_POST = [
            'cfdev_nonce' => 'valid',
            'cfdev'       => [$field->id => 'Action'],
        ];

        $tm->saveTerm(3);

        $this->assertArrayHasKey($field->id, $saved);
        $this->assertSame('Action', $saved[$field->id]);
    }

    public function testSaveTermTabsMultipleSectionsSaveAllFields(): void
    {
        $saved = [];
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('update_term_meta')->alias(function (int $id, string $key, mixed $val) use (&$saved): bool {
            $saved[$key] = $val;
            return true;
        });

        $tm = new TermMeta('genre', '', ['tabs', [
            'General'  => [['type' => 'text', 'name' => 'title', 'label' => 'Title']],
            'Advanced' => [['type' => 'text', 'name' => 'slug', 'label' => 'Slug']],
        ]]);
        $fields = array_values($tm->fields);

        $_POST = [
            'cfdev_nonce' => 'valid',
            'cfdev'       => [$fields[0]->id => 'Action', $fields[1]->id => 'action'],
        ];

        $tm->saveTerm(3);

        $this->assertCount(2, $saved);
    }

    // -------------------------------------------------------------------------
    // saveTerm() — Tabs + Bundle dispatch
    // -------------------------------------------------------------------------

    public function testSaveTermTabsBundlePathCallsBundleSave(): void
    {
        $saved = [];
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('delete_term_meta')->justReturn(true);
        Functions\when('update_term_meta')->alias(function (int $id, string $key, mixed $val) use (&$saved): bool {
            $saved[$key] = $val;
            return true;
        });

        $tm = new TermMeta('genre', '', ['tabs', [
            'Rows' => [['bundle', 'details', [['type' => 'text', 'name' => 'name', 'label' => 'Name']]]],
        ]]);

        /** @var \Weblitzer\CFDev\Fields\Tabs $tabs */
        $tabs   = $tm->data;
        $tab    = array_values((array) $tabs->tabs)[0];
        /** @var \Weblitzer\CFDev\Fields\Bundle $bundle */
        $bundle = $tab->fields;
        $field  = array_values($bundle->fields)[0];

        $_POST = [
            'cfdev_nonce' => 'valid',
            'cfdev'       => [$bundle->id => [[$field->id => 'Alice']]],
        ];

        $tm->saveTerm(4);

        $this->assertArrayHasKey($bundle->id, $saved);
    }

    // -------------------------------------------------------------------------
    // setTitle() — public fluent setter
    // -------------------------------------------------------------------------

    public function testSetTitleChangesTheTitleAndReturnsStatic(): void
    {
        $tm     = $this->makeTermMeta();
        $result = $tm->setTitle('Custom Title');

        $this->assertSame($tm, $result);
        $this->assertSame('Custom Title', $tm->title);
    }

    // -------------------------------------------------------------------------
    // resolveTitle() — private, via reflection
    // -------------------------------------------------------------------------

    public function testResolveTitleReturnsExplicitTitleWhenSet(): void
    {
        $tm = $this->makeTermMeta();
        $tm->setTitle('My Title');

        $method = new \ReflectionMethod(TermMeta::class, 'resolveTitle');
        $method->setAccessible(true);

        $this->assertSame('My Title', $method->invoke($tm, 'genre'));
    }

    public function testResolveTitleUsesGetTaxonomyWhenTitleIsEmpty(): void
    {
        $tax                          = new \stdClass();
        $tax->labels                  = new \stdClass();
        $tax->labels->singular_name   = 'Genre';
        Functions\when('get_taxonomy')->justReturn($tax);

        $tm     = $this->makeTermMeta(); // title = ''
        $method = new \ReflectionMethod(TermMeta::class, 'resolveTitle');
        $method->setAccessible(true);

        $this->assertSame('Genre', $method->invoke($tm, 'genre'));
    }

    public function testResolveTitleFallsBackToUcfirstWhenGetTaxonomyReturnsNull(): void
    {
        Functions\when('get_taxonomy')->justReturn(null);

        $tm     = $this->makeTermMeta();
        $method = new \ReflectionMethod(TermMeta::class, 'resolveTitle');
        $method->setAccessible(true);

        $this->assertSame('Genre', $method->invoke($tm, 'genre'));
    }

    // -------------------------------------------------------------------------
    // resolveObjectId() — protected, via reflection
    // -------------------------------------------------------------------------

    public function testResolveObjectIdReadsTagIdFromGetParam(): void
    {
        $_GET['tag_ID'] = '5';

        $tm     = $this->makeTermMeta();
        $method = new \ReflectionMethod(TermMeta::class, 'resolveObjectId');
        $method->setAccessible(true);

        $result = $method->invoke($tm);
        unset($_GET['tag_ID']);

        $this->assertSame(5, $result);
    }

    public function testResolveObjectIdReturnsZeroWhenTagIdAbsent(): void
    {
        unset($_GET['tag_ID']);

        $tm     = $this->makeTermMeta();
        $method = new \ReflectionMethod(TermMeta::class, 'resolveObjectId');
        $method->setAccessible(true);

        $this->assertSame(0, $method->invoke($tm));
    }
}
