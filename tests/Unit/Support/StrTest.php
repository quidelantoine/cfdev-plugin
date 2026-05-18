<?php

namespace CFDev\Tests\Unit\Support;

use CFDev\Support\Str;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class StrTest extends CFDevTestCase
{
    // Brain\Monkey returns arg 2 for apply_filters() by default — no stub needed.

    // -------------------------------------------------------------------------
    // beautify()
    // -------------------------------------------------------------------------

    public function testBeautifyReplacesUnderscoresWithSpaces(): void
    {
        $this->assertSame('Hello World', Str::beautify('hello_world'));
    }

    public function testBeautifyCapitalizesEachWord(): void
    {
        $this->assertSame('My Post Type', Str::beautify('my_post_type'));
    }

    public function testBeautifyLeavesAlreadyBeautifulStringUnchanged(): void
    {
        $this->assertSame('Book', Str::beautify('book'));
    }

    public function testBeautifyEmptyString(): void
    {
        $this->assertSame('', Str::beautify(''));
    }

    // -------------------------------------------------------------------------
    // uglify()
    // -------------------------------------------------------------------------

    public function testUglifyReplacesHyphensWithUnderscores(): void
    {
        // sanitize_title('Hello World') → 'hello-world' (WordPress default)
        Functions\when('sanitize_title')->alias(fn(string $s) => strtolower(str_replace(' ', '-', $s)));

        $this->assertSame('hello_world', Str::uglify('Hello World'));
    }

    public function testUglifyMultipleHyphens(): void
    {
        Functions\when('sanitize_title')->alias(fn(string $s) => strtolower(str_replace(' ', '-', $s)));

        $this->assertSame('my_post_type', Str::uglify('My Post Type'));
    }

    // -------------------------------------------------------------------------
    // pluralize() — uncountable
    // -------------------------------------------------------------------------

    public function testPluralizeUncountableSheep(): void
    {
        $this->assertSame('sheep', Str::pluralize('sheep'));
    }

    public function testPluralizeUncountableFish(): void
    {
        $this->assertSame('fish', Str::pluralize('fish'));
    }

    public function testPluralizeUncountableInformation(): void
    {
        $this->assertSame('information', Str::pluralize('information'));
    }

    // -------------------------------------------------------------------------
    // pluralize() — irregular
    // -------------------------------------------------------------------------

    public function testPluralizeIrregularMan(): void
    {
        $this->assertSame('men', Str::pluralize('man'));
    }

    public function testPluralizeIrregularChild(): void
    {
        $this->assertSame('children', Str::pluralize('child'));
    }

    public function testPluralizeIrregularPerson(): void
    {
        $this->assertSame('people', Str::pluralize('person'));
    }

    // -------------------------------------------------------------------------
    // pluralize() — regex rules
    // -------------------------------------------------------------------------

    public function testPluralizeQuiz(): void
    {
        $this->assertSame('quizzes', Str::pluralize('quiz'));
    }

    public function testPluralizeWordEndingInY(): void
    {
        // /([^aeiouy]|qu)y$/i → $1ies
        $this->assertSame('categories', Str::pluralize('category'));
    }

    public function testPluralizeRegularWord(): void
    {
        // Falls through to /$/ → adds 's'
        $this->assertSame('books', Str::pluralize('book'));
    }

    public function testPluralizeWordEndingInSStaysUnchanged(): void
    {
        // /s$/i replaces trailing 's' with 's' — no change
        // 'cars' doesn't match any earlier rule, falls through to /s$/i
        $this->assertSame('cars', Str::pluralize('cars'));
    }

    public function testPluralizeStatusMatchesAliasRule(): void
    {
        // /(alias|status)$/i comes before /s$/i → 'status' → 'statuses'
        $this->assertSame('statuses', Str::pluralize('status'));
    }

    public function testPluralizeWordEndingInX(): void
    {
        // /(x|ch|ss|sh)$/i → $1es
        $this->assertSame('boxes', Str::pluralize('box'));
    }
}
