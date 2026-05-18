<?php

namespace Weblitzer\CFDev\Tests\Unit;

use Weblitzer\CFDev\Container;
use RuntimeException;

class ContainerTest extends CFDevTestCase
{
    public function testBindAndGet(): void
    {
        $container = new Container();
        $obj = new \stdClass();
        $container->bind('my_service', $obj);
        $this->assertSame($obj, $container->get('my_service'));
    }

    public function testGetThrowsForMissingBinding(): void
    {
        $container = new Container();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches("/Binding 'missing' not found/");
        $container->get('missing');
    }

    public function testBindOverwritesPreviousValue(): void
    {
        $container = new Container();
        $container->bind('key', 'first');
        $container->bind('key', 'second');
        $this->assertSame('second', $container->get('key'));
    }

    public function testBindAcceptsScalarValues(): void
    {
        $container = new Container();
        $container->bind('version', '1.0.0');
        $this->assertSame('1.0.0', $container->get('version'));
    }

    public function testMultipleBindingsAreIsolated(): void
    {
        $container = new Container();
        $container->bind('a', 'alpha');
        $container->bind('b', 'beta');
        $this->assertSame('alpha', $container->get('a'));
        $this->assertSame('beta', $container->get('b'));
    }
}
