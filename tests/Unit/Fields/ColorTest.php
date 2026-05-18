<?php

namespace CFDev\Tests\Unit\Fields;

use CFDev\Fields\Color;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class ColorTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeField(array $overrides = []): Color
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });

        $defaults = [
            'type' => 'color',
            'name' => 'my_color',
            'label' => 'My Color',
        ];

        return new Color(array_merge($defaults, $overrides), 'my_metabox');
    }

    // -------------------------------------------------------------------------
    // Construction / defaults
    // -------------------------------------------------------------------------

    public function testSupportsAjax(): void
    {
        $this->assertTrue($this->makeField()->supports_ajax);
    }

    public function testSupportsBundle(): void
    {
        $this->assertTrue($this->makeField()->supports_bundle);
    }

    public function testDoesNotSupportRepeatable(): void
    {
        $this->assertFalse($this->makeField()->supports_repeatable);
    }

    // -------------------------------------------------------------------------
    // CSS classes
    // -------------------------------------------------------------------------

    public function testHasJsColorpickerClass(): void
    {
        $this->assertContains('js-cfdev-colorpicker', $this->makeField()->css_classes);
    }

    public function testHasCfdevColorpickerClass(): void
    {
        $this->assertContains('cfdev-colorpicker', $this->makeField()->css_classes);
    }

    public function testHasColorpickerClass(): void
    {
        $this->assertContains('colorpicker', $this->makeField()->css_classes);
    }

    public function testHasCfdevInputClass(): void
    {
        $this->assertContains('cfdev-input', $this->makeField()->css_classes);
    }

    public function testHasExactlyFourDefaultClasses(): void
    {
        $this->assertCount(4, $this->makeField()->css_classes);
    }

    public function testOutputCssClassContainsAllClasses(): void
    {
        $output = $this->makeField()->outputCssClass();

        $this->assertStringContainsString('js-cfdev-colorpicker', $output);
        $this->assertStringContainsString('cfdev-colorpicker', $output);
        $this->assertStringContainsString('colorpicker', $output);
        $this->assertStringContainsString('cfdev-input', $output);
    }

    public function testOutputCssClassMergesExtra(): void
    {
        $output = $this->makeField()->outputCssClass(['my-extra']);
        $this->assertStringContainsString('my-extra', $output);
        $this->assertStringContainsString('cfdev-colorpicker', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — hérité de Field (input text)
    // -------------------------------------------------------------------------

    public function testOutputRendersInputTag(): void
    {
        $output = $this->makeField()->outputHtml('#ff0000');
        $this->assertStringContainsString('<input', $output);
        $this->assertStringContainsString('type="text"', $output);
    }

    public function testOutputContainsValue(): void
    {
        $output = $this->makeField()->outputHtml('#ff0000');
        $this->assertStringContainsString('#ff0000', $output);
    }

    public function testOutputUsesDefaultValueWhenEmpty(): void
    {
        $field = $this->makeField(['default_value' => '#000000']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('#000000', $output);
    }

    public function testOutputContainsColorpickerClass(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('cfdev-colorpicker', $output);
    }
}
