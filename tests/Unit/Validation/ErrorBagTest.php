<?php

namespace Weblitzer\CFDev\Tests\Unit\Validation;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Weblitzer\CFDev\Validation\ErrorBag;

class ErrorBagTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset private static $runtime between tests
        $ref = new \ReflectionProperty(ErrorBag::class, 'runtime');
        $ref->setAccessible(true);
        $ref->setValue(null, null);

        Functions\when('get_current_user_id')->justReturn(1);
    }

    /** @return array<string, array{label: string, errors: array<int, string>}> */
    private function sampleErrors(): array
    {
        return [
            '_titre' => ['label' => 'Titre', 'errors' => ['This field is required.']],
        ];
    }

    // -------------------------------------------------------------------------
    // push()
    // -------------------------------------------------------------------------

    public function testPushStoresErrorsAsTransient(): void
    {
        Functions\when('get_transient')->justReturn(false);

        $stored = [];
        Functions\when('set_transient')->alias(function (string $key, array $data) use (&$stored): void {
            $stored = $data;
        });

        ErrorBag::push('post', 42, $this->sampleErrors());

        $this->assertArrayHasKey('_titre', $stored);
    }

    public function testPushMergesWithExistingErrors(): void
    {
        $existing = ['_prix' => ['label' => 'Prix', 'errors' => ['Required.']]];
        Functions\when('get_transient')->justReturn($existing);

        $stored = [];
        Functions\when('set_transient')->alias(function (string $key, array $data) use (&$stored): void {
            $stored = $data;
        });

        ErrorBag::push('post', 42, $this->sampleErrors());

        $this->assertArrayHasKey('_titre', $stored);
        $this->assertArrayHasKey('_prix', $stored);
    }

    public function testPushUsesMetaTypeAndObjectIdInKey(): void
    {
        Functions\when('get_transient')->justReturn(false);

        $usedKey = '';
        Functions\when('set_transient')->alias(function (string $key) use (&$usedKey): void {
            $usedKey = $key;
        });

        ErrorBag::push('user', 99, $this->sampleErrors());

        $this->assertStringContainsString('user', $usedKey);
        $this->assertStringContainsString('99', $usedKey);
    }

    // -------------------------------------------------------------------------
    // load()
    // -------------------------------------------------------------------------

    public function testLoadPopulatesRuntimeFromTransient(): void
    {
        Functions\when('get_transient')->justReturn($this->sampleErrors());
        Functions\when('delete_transient')->justReturn();

        ErrorBag::load('post', 42);

        $this->assertSame(['This field is required.'], ErrorBag::forField('_titre'));
    }

    public function testLoadDeletesTransientAfterReading(): void
    {
        Functions\when('get_transient')->justReturn($this->sampleErrors());

        $deleted = null;
        Functions\when('delete_transient')->alias(function (string $key) use (&$deleted): void {
            $deleted = $key;
        });

        ErrorBag::load('post', 42);

        $this->assertNotNull($deleted);
        $this->assertStringContainsString('post', $deleted);
    }

    public function testLoadIsIdempotent(): void
    {
        $callCount = 0;
        Functions\when('get_transient')->alias(function () use (&$callCount): array {
            $callCount++;
            return $this->sampleErrors();
        });
        Functions\when('delete_transient')->justReturn();

        ErrorBag::load('post', 42);
        ErrorBag::load('post', 42); // second call — must be no-op

        $this->assertSame(1, $callCount);
    }

    public function testLoadWithNoTransientSetsEmptyRuntime(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('delete_transient')->justReturn();

        ErrorBag::load('post', 42);

        $this->assertSame([], ErrorBag::forField('_titre'));
        $this->assertSame([], ErrorBag::all());
    }

    // -------------------------------------------------------------------------
    // peek()
    // -------------------------------------------------------------------------

    public function testPeekReturnsTransientWithoutLoadingRuntime(): void
    {
        Functions\when('get_transient')->justReturn($this->sampleErrors());

        $result = ErrorBag::peek('post', 42);

        $this->assertArrayHasKey('_titre', $result);
        // Runtime must NOT have been populated by peek
        $this->assertSame([], ErrorBag::all());
    }

    public function testPeekReturnsEmptyArrayWhenNoTransient(): void
    {
        Functions\when('get_transient')->justReturn(false);

        $this->assertSame([], ErrorBag::peek('post', 42));
    }

    // -------------------------------------------------------------------------
    // forField()
    // -------------------------------------------------------------------------

    public function testForFieldReturnsErrorsAfterLoad(): void
    {
        Functions\when('get_transient')->justReturn($this->sampleErrors());
        Functions\when('delete_transient')->justReturn();

        ErrorBag::load('post', 42);

        $this->assertSame(['This field is required.'], ErrorBag::forField('_titre'));
    }

    public function testForFieldReturnsEmptyArrayForUnknownKey(): void
    {
        Functions\when('get_transient')->justReturn($this->sampleErrors());
        Functions\when('delete_transient')->justReturn();

        ErrorBag::load('post', 42);

        $this->assertSame([], ErrorBag::forField('_nonexistent'));
    }

    public function testForFieldBeforeLoadReturnsEmpty(): void
    {
        // $runtime is null — forField returns []
        $this->assertSame([], ErrorBag::forField('_titre'));
    }

    // -------------------------------------------------------------------------
    // all()
    // -------------------------------------------------------------------------

    public function testAllReturnsRuntimeAfterLoad(): void
    {
        $errors = [
            '_titre' => ['label' => 'Titre', 'errors' => ['Required.']],
            '_prix'  => ['label' => 'Prix',  'errors' => ['Must be positive.']],
        ];
        Functions\when('get_transient')->justReturn($errors);
        Functions\when('delete_transient')->justReturn();

        ErrorBag::load('post', 42);

        $this->assertCount(2, ErrorBag::all());
        $this->assertArrayHasKey('_titre', ErrorBag::all());
        $this->assertArrayHasKey('_prix', ErrorBag::all());
    }

    public function testAllBeforeLoadReturnsEmpty(): void
    {
        $this->assertSame([], ErrorBag::all());
    }
}
