<?php

namespace CFDev\Tests\Unit\Fields;

use CFDev\Fields\PostCheckboxes;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class PostCheckboxesTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makePost(int $id, string $title): object
    {
        return (object) [
            'ID'         => $id,
            'post_title' => $title,
        ];
    }

    private function makeField(array $overrides = [], array $posts = []): PostCheckboxes
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('get_posts')->justReturn($posts);

        $defaults = [
            'type'  => 'post_checkboxes',
            'name'  => 'my_post_checkboxes',
            'label' => 'My Post Checkboxes',
        ];

        return new PostCheckboxes(array_merge($defaults, $overrides), 'my_metabox');
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

    public function testDefaultPostTypeIsPost(): void
    {
        $this->assertSame('post', $this->makeField()->args['post_type']);
    }

    public function testDefaultPostsPerPageIsMinusOne(): void
    {
        $this->assertSame(-1, $this->makeField()->args['posts_per_page']);
    }

    public function testCustomPostTypeIsRespected(): void
    {
        $field = $this->makeField(['args' => ['post_type' => 'page']]);
        $this->assertSame('page', $field->args['post_type']);
    }

    public function testDefaultValueIsCastToArray(): void
    {
        $field = $this->makeField(['default_value' => 10]);
        $this->assertIsArray($field->default_value);
    }

    public function testAfterHasArraySuffix(): void
    {
        $this->assertStringContainsString('[]', $this->makeField()->after);
    }

    // -------------------------------------------------------------------------
    // outputHtml — structure HTML
    // -------------------------------------------------------------------------

    public function testOutputRendersWrapperDiv(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml('-1');
        $this->assertStringContainsString('cfdev-checkboxes-wrap', $output);
    }

    public function testOutputRendersOneCheckboxPerPost(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml('-1');
        $this->assertEquals(3, substr_count($output, 'type="checkbox"'));
    }

    public function testOutputRendersPostIdsAsValues(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml('-1');
        $this->assertStringContainsString('value="10"', $output);
        $this->assertStringContainsString('value="20"', $output);
        $this->assertStringContainsString('value="30"', $output);
    }

    public function testOutputRendersPostTitlesAsLabels(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml('-1');
        $this->assertStringContainsString('Hello World', $output);
        $this->assertStringContainsString('About Us', $output);
        $this->assertStringContainsString('Contact', $output);
    }

    public function testOutputEmptyWhenNoPosts(): void
    {
        $output = $this->makeField([], [])->outputHtml('-1');
        $this->assertEquals(0, substr_count($output, 'type="checkbox"'));
    }

    public function testOutputLabelForMatchesInputId(): void
    {
        $field  = $this->makeField([], [ $this->makePost(10, 'Hello World') ]);
        $output = $field->outputHtml('-1');
        $this->assertStringContainsString('for="' . $field->id . '_hello_world"', $output);
        $this->assertStringContainsString('id="' . $field->id . '_hello_world"', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec value array
    // -------------------------------------------------------------------------

    public function testOutputChecksMatchingPostIds(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml([10, 30]);
        $this->assertEquals(2, substr_count($output, 'checked="checked"'));
    }

    public function testOutputChecksSinglePostId(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml([20]);
        $this->assertEquals(1, substr_count($output, 'checked="checked"'));
    }

    public function testOutputChecksAllPosts(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml([10, 20, 30]);
        $this->assertEquals(3, substr_count($output, 'checked="checked"'));
    }

    public function testOutputNoCheckedWhenPostIdNotInValue(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml([999]);
        $this->assertEquals(0, substr_count($output, 'checked="checked"'));
    }

    public function testOutputNoCheckedWhenValueIsEmptyArray(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml([]);
        $this->assertEquals(0, substr_count($output, 'checked="checked"'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec value = '-1'
    // -------------------------------------------------------------------------

    public function testOutputNoCheckedWhenValueIsMinusOne(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml('-1');
        $this->assertEquals(0, substr_count($output, 'checked="checked"'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec default_value
    // -------------------------------------------------------------------------

    public function testOutputChecksDefaultPostIdsWhenValueNotArrayAndNotMinusOne(): void
    {
        $field  = $this->makeField(['default_value' => [10, 20]], $this->defaultPosts());
        $output = $field->outputHtml('');
        $this->assertEquals(2, substr_count($output, 'checked="checked"'));
    }

    public function testOutputValueTakesPriorityOverDefault(): void
    {
        $field  = $this->makeField(['default_value' => [10]], $this->defaultPosts());
        $output = $field->outputHtml([30]);
        $this->assertEquals(1, substr_count($output, 'checked="checked"'));
        $pos_val     = strpos($output, 'value="30"');
        $pos_checked = strpos($output, 'checked="checked"');
        $this->assertLessThan($pos_checked, $pos_val);
    }

    // -------------------------------------------------------------------------
    // saveValue
    // -------------------------------------------------------------------------

    public function testSaveValueReturnsValueWhenNotEmpty(): void
    {
        $result = $this->makeField()->saveValue([10, 20]);
        $this->assertSame([10, 20], $result);
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
        $result = $this->makeField()->saveValue([30]);
        $this->assertSame([30], $result);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field  = $this->makeField(['explanation' => 'Select posts'], $this->defaultPosts());
        $output = $field->outputHtml('-1');
        $this->assertStringContainsString('Select posts', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField([], $this->defaultPosts())->outputHtml('-1');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
