<?php

namespace Weblitzer\CFDev\Tests\Unit\Meta;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

/**
 * Tests Meta::validateFields() via MetaBox — covers flat, bundle, and tabs paths.
 * validateFields() is protected; we trigger it through savePost() / saveTerm() guards
 * or test its effects via ErrorBag when called from the save methods.
 *
 * Here we use a thin subclass to expose validateFields() directly.
 */
class ValidateFieldsTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('sanitize_title')->alias(function (string $s): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $s), '-'));
        });
    }

    /**
     * Exposes the protected validateFields() for direct testing.
     *
     * @param array<mixed> $data
     */
    private function makeMetaBox(array $data = []): MetaBox
    {
        return new MetaBox('test_mb', 'Test', 'post', $data);
    }

    /** @return array<string, mixed> */
    private function requiredText(string $name): array
    {
        return [
            'type'     => 'text',
            'name'     => $name,
            'label'    => ucfirst($name),
            'required' => true,
        ];
    }

    /** @return array<string, mixed> */
    private function optionalText(string $name): array
    {
        return ['type' => 'text', 'name' => $name, 'label' => ucfirst($name)];
    }

    /**
     * Call the protected validateFields() via Reflection.
     *
     * @param  array<string, mixed>                                       $values
     * @return array<string, array{label: string, errors: string[]}>
     */
    private function validate(MetaBox $mb, array $values): array
    {
        $ref = new \ReflectionMethod(MetaBox::class, 'validateFields');
        $ref->setAccessible(true);
        /** @var array<string, array{label: string, errors: string[]}> */
        return $ref->invoke($mb, $values);
    }

    // -------------------------------------------------------------------------
    // Flat fields
    // -------------------------------------------------------------------------

    public function testFlatPassingFieldReturnsNoErrors(): void
    {
        $mb = $this->makeMetaBox([$this->requiredText('title')]);
        $field = array_values($mb->fields)[0];

        $errors = $this->validate($mb, [$field->id => 'Hello']);

        $this->assertEmpty($errors);
    }

    public function testFlatFailingRequiredFieldReturnsError(): void
    {
        $mb    = $this->makeMetaBox([$this->requiredText('title')]);
        $field = array_values($mb->fields)[0];

        $errors = $this->validate($mb, [$field->id => '']);

        $this->assertArrayHasKey($field->id, $errors);
        $this->assertNotEmpty($errors[$field->id]['errors']);
    }

    public function testFlatMissingValueTreatedAsEmpty(): void
    {
        $mb    = $this->makeMetaBox([$this->requiredText('title')]);
        $field = array_values($mb->fields)[0];

        $errors = $this->validate($mb, []); // key absent → ''

        $this->assertArrayHasKey($field->id, $errors);
    }

    public function testFlatSkipsBundleInlineFields(): void
    {
        $mb = $this->makeMetaBox([$this->requiredText('title')]);
        $field = array_values($mb->fields)[0];
        $field->in_bundle = true;

        $errors = $this->validate($mb, []);

        $this->assertEmpty($errors);
    }

    public function testFlatMultipleFieldsCollectsAllErrors(): void
    {
        $mb = $this->makeMetaBox([
            $this->requiredText('title'),
            $this->requiredText('subtitle'),
        ]);

        $errors = $this->validate($mb, []);

        $this->assertCount(2, $errors);
    }

    public function testFlatOptionalFieldPassesWhenEmpty(): void
    {
        $mb    = $this->makeMetaBox([$this->optionalText('title')]);
        $field = array_values($mb->fields)[0];

        $errors = $this->validate($mb, [$field->id => '']);

        $this->assertEmpty($errors);
    }

    // -------------------------------------------------------------------------
    // Bundle path
    // -------------------------------------------------------------------------

    public function testBundleRequiredFieldFailsForEmptyRow(): void
    {
        $mb = $this->makeMetaBox(['bundle', 'details', [$this->requiredText('name')]]);
        /** @var \Weblitzer\CFDev\Fields\Bundle $bundle */
        $bundle = $mb->data;
        $field  = array_values($bundle->fields)[0];

        $errors = $this->validate($mb, [
            $bundle->id => [
                ['name' => $field->id, $field->id => ''],  // empty → required fails
            ],
        ]);

        $this->assertNotEmpty($errors);
        // key uses dot-notation: bundle_id.row_index.field_id
        $expectedKey = $bundle->id . '.0.' . $field->id;
        $this->assertArrayHasKey($expectedKey, $errors);
    }

    public function testBundlePassingRowReturnsNoErrors(): void
    {
        $mb = $this->makeMetaBox(['bundle', 'details', [$this->requiredText('name')]]);
        /** @var \Weblitzer\CFDev\Fields\Bundle $bundle */
        $bundle = $mb->data;
        $field  = array_values($bundle->fields)[0];

        $errors = $this->validate($mb, [
            $bundle->id => [[$field->id => 'Alice']],
        ]);

        $this->assertEmpty($errors);
    }

    public function testBundleErrorKeyUsesRowIndex(): void
    {
        $mb = $this->makeMetaBox(['bundle', 'details', [$this->requiredText('name')]]);
        /** @var \Weblitzer\CFDev\Fields\Bundle $bundle */
        $bundle = $mb->data;
        $field  = array_values($bundle->fields)[0];

        $errors = $this->validate($mb, [
            $bundle->id => [
                [$field->id => 'Alice'], // row 0: OK
                [$field->id => ''],      // row 1: fails
            ],
        ]);

        $this->assertArrayHasKey($bundle->id . '.1.' . $field->id, $errors);
        $this->assertArrayNotHasKey($bundle->id . '.0.' . $field->id, $errors);
    }

    public function testBundleNoRowsReturnsNoErrors(): void
    {
        $mb = $this->makeMetaBox(['bundle', 'details', [$this->requiredText('name')]]);
        /** @var \Weblitzer\CFDev\Fields\Bundle $bundle */
        $bundle = $mb->data;

        $errors = $this->validate($mb, [$bundle->id => []]);

        $this->assertEmpty($errors);
    }

    // -------------------------------------------------------------------------
    // Tabs path
    // -------------------------------------------------------------------------

    public function testTabsFlatFieldFailureReturnsError(): void
    {
        $mb = $this->makeMetaBox(['tabs', [
            'General' => [$this->requiredText('title')],
        ]]);
        $field = array_values($mb->fields)[0];

        $errors = $this->validate($mb, [$field->id => '']);

        $this->assertArrayHasKey($field->id, $errors);
    }

    public function testTabsFieldPassingReturnsNoErrors(): void
    {
        $mb = $this->makeMetaBox(['tabs', [
            'General' => [$this->requiredText('title')],
        ]]);
        $field = array_values($mb->fields)[0];

        $errors = $this->validate($mb, [$field->id => 'Hello']);

        $this->assertEmpty($errors);
    }

    public function testTabsMultipleSectionsCollectsAllErrors(): void
    {
        $mb = $this->makeMetaBox(['tabs', [
            'General'  => [$this->requiredText('title')],
            'Advanced' => [$this->requiredText('slug')],
        ]]);

        $errors = $this->validate($mb, []);

        $this->assertCount(2, $errors);
    }

    // -------------------------------------------------------------------------
    // Tabs + Bundle path
    // -------------------------------------------------------------------------

    public function testTabsBundleInTabValidatesWithDotNotation(): void
    {
        $mb = $this->makeMetaBox(['tabs', [
            'Rows' => [['bundle', 'details', [$this->requiredText('name')]]],
        ]]);

        /** @var \Weblitzer\CFDev\Fields\Tabs $tabs */
        $tabs   = $mb->data;
        $tab    = array_values((array) $tabs->tabs)[0];
        /** @var \Weblitzer\CFDev\Fields\Bundle $bundle */
        $bundle = $tab->fields;
        $field  = array_values($bundle->fields)[0];

        $errors = $this->validate($mb, [
            $bundle->id => [[$field->id => '']],
        ]);

        $this->assertNotEmpty($errors);
        $expectedKey = $bundle->id . '.0.' . $field->id;
        $this->assertArrayHasKey($expectedKey, $errors);
    }

    public function testTabsBundleInTabPassingRowReturnsNoErrors(): void
    {
        $mb = $this->makeMetaBox(['tabs', [
            'Rows' => [['bundle', 'details', [$this->requiredText('name')]]],
        ]]);

        /** @var \Weblitzer\CFDev\Fields\Tabs $tabs */
        $tabs   = $mb->data;
        $tab    = array_values((array) $tabs->tabs)[0];
        /** @var \Weblitzer\CFDev\Fields\Bundle $bundle */
        $bundle = $tab->fields;
        $field  = array_values($bundle->fields)[0];

        $errors = $this->validate($mb, [
            $bundle->id => [[$field->id => 'Alice']],
        ]);

        $this->assertEmpty($errors);
    }
}
