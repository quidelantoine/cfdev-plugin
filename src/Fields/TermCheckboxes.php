<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;
use Weblitzer\CFDev\Support\Str;

class TermCheckboxes extends Field
{
    public bool $supports_bundle = true;

    /** @var array<string> */
    public array $css_classes = array( 'cfdev-input' );
    public mixed $terms = null;

    /** @param array<mixed> $field */
    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);

        $this->args = array_merge(
            array(
                'taxonomy' => 'category',
            ),
            $this->args
        );

        $this->default_value = (array) $this->default_value;

        //add_action('init', array( &$this, 'get_taxonomy_terms' ));
        $this->terms = get_terms($this->args['taxonomy'], $this->args);

        $this->after .= '[]';
    }

    /** @param string|array<mixed> $value */
    public function outputHtml(string|array $value): string
    {
        if (!is_array($this->terms)) {
            return $this->outputExplanation();
        }

        $checkboxes = '';
        foreach ($this->terms as $term) {
            $checkboxes .= $this->buildCheckbox($term, $value);
        }

        return sprintf(
            '<div %s class="cfdev-checkboxes-wrap">%s</div>%s',
            $this->outputId(),
            $checkboxes,
            $this->outputExplanation()
        );
    }

    /** @param string|array<mixed> $value */
    private function buildCheckbox(object $term, string|array $value): string
    {
        /** @var \WP_Term $term */
        $termId    = $term->term_id;
        $termName  = $term->name;
        $inputId   = $this->id . $this->after_id . '_' . Str::uglify($termName);
        $checked   = $this->resolveChecked($termId, $value);

        $input = sprintf(
            '<input type="checkbox" %s %s %s value="%s" %s/>',
            $this->outputName(),
            $this->outputId($inputId),
            $this->outputCssClass(),
            htmlspecialchars((string) $termId, ENT_QUOTES, 'UTF-8'),
            $checked
        );

        $label = sprintf('<label for="%s">%s</label>', $inputId, htmlspecialchars($termName, ENT_QUOTES, 'UTF-8'));

        return $input . ' ' . $label . '<br />';
    }

    /** @param string|array<mixed> $value */
    private function resolveChecked(int $termId, string|array $value): string
    {
        $isChecked = match (true) {
            is_array($value)  => in_array($termId, $value),
            $value === '-1'   => false,
            default           => in_array($termId, (array) $this->default_value),
        };

        return $isChecked ? 'checked="checked"' : '';
    }


//    public function outputHtml(string|array $value): string
//    {
//        $output = '<div class="cfdev-checkboxes-wrap">';
//        if (is_array($this->terms)) {
//            foreach ($this->terms as $term) {
//                $checked = '';
//                if (is_array($value)) {
//                    if (in_array($term->term_id, $value)) {
//                        $checked = ' checked="checked"';
//                    }
//                } elseif ($value == '-1') {
//                } elseif (in_array($term->term_id, $this->default_value)) {
//                    $checked = ' checked="checked"';
//                }
//
//
//                $output .= '<input type="checkbox" ' . $this->outputName() . ' ' .
// $this->outputId($this->id . $this->after_id . '_' . Str::uglify($term->name)) . ' ' .
// $this->outputCssClass() . ' value="' . $term->term_id . '" ' . $checked . '/> ';
//
//                $output .= '<label for="' . $this->id . $this->after_id . '_' . Str::uglify($term->name) . '">' . $term->name . '</label>';
//                $output .= '<br />';
//            }
//        }
//        $output .= '</div>';
//
//        $output .= $this->outputExplanation();
//
//        return $output;
//    }

    /**
     * @param  string|array<mixed>  $value
     * @return string|array<mixed>
     */
    public function saveValue(string|array $value): string|array
    {
        return empty($value) ? '-1' : $value;
    }

//    /**
//     * Gets taxonomy terms for use in the output
//     *
//     * @author  Abhinav Sood
//     * @since   1.0.0
//     *
//     */
//    public function get_taxonomy_terms()
//    {
//        $this->terms = get_terms($this->args['taxonomy'], $this->args);
//    }
}
