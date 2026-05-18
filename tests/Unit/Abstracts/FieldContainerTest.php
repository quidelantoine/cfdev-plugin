<?php

namespace CFDev\Tests\Unit\Abstracts;

use CFDev\Abstracts\FieldContainer;
use CFDev\Tests\Unit\CFDevTestCase;

class FieldContainerTest extends CFDevTestCase
{
    private function make(): FieldContainer
    {
        return new class extends FieldContainer {
        };
    }

    // -------------------------------------------------------------------------
    // Default property values
    // -------------------------------------------------------------------------

    public function testIdDefaultsToEmptyString(): void
    {
        $this->assertSame('', $this->make()->id);
    }

    public function testMetaTypeDefaultsToFalse(): void
    {
        $this->assertFalse($this->make()->meta_type);
    }

    // -------------------------------------------------------------------------
    // Mutable properties
    // -------------------------------------------------------------------------

    public function testIdCanBeSet(): void
    {
        $container     = $this->make();
        $container->id = '_details';
        $this->assertSame('_details', $container->id);
    }

    public function testMetaTypeCanBeSetToString(): void
    {
        $container            = $this->make();
        $container->meta_type = 'post';
        $this->assertSame('post', $container->meta_type);
    }

    public function testMetaTypeCanBeResetToFalse(): void
    {
        $container            = $this->make();
        $container->meta_type = 'user';
        $container->meta_type = false;
        $this->assertFalse($container->meta_type);
    }
}
