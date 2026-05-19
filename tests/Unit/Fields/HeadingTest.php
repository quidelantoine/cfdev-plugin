<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Fields\Heading;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class HeadingTest extends CFDevTestCase
{
    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Heading
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });

        $defaults = [
            'type'  => 'heading',
            'label' => 'My Section',
        ];

        return new Heading(array_merge($defaults, $overrides), 'my_metabox');
    }

    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    public function testDoesNotSupportRepeatable(): void
    {
        $this->assertFalse($this->makeField()->supports_repeatable);
    }

    public function testDoesNotSupportAjax(): void
    {
        $this->assertFalse($this->makeField()->supports_ajax);
    }

    public function testDoesNotSupportBundle(): void
    {
        $this->assertFalse($this->makeField()->supports_bundle);
    }

    public function testAutoGeneratesUniqueId(): void
    {
        $a = $this->makeField();
        $b = $this->makeField();
        $this->assertStringStartsWith('heading_', $a->id);
        $this->assertNotSame($a->id, $b->id);
    }

    public function testExplicitIdIsPreserved(): void
    {
        $field = $this->makeField(['id' => 'my_heading']);
        $this->assertSame('my_heading', $field->id);
    }

    // -------------------------------------------------------------------------
    // outputHtml
    // -------------------------------------------------------------------------

    public function testOutputRendersH3WithLabel(): void
    {
        $output = $this->makeField(['label' => 'My Section'])->outputHtml('');
        $this->assertStringContainsString('<h3', $output);
        $this->assertStringContainsString('My Section', $output);
        $this->assertStringContainsString('cfdev-heading', $output);
    }

    public function testOutputIgnoresValue(): void
    {
        $output = $this->makeField()->outputHtml('some_value');
        $this->assertStringNotContainsString('some_value', $output);
    }

    public function testOutputRendersDescriptionWhenProvided(): void
    {
        $output = $this->makeField(['description' => 'Help text'])->outputHtml('');
        $this->assertStringContainsString('Help text', $output);
        $this->assertStringContainsString('cfdev-heading-description', $output);
        $this->assertStringContainsString('cfdev-description', $output);
    }

    public function testOutputNoDescriptionByDefault(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('cfdev-heading-description', $output);
    }

    // -------------------------------------------------------------------------
    // save
    // -------------------------------------------------------------------------

    public function testSaveReturnsFalseWithoutSideEffects(): void
    {
        $field = $this->makeField();
        $this->assertFalse($field->save(1, ''));
    }
}
