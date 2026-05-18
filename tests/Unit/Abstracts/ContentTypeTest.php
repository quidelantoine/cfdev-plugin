<?php

namespace CFDev\Tests\Unit\Abstracts;

use CFDev\Abstracts\ContentType;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class ContentTypeTest extends CFDevTestCase
{
    private function make(string $name = 'book'): ContentType
    {
        return new class ($name) extends ContentType {
            public function __construct(string $n)
            {
                $this->name   = $n;
                $this->title  = ucfirst($n);
                $this->plural = $n . 's';
            }
            public function register(): void
            {
            }
            protected function buildLabels(): array
            {
                return []; 
            }
            protected function buildArgs(): array
            {
                return []; 
            }
        };
    }

    // -------------------------------------------------------------------------
    // addSupport()
    // -------------------------------------------------------------------------

    public function testAddSupportPassesNameAndFeatureToWp(): void
    {
        Functions\expect('add_post_type_support')
            ->once()
            ->with('book', 'thumbnail');

        $this->make('book')->addSupport('thumbnail');
        $this->addToAssertionCount(1);
    }

    public function testAddSupportReturnsSelf(): void
    {
        Functions\when('add_post_type_support')->justReturn();

        $ct = $this->make();
        $this->assertSame($ct, $ct->addSupport('thumbnail'));
    }

    public function testAddSupportWithArrayPassesItDirectly(): void
    {
        Functions\expect('add_post_type_support')
            ->once()
            ->with('book', ['thumbnail', 'excerpt']);

        $this->make('book')->addSupport(['thumbnail', 'excerpt']);
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // removeSupport()
    // -------------------------------------------------------------------------

    public function testRemoveSupportPassesNameAndFeatureToWp(): void
    {
        Functions\expect('remove_post_type_support')
            ->once()
            ->with('book', 'comments');

        $this->make('book')->removeSupport('comments');
        $this->addToAssertionCount(1);
    }

    public function testRemoveSupportReturnsSelf(): void
    {
        Functions\when('remove_post_type_support')->justReturn();

        $ct = $this->make();
        $this->assertSame($ct, $ct->removeSupport('comments'));
    }

    public function testRemoveSupportWithArrayIteratesEachFeature(): void
    {
        // removeSupport() wraps in (array) and loops — one WP call per feature
        Functions\expect('remove_post_type_support')->twice();

        $this->make()->removeSupport(['comments', 'trackbacks']);
        $this->addToAssertionCount(1);
    }

    public function testRemoveSupportWithStringSingleCall(): void
    {
        Functions\expect('remove_post_type_support')->once();

        $this->make()->removeSupport('comments');
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // supports()
    // -------------------------------------------------------------------------

    public function testSupportsReturnsTrueWhenWpConfirms(): void
    {
        Functions\when('post_type_supports')->justReturn(true);

        $this->assertTrue($this->make()->supports('thumbnail'));
    }

    public function testSupportsReturnsFalseWhenWpDenies(): void
    {
        Functions\when('post_type_supports')->justReturn(false);

        $this->assertFalse($this->make()->supports('thumbnail'));
    }

    public function testSupportsPassesCorrectNameAndFeature(): void
    {
        Functions\expect('post_type_supports')
            ->once()
            ->with('book', 'excerpt')
            ->andReturn(false);

        $this->make('book')->supports('excerpt');
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // addMetaBox()
    // -------------------------------------------------------------------------

    public function testAddMetaBoxReturnsSelf(): void
    {
        $ct     = $this->make('book');
        $result = $ct->addMetaBox('details', 'Book Details', []);

        $this->assertSame($ct, $result);
    }

    public function testAddMetaBoxWithContextAndPriorityReturnsSelf(): void
    {
        $ct     = $this->make('book');
        $result = $ct->addMetaBox('sidebar', 'Sidebar Info', [], 'side', 'high');

        $this->assertSame($ct, $result);
    }

    // -------------------------------------------------------------------------
    // Fluent interface chaining
    // -------------------------------------------------------------------------

    public function testAddAndRemoveSupportChain(): void
    {
        Functions\when('add_post_type_support')->justReturn();
        Functions\when('remove_post_type_support')->justReturn();

        $ct     = $this->make();
        $result = $ct->addSupport('thumbnail')->removeSupport('comments');

        $this->assertSame($ct, $result);
    }

    public function testAddMetaBoxChainedWithAddSupport(): void
    {
        Functions\when('add_post_type_support')->justReturn();

        $ct     = $this->make('book');
        $result = $ct->addMetaBox('details', 'Details', [])->addSupport('thumbnail');

        $this->assertSame($ct, $result);
    }
}
