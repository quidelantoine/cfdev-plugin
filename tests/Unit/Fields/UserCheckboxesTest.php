<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Fields\UserCheckboxes;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class UserCheckboxesTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUser(int $id, string $displayName): object
    {
        return (object) [
            'ID'           => $id,
            'display_name' => $displayName,
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @param array<int, object>   $users
     */
    private function makeField(array $overrides = [], array $users = []): UserCheckboxes
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('get_users')->justReturn($users);

        $defaults = [
            'type'  => 'user_checkboxes',
            'name'  => 'my_user_checkboxes',
            'label' => 'My User Checkboxes',
        ];

        return new UserCheckboxes(array_merge($defaults, $overrides), 'my_metabox');
    }

    /** @return array<int, object> */
    private function defaultUsers(): array
    {
        return [
            $this->makeUser(1, 'Alice'),
            $this->makeUser(2, 'Bob'),
            $this->makeUser(3, 'Charlie'),
        ];
    }

    // -------------------------------------------------------------------------
    // Construction / defaults
    // -------------------------------------------------------------------------

    public function testSupportsBundle(): void
    {
        $this->assertTrue($this->makeField()->supports_bundle);
    }

    public function testDoesNotSupportRepeatable(): void
    {
        $this->assertFalse($this->makeField()->supports_repeatable);
    }

    public function testDoesNotSupportAjax(): void
    {
        $this->assertFalse($this->makeField()->supports_ajax);
    }

    public function testHasCfdevInputClass(): void
    {
        $this->assertContains('cfdev-input', $this->makeField()->css_classes);
    }

    public function testDefaultOrderbyIsDisplayName(): void
    {
        $this->assertSame('display_name', $this->makeField()->args['orderby']);
    }

    public function testDefaultValueIsCastToArray(): void
    {
        $field = $this->makeField(['default_value' => 1]);
        $this->assertIsArray($field->default_value);
    }

    public function testAfterHasArraySuffix(): void
    {
        $this->assertStringContainsString('[]', $this->makeField()->after);
    }

    // -------------------------------------------------------------------------
    // outputHtml — structure
    // -------------------------------------------------------------------------

    public function testOutputRendersWrapperDiv(): void
    {
        $output = $this->makeField([], $this->defaultUsers())->outputHtml('-1');
        $this->assertStringContainsString('cfdev-checkboxes-wrap', $output);
    }

    public function testOutputRendersOneCheckboxPerUser(): void
    {
        $output = $this->makeField([], $this->defaultUsers())->outputHtml('-1');
        $this->assertSame(3, substr_count($output, 'type="checkbox"'));
    }

    public function testOutputRendersUserIdsAsValues(): void
    {
        $output = $this->makeField([], $this->defaultUsers())->outputHtml('-1');
        $this->assertStringContainsString('value="1"', $output);
        $this->assertStringContainsString('value="2"', $output);
        $this->assertStringContainsString('value="3"', $output);
    }

    public function testOutputRendersDisplayNamesAsLabels(): void
    {
        $output = $this->makeField([], $this->defaultUsers())->outputHtml('-1');
        $this->assertStringContainsString('Alice', $output);
        $this->assertStringContainsString('Bob', $output);
        $this->assertStringContainsString('Charlie', $output);
    }

    public function testOutputEmptyWhenNoUsers(): void
    {
        $output = $this->makeField([], [])->outputHtml('-1');
        $this->assertSame(0, substr_count($output, 'type="checkbox"'));
    }

    public function testOutputLabelForMatchesInputId(): void
    {
        $field  = $this->makeField([], [$this->makeUser(1, 'Alice')]);
        $output = $field->outputHtml('-1');
        $this->assertStringContainsString('for="' . $field->id . '_alice"', $output);
        $this->assertStringContainsString('id="' . $field->id . '_alice"', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec value array
    // -------------------------------------------------------------------------

    public function testOutputChecksMatchingUserIds(): void
    {
        $output = $this->makeField([], $this->defaultUsers())->outputHtml([1, 3]);
        $this->assertSame(2, substr_count($output, 'checked="checked"'));
    }

    public function testOutputChecksSingleUserId(): void
    {
        $output = $this->makeField([], $this->defaultUsers())->outputHtml([2]);
        $this->assertSame(1, substr_count($output, 'checked="checked"'));
    }

    public function testOutputChecksAllUsers(): void
    {
        $output = $this->makeField([], $this->defaultUsers())->outputHtml([1, 2, 3]);
        $this->assertSame(3, substr_count($output, 'checked="checked"'));
    }

    public function testOutputNoCheckedWhenUserIdNotInValue(): void
    {
        $output = $this->makeField([], $this->defaultUsers())->outputHtml([999]);
        $this->assertSame(0, substr_count($output, 'checked="checked"'));
    }

    public function testOutputNoCheckedWhenValueIsEmptyArray(): void
    {
        $output = $this->makeField([], $this->defaultUsers())->outputHtml([]);
        $this->assertSame(0, substr_count($output, 'checked="checked"'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec value = '-1'
    // -------------------------------------------------------------------------

    public function testOutputNoCheckedWhenValueIsMinusOne(): void
    {
        $output = $this->makeField([], $this->defaultUsers())->outputHtml('-1');
        $this->assertSame(0, substr_count($output, 'checked="checked"'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec default_value
    // -------------------------------------------------------------------------

    public function testOutputChecksDefaultUserIdsWhenValueNotArrayAndNotMinusOne(): void
    {
        $field  = $this->makeField(['default_value' => [1, 2]], $this->defaultUsers());
        $output = $field->outputHtml('');
        $this->assertSame(2, substr_count($output, 'checked="checked"'));
    }

    public function testOutputValueTakesPriorityOverDefault(): void
    {
        $field  = $this->makeField(['default_value' => [1]], $this->defaultUsers());
        $output = $field->outputHtml([3]);
        $this->assertSame(1, substr_count($output, 'checked="checked"'));
    }

    // -------------------------------------------------------------------------
    // saveValue
    // -------------------------------------------------------------------------

    public function testSaveValueReturnsValueWhenNotEmpty(): void
    {
        $result = $this->makeField()->saveValue([1, 2]);
        $this->assertSame([1, 2], $result);
    }

    public function testSaveValueReturnsMinusOneWhenEmptyArray(): void
    {
        $result = $this->makeField()->saveValue([]);
        $this->assertSame('-1', $result);
    }

    public function testSaveValueReturnsMinusOneWhenEmptyString(): void
    {
        $result = $this->makeField()->saveValue('');
        $this->assertSame('-1', $result);
    }

    public function testSaveValueSingleItem(): void
    {
        $result = $this->makeField()->saveValue([3]);
        $this->assertSame([3], $result);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field  = $this->makeField(['explanation' => 'Select users'], $this->defaultUsers());
        $output = $field->outputHtml('-1');
        $this->assertStringContainsString('Select users', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField([], $this->defaultUsers())->outputHtml('-1');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
