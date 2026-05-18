<?php

namespace CFDev\Tests\Unit\Fields;

use CFDev\Fields\PostSelect;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class PostSelectTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makePost(int $id, string $title): object
    {
        return (object)[
            'ID' => $id,
            'post_title' => $title,
        ];
    }

    private function makeField(array $overrides = [], array $posts = []): PostSelect
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('selected')->alias(function (mixed $selected, mixed $current, bool $echo = true): string {
            $result = $selected == $current ? ' selected="selected"' : '';
            if ($echo) {
                echo $result; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            return $result;
        });
        Functions\when('get_posts')->justReturn($posts);

        $defaults = [
            'type' => 'post_select',
            'name' => 'my_post',
            'label' => 'My Post',
        ];

        return new PostSelect(array_merge($defaults, $overrides), 'my_metabox');
    }

    private function defaultPosts(): array
    {
        return [
            $this->makePost(10, 'Hello World'),
            $this->makePost(20, 'About Us'),
            $this->makePost(30, 'Contact'),
        ];
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

    public function testHasCssClasses(): void
    {
        $this->assertContains('cfdev-input cfdev-select cfdev-post-select', $this->makeField()->css_classes);
    }

    // -------------------------------------------------------------------------
    // Construction — args par défaut
    // -------------------------------------------------------------------------

    public function testArgsDefaultPostTypeIsPost(): void
    {
        $this->assertSame('post', $this->makeField()->args['post_type']);
    }

    public function testArgsDefaultPostsPerPageIsMinusOne(): void
    {
        $this->assertSame(-1, $this->makeField()->args['posts_per_page']);
    }

    public function testArgsCacheResultsIsFalse(): void
    {
        $this->assertFalse($this->makeField()->args['cache_results']);
    }

    public function testArgsNoFoundRowsIsTrue(): void
    {
        $this->assertTrue($this->makeField()->args['no_found_rows']);
    }

    public function testArgsCustomPostTypeIsRespected(): void
    {
        $field = $this->makeField(['args' => ['post_type' => 'page']]);
        $this->assertSame('page', $field->args['post_type']);
    }

    public function testPostsAreLoadedOnConstruction(): void
    {
        $posts = $this->defaultPosts();
        $field = $this->makeField([], $posts);
        $this->assertSame($posts, $field->posts);
    }

    public function testPostsIsEmptyArrayByDefault(): void
    {
        $this->assertSame([], $this->makeField()->posts);
    }

    // -------------------------------------------------------------------------
    // outputHtml — structure HTML
    // -------------------------------------------------------------------------

    public function testOutputRendersSelectTag(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml('');
        $this->assertStringContainsString('<select', $output);
        $this->assertStringContainsString('</select>', $output);
    }

    public function testOutputContainsNameAttribute(): void
    {
        $field = $this->makeField([], $this->defaultPosts());
        $output = $field->outputHtml('');
        $this->assertStringContainsString('name=', $output);
        $this->assertStringContainsString($field->id, $output);
    }

    public function testOutputRendersOneOptionPerPost(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml('');
        $this->assertEquals(3, substr_count($output, '<option'));
    }

    public function testOutputRendersPostIdsAsValues(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml('');
        $this->assertStringContainsString('value="10"', $output);
        $this->assertStringContainsString('value="20"', $output);
        $this->assertStringContainsString('value="30"', $output);
    }

    public function testOutputRendersPostTitlesAsLabels(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml('');
        $this->assertStringContainsString('Hello World', $output);
        $this->assertStringContainsString('About Us', $output);
        $this->assertStringContainsString('Contact', $output);
    }

    public function testOutputEmptySelectWhenNoPosts(): void
    {
        $output = $this->makeField([], [])->outputHtml('');
        $this->assertStringContainsString('<select', $output);
        $this->assertEquals(0, substr_count($output, '<option'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — show_option_none
    // -------------------------------------------------------------------------

    public function testOutputRendersNoneOptionWhenArgSet(): void
    {
        $field = $this->makeField(['args' => ['show_option_none' => '— Select a post —']], $this->defaultPosts());
        $output = $field->outputHtml('');
        $this->assertStringContainsString('— Select a post —', $output);
        $this->assertStringContainsString('value="0"', $output);
    }

    public function testOutputNoneOptionSelectedWhenValueEmpty(): void
    {
        $field = $this->makeField(['args' => ['show_option_none' => '— Select —']], $this->defaultPosts());
        $output = $field->outputHtml('');
        $zero_pos = strpos($output, 'value="0"');
        $sel_pos = strpos($output, 'selected="selected"');
        $this->assertNotFalse($sel_pos);
        $this->assertLessThan($sel_pos, $zero_pos);
    }

    public function testOutputNoneOptionNotSelectedWhenValueSet(): void
    {
        $field = $this->makeField(['args' => ['show_option_none' => '— Select —']], $this->defaultPosts());
        $output = $field->outputHtml('10');
        $this->assertStringNotContainsString('value="0" selected="selected"', $output);
    }

    public function testOutputNoNoneOptionByDefault(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml('');
        $this->assertStringNotContainsString('value="0"', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — selected state avec value
    // -------------------------------------------------------------------------

    public function testOutputSelectsMatchingPost(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml('20');
        $this->assertEquals(1, substr_count($output, 'selected="selected"'));
    }

    public function testOutputSelectedOptionIsCorrect(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml('30');
        $val_pos = strpos($output, 'value="30"');
        $sel_pos = strpos($output, 'selected="selected"');
        $this->assertLessThan($sel_pos, $val_pos);
    }

    public function testOutputNoSelectedWhenValueNotInPosts(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml('999');
        $this->assertEquals(0, substr_count($output, 'selected="selected"'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — selected state avec default_value
    // -------------------------------------------------------------------------

    public function testOutputSelectsDefaultValueWhenValueEmpty(): void
    {
        $field = $this->makeField(['default_value' => 10], $this->defaultPosts());
        $output = $field->outputHtml('');
        $this->assertEquals(1, substr_count($output, 'selected="selected"'));
        $val_pos = strpos($output, 'value="10"');
        $sel_pos = strpos($output, 'selected="selected"');
        $this->assertLessThan($sel_pos, $val_pos);
    }

    public function testOutputValueTakesPriorityOverDefault(): void
    {
        $field = $this->makeField(['default_value' => 10], $this->defaultPosts());
        $output = $field->outputHtml('30');
        $this->assertEquals(1, substr_count($output, 'selected="selected"'));
        $val_pos = strpos($output, 'value="30"');
        $sel_pos = strpos($output, 'selected="selected"');
        $this->assertLessThan($sel_pos, $val_pos);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field = $this->makeField(['explanation' => 'Select a post'], $this->defaultPosts());
        $output = $field->outputHtml('');
        $this->assertStringContainsString('Select a post', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
