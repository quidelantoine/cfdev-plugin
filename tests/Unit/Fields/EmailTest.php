<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Fields\Email;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class EmailTest extends CFDevTestCase
{
    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Email
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('sanitize_email')->alias(function (string $v): string {
            return filter_var($v, FILTER_SANITIZE_EMAIL) ?: '';
        });
        Functions\when('is_email')->alias(function (string $v): bool {
            return (bool) filter_var($v, FILTER_VALIDATE_EMAIL);
        });

        $defaults = ['type' => 'email', 'name' => 'my_email', 'label' => 'Email'];

        return new Email(array_merge($defaults, $overrides), 'my_metabox');
    }

    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    public function testSupportsRepeatable(): void
    {
        $this->assertTrue($this->makeField()->supports_repeatable);
    }

    public function testSupportsBundle(): void
    {
        $this->assertTrue($this->makeField()->supports_bundle);
    }

    public function testSupportsAjax(): void
    {
        $this->assertTrue($this->makeField()->supports_ajax);
    }

    // -------------------------------------------------------------------------
    // outputHtml
    // -------------------------------------------------------------------------

    public function testOutputRendersEmailInput(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('type="email"', $output);
    }

    public function testOutputContainsValue(): void
    {
        $output = $this->makeField()->outputHtml('foo@bar.com');
        $this->assertStringContainsString('value="foo@bar.com"', $output);
    }

    public function testOutputUsesDefaultValueWhenEmpty(): void
    {
        $field  = $this->makeField(['default_value' => 'default@example.com']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('value="default@example.com"', $output);
    }

    // -------------------------------------------------------------------------
    // saveValue
    // -------------------------------------------------------------------------

    public function testSaveValueValidEmail(): void
    {
        $result = $this->makeField()->saveValue('user@example.com');
        $this->assertSame('user@example.com', $result);
    }

    public function testSaveValueStripsInvalidChars(): void
    {
        $result = $this->makeField()->saveValue('user <script>@example.com');
        $this->assertIsString($result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testSaveValueArray(): void
    {
        $result = $this->makeField()->saveValue(['a@b.com', 'c@d.com']);
        $this->assertIsArray($result);
        $this->assertSame('a@b.com', $result[0]);
        $this->assertSame('c@d.com', $result[1]);
    }

    // -------------------------------------------------------------------------
    // validate — format rule auto-injected
    // -------------------------------------------------------------------------

    public function testValidatePassesOnEmptyValue(): void
    {
        $validator = $this->makeField()->validate('');
        $this->assertTrue($validator->passes());
    }

    public function testValidatePassesOnValidEmail(): void
    {
        $validator = $this->makeField()->validate('user@example.com');
        $this->assertTrue($validator->passes());
    }

    public function testValidateFailsOnInvalidEmail(): void
    {
        $validator = $this->makeField()->validate('not-an-email');
        $this->assertFalse($validator->passes());
        $this->assertNotEmpty($validator->errors());
    }

    public function testValidateFailsOnEmptyWhenRequired(): void
    {
        $validator = $this->makeField(['required' => true])->validate('');
        $this->assertFalse($validator->passes());
    }
}
