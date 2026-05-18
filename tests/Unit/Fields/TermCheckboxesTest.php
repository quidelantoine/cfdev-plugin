<?php

namespace CFDev\Tests\Unit\Fields;

use CFDev\Fields\TermCheckboxes;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class TermCheckboxesTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeTerm(int $id, string $name): object
    {
        return (object)[
            'term_id' => $id,
            'name' => $name,
            'slug' => strtolower($name),
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @param array<int, object>   $terms
     */
    private function makeField(array $overrides = [], array $terms = []): TermCheckboxes
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('get_terms')->justReturn($terms);

        $defaults = [
            'type' => 'term_checkboxes',
            'name' => 'my_terms',
            'label' => 'My Terms',
        ];

        return new TermCheckboxes(array_merge($defaults, $overrides), 'my_metabox');
    }

    /** @return array<int, object> */
    private function defaultTerms(): array
    {
        return [
            $this->makeTerm(1, 'News'),
            $this->makeTerm(2, 'Events'),
            $this->makeTerm(3, 'Sport'),
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

    public function testDefaultTaxonomyIsCategory(): void
    {
        $this->assertSame('category', $this->makeField()->args['taxonomy']);
    }

    public function testCustomTaxonomyIsRespected(): void
    {
        $field = $this->makeField(['args' => ['taxonomy' => 'post_tag']]);
        $this->assertSame('post_tag', $field->args['taxonomy']);
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

    public function testTermsAreLoadedOnConstruction(): void
    {
        $terms = $this->defaultTerms();
        $field = $this->makeField([], $terms);
        $this->assertSame($terms, $field->terms);
    }

    // -------------------------------------------------------------------------
    // outputHtml — structure HTML
    // -------------------------------------------------------------------------

    public function testOutputRendersWrapperDiv(): void
    {
        $output = $this->makeField([], $this->defaultTerms())->outputHtml('-1');
        $this->assertStringContainsString('cfdev-checkboxes-wrap', $output);
    }

    public function testOutputRendersOneCheckboxPerTerm(): void
    {
        $output = $this->makeField([], $this->defaultTerms())->outputHtml('-1');
        $this->assertEquals(3, substr_count($output, 'type="checkbox"'));
    }

    public function testOutputRendersTermNameInLabel(): void
    {
        $output = $this->makeField([], $this->defaultTerms())->outputHtml('-1');
        $this->assertStringContainsString('News', $output);
        $this->assertStringContainsString('Events', $output);
        $this->assertStringContainsString('Sport', $output);
    }

    public function testOutputRendersTermIdAsValue(): void
    {
        $output = $this->makeField([], $this->defaultTerms())->outputHtml('-1');
        $this->assertStringContainsString('value="1"', $output);
        $this->assertStringContainsString('value="2"', $output);
        $this->assertStringContainsString('value="3"', $output);
    }

    public function testOutputEmptyWhenNoTerms(): void
    {
        $output = $this->makeField([], [])->outputHtml('-1');
        $this->assertStringNotContainsString('type="checkbox"', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec value array
    // -------------------------------------------------------------------------

    public function testOutputChecksSelectedTermIds(): void
    {
        $output = $this->makeField([], $this->defaultTerms())->outputHtml([1, 3]);
        $this->assertEquals(2, substr_count($output, 'checked="checked"'));
    }

    public function testOutputNoCheckedWhenValueIsMinusOne(): void
    {
        $output = $this->makeField([], $this->defaultTerms())->outputHtml('-1');
        $this->assertEquals(0, substr_count($output, 'checked="checked"'));
    }

    public function testOutputAllCheckedWhenAllIdsInValue(): void
    {
        $output = $this->makeField([], $this->defaultTerms())->outputHtml([1, 2, 3]);
        $this->assertEquals(3, substr_count($output, 'checked="checked"'));
    }

    public function testOutputNoneCheckedWhenValueIsEmptyArray(): void
    {
        $output = $this->makeField([], $this->defaultTerms())->outputHtml([]);
        $this->assertEquals(0, substr_count($output, 'checked="checked"'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec default_value
    // -------------------------------------------------------------------------

    public function testOutputChecksDefaultValueWhenValueIsNotArrayAndNotMinusOne(): void
    {
        // Quand value n'est pas un array et n'est pas '-1',
        // le code tombe sur in_array($term->term_id, $this->default_value)
        $field = $this->makeField(['default_value' => [2]], $this->defaultTerms());
        $output = $field->outputHtml('');
        $this->assertEquals(1, substr_count($output, 'checked="checked"'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — id et label liés
    // -------------------------------------------------------------------------

    public function testOutputLabelForMatchesInputId(): void
    {
        $terms = [$this->makeTerm(1, 'News')];
        $field = $this->makeField([], $terms);
        $output = $field->outputHtml('-1');
        // L'id du champ contient le slug du terme
        $this->assertStringContainsString('for="' . $field->id, $output);
        $this->assertStringContainsString('_news"', $output);
    }

    // -------------------------------------------------------------------------
    // saveValue
    // -------------------------------------------------------------------------

    public function testSaveValueReturnsValueWhenNotEmpty(): void
    {
        $field = $this->makeField();
        $result = $field->saveValue([1, 2]);
        $this->assertSame([1, 2], $result);
    }

    public function testSaveValueReturnsMinusOneWhenEmptyArray(): void
    {
        $field = $this->makeField();
        $result = $field->saveValue([]);
        $this->assertSame('-1', $result);
    }

    public function testSaveValueReturnsMinusOneWhenEmptyString(): void
    {
        $field = $this->makeField();
        $result = $field->saveValue('');
        $this->assertSame('-1', $result);
    }

    public function testSaveValueStringValueReturnedAsIs(): void
    {
        $field = $this->makeField();
        $result = $field->saveValue('some-value');
        $this->assertSame('some-value', $result);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field = $this->makeField(['explanation' => 'Pick categories'], $this->defaultTerms());
        $output = $field->outputHtml('-1');
        $this->assertStringContainsString('Pick categories', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField([], $this->defaultTerms())->outputHtml('-1');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
