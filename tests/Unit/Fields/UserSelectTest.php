<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Fields\UserSelect;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class UserSelectTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): UserSelect
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });

        $defaults = [
            'type'  => 'user_select',
            'name'  => 'my_user',
            'label' => 'My User',
        ];

        return new UserSelect(array_merge($defaults, $overrides), 'my_metabox');
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

    public function testArgsHasOrderby(): void
    {
        $field = $this->makeField();
        $this->assertArrayHasKey('orderby', $field->args);
        $this->assertSame('ID', $field->args['orderby']);
    }

    public function testArgsClassContainsCfdevInput(): void
    {
        $this->assertStringContainsString('cfdev-input', $this->makeField()->args['class']);
    }

    public function testArgsClassContainsCfdevSelect(): void
    {
        $this->assertStringContainsString('cfdev-select', $this->makeField()->args['class']);
    }

    public function testArgsClassContainsCfdevUserSelect(): void
    {
        $this->assertStringContainsString('cfdev-user-select', $this->makeField()->args['class']);
    }

    public function testArgsCustomClassIsMerged(): void
    {
        $field = $this->makeField(['args' => ['class' => 'my-custom']]);
        $this->assertStringContainsString('my-custom', $field->args['class']);
        $this->assertStringContainsString('cfdev-input', $field->args['class']);
    }

    public function testArgsEchoIsZero(): void
    {
        $this->assertSame(0, $this->makeField()->args['echo']);
    }

    public function testArgsCustomOrderbyIsRespected(): void
    {
        $field = $this->makeField(['args' => ['orderby' => 'display_name']]);
        $this->assertSame('display_name', $field->args['orderby']);
    }

    // -------------------------------------------------------------------------
    // outputHtml — appel wp_dropdown_users
    // -------------------------------------------------------------------------

    public function testOutputCallsWpDropdownUsers(): void
    {
        $this->addToAssertionCount(1);

        Functions\expect('wp_dropdown_users')
            ->once()
            ->andReturn('<select></select>');

        $this->makeField()->outputHtml('');
    }

    public function testOutputPassesSelectedValue(): void
    {
        $this->addToAssertionCount(1);

        Functions\expect('wp_dropdown_users')
            ->once()
            ->with(\Mockery::on(function (array $args): bool {
                return isset($args['selected']) && $args['selected'] == 42;
            }))
            ->andReturn('<select></select>');

        $this->makeField()->outputHtml('42');
    }

    public function testOutputUsesDefaultValueWhenEmpty(): void
    {
        $this->addToAssertionCount(1);

        Functions\expect('wp_dropdown_users')
            ->once()
            ->with(\Mockery::on(function (array $args): bool {
                return isset($args['selected']) && $args['selected'] == 5;
            }))
            ->andReturn('<select></select>');

        $this->makeField(['default_value' => 5])->outputHtml('');
    }

    public function testOutputPassesNameWithCfdevPrefix(): void
    {
        $this->addToAssertionCount(1);

        $field = $this->makeField();

        Functions\expect('wp_dropdown_users')
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

        Functions\expect('wp_dropdown_users')
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

        Functions\expect('wp_dropdown_users')
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

        Functions\expect('wp_dropdown_users')
            ->once()
            ->with(\Mockery::on(function (array $args): bool {
                return isset($args['name']) && ! str_ends_with($args['name'], '[]');
            }))
            ->andReturn('<select></select>');

        $this->makeField()->outputHtml('');
    }

    public function testOutputReturnsDropdownHtml(): void
    {
        Functions\when('wp_dropdown_users')->justReturn('<select><option>John</option></select>');

        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('<select>', $output);
        $this->assertStringContainsString('John', $output);
    }

    public function testOutputStoresDropdownOnProperty(): void
    {
        Functions\when('wp_dropdown_users')->justReturn('<select></select>');

        $field = $this->makeField();
        $field->outputHtml('');
        $this->assertSame('<select></select>', $field->dropdown);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        Functions\when('wp_dropdown_users')->justReturn('');

        $field  = $this->makeField(['explanation' => 'Select a user']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('Select a user', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        Functions\when('wp_dropdown_users')->justReturn('');

        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
