<?php

namespace CFDev\Tests\Unit\Config;

use CFDev\Config\Config;
use CFDev\Tests\Unit\CFDevTestCase;

class ConfigTest extends CFDevTestCase
{
    private function make(
        string $version = '1.0.0',
        string $dir = '/path/to/plugin/',
        string $url = 'https://example.com/plugin/',
        string $src_dir = '/path/to/plugin/src/',
    ): Config {
        return new Config($version, $dir, $url, $src_dir);
    }

    // -------------------------------------------------------------------------
    // Constructor — property storage
    // -------------------------------------------------------------------------

    public function testVersionIsStored(): void
    {
        $this->assertSame('2.9.18', $this->make(version: '2.9.18')->version);
    }

    public function testDirIsStored(): void
    {
        $this->assertSame('/var/www/plugin/', $this->make(dir: '/var/www/plugin/')->dir);
    }

    public function testUrlIsStored(): void
    {
        $this->assertSame('https://site.com/plugin/', $this->make(url: 'https://site.com/plugin/')->url);
    }

    public function testSrcDirIsStored(): void
    {
        $this->assertSame('/var/www/plugin/src/', $this->make(src_dir: '/var/www/plugin/src/')->src_dir);
    }

    public function testAllFourPropertiesStoredTogether(): void
    {
        $config = new Config('3.0.0', '/dir/', 'https://url/', '/src/');

        $this->assertSame('3.0.0', $config->version);
        $this->assertSame('/dir/', $config->dir);
        $this->assertSame('https://url/', $config->url);
        $this->assertSame('/src/', $config->src_dir);
    }

    // -------------------------------------------------------------------------
    // Readonly enforcement
    // -------------------------------------------------------------------------

    public function testVersionIsReadonly(): void
    {
        $config = $this->make();
        $this->expectException(\Error::class);
        $config->version = 'hacked'; // @phpstan-ignore-line
    }

    public function testUrlIsReadonly(): void
    {
        $config = $this->make();
        $this->expectException(\Error::class);
        $config->url = 'https://evil.com/'; // @phpstan-ignore-line
    }
}
