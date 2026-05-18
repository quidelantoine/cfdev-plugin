<?php

namespace CFDev\Tests\Unit\Fields;

use CFDev\Fields\TermSelect;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class TermSelectTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeField(array $overrides = []): TermSelect
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });

        $defaults = [
            'type' => 'term_select',
            'name' => 'my_term',
            'label' => 'My Term',
        ];

        return new TermSelect(array_merge($defaults, $overrides), 'my_metabox');
    }

    // -------------------------------------------------------------------------
    // Construction / defaults
    // -------------------------------------------------------------------------

    public function testSupportsRepeatable(): void
    {
        $this->assertTrue($this->makeField()->supports_repeatable);
    }

    public function testSupportsAjax(): void
    {
        $this->assertTrue($this->makeField()->supports_ajax);
    }

    public function testSupportsBundle(): void
    {
        $this->assertTrue($this->makeField()->supports_bundle);
    }

    // -------------------------------------------------------------------------
    // Construction — args par défaut
    // -------------------------------------------------------------------------

    public function testArgsDefaultTaxonomyIsCategory(): void
    {
        $this->assertSame('category', $this->makeField()->args['taxonomy']);
    }

    public function testArgsDefaultHideEmptyIsZero(): void
    {
        $this->assertSame(0, $this->makeField()->args['hide_empty']);
    }

    public function testArgsEchoIsZero(): void
    {
        $this->assertSame(0, $this->makeField()->args['echo']);
    }

    public function testArgsClassContainsCfdevInput(): void
    {
        $this->assertStringContainsString('cfdev-input', $this->makeField()->args['class']);
    }

    public function testArgsClassContainsCfdevSelect(): void
    {
        $this->assertStringContainsString('cfdev-select', $this->makeField()->args['class']);
    }

    public function testArgsClassContainsCfdevTermSelect(): void
    {
        $this->assertStringContainsString('cfdev-term-select', $this->makeField()->args['class']);
    }

    public function testArgsCustomTaxonomyIsRespected(): void
    {
        $field = $this->makeField(['args' => ['taxonomy' => 'post_tag']]);
        $this->assertSame('post_tag', $field->args['taxonomy']);
    }

    public function testArgsCustomClassIsMerged(): void
    {
        $field = $this->makeField(['args' => ['class' => 'my-custom']]);
        $this->assertStringContainsString('my-custom', $field->args['class']);
        $this->assertStringContainsString('cfdev-input', $field->args['class']);
    }

    public function testArgsHideEmptyCanBeOverridden(): void
    {
        $field = $this->makeField(['args' => ['hide_empty' => 1]]);
        $this->assertSame(1, $field->args['hide_empty']);
    }

    // -------------------------------------------------------------------------
    // outputHtml — appel wp_dropdown_categories
    // -------------------------------------------------------------------------

    public function testOutputCallsWpDropdownCategories(): void
    {
        $this->addToAssertionCount(1);

        Functions\expect('wp_dropdown_categories')
            ->once()
            ->andReturn('<select></select>');

        $this->makeField()->outputHtml('');
    }

    public function testOutputPassesSelectedValue(): void
    {
        $this->addToAssertionCount(1);

        Functions\expect('wp_dropdown_categories')
            ->once()
            ->with(\Mockery::on(function (array $args): bool {
                return isset($args['selected']) && $args['selected'] == 5;
            }))
            ->andReturn('<select></select>');

        $this->makeField()->outputHtml(5);
    }

    public function testOutputUsesDefaultValueWhenEmpty(): void
    {
        $this->addToAssertionCount(1);

        Functions\expect('wp_dropdown_categories')
            ->once()
            ->with(\Mockery::on(function (array $args): bool {
                return isset($args['selected']) && $args['selected'] == 3;
            }))
            ->andReturn('<select></select>');

        $this->makeField(['default_value' => 3])->outputHtml('');
    }

    public function testOutputPassesNameWithCfdevPrefix(): void
    {
        $this->addToAssertionCount(1);

        $field = $this->makeField();

        Functions\expect('wp_dropdown_categories')
            ->once()
            ->with(\Mockery::on(function (array $args) use ($field): bool {
                return isset($args['name'])
                    && str_starts_with($args['name'], 'cfdev')
                    && str_contains($args['name'], $field->id);
            }))
            ->andReturn('<select></select>');

        $field->outputHtml('');
    }

    public function testOutputPassesIdWithFieldId(): void
    {
        $this->addToAssertionCount(1);

        $field = $this->makeField();

        Functions\expect('wp_dropdown_categories')
            ->once()
            ->with(\Mockery::on(function (array $args) use ($field): bool {
                return isset($args['id']) && str_contains($args['id'], $field->id);
            }))
            ->andReturn('<select></select>');

        $field->outputHtml('');
    }

    public function testOutputNameHasArraySuffixWhenRepeatable(): void
    {
        $this->addToAssertionCount(1);

        Functions\expect('wp_dropdown_categories')
            ->once()
            ->with(\Mockery::on(function (array $args): bool {
                return isset($args['name']) && str_ends_with($args['name'], '[]');
            }))
            ->andReturn('<select></select>');

        $this->makeField(['repeatable' => true])->outputHtml('');
    }

    public function testOutputNameHasNoArraySuffixWhenNotRepeatable(): void
    {
        $this->addToAssertionCount(1);

        Functions\expect('wp_dropdown_categories')
            ->once()
            ->with(\Mockery::on(function (array $args): bool {
                return isset($args['name']) && !str_ends_with($args['name'], '[]');
            }))
            ->andReturn('<select></select>');

        $this->makeField()->outputHtml('');
    }

    public function testOutputReturnsDropdownHtml(): void
    {
        Functions\when('wp_dropdown_categories')->justReturn('<select><option>News</option></select>');

        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('<select>', $output);
        $this->assertStringContainsString('News', $output);
    }

    public function testOutputStoresDropdownOnProperty(): void
    {
        Functions\when('wp_dropdown_categories')->justReturn('<select></select>');

        $field = $this->makeField();
        $field->outputHtml('');
        $this->assertSame('<select></select>', $field->dropdown);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        Functions\when('wp_dropdown_categories')->justReturn('');

        $field = $this->makeField(['explanation' => 'Select a category']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('Select a category', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        Functions\when('wp_dropdown_categories')->justReturn('');

        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
