<?php

namespace CFDev\Fields;

use CFDev\Field;
use CFDev\Support\Str;

class PostCheckboxes extends Field
{
    public bool $supports_bundle = true;
    
    public array $css_classes = array( 'cfdev-input' );

    protected array $posts = array();

    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);

        $this->args = array_merge(
            array(
                'post_type'         => 'post',
                'posts_per_page'    => -1
            ),
            $this->args
        );
        
        $this->default_value = (array) $this->default_value;
        $this->posts = get_posts($this->args);
        $this->after .= '[]';
    }

    public function outputHtml(string|array $value): string
    {
        if (!is_array($this->posts)) {
            return $this->outputExplanation();
        }

        $html = '';
        foreach ($this->posts as $post) {
            $html .= $this->buildCheckbox($post, $value);
        }

        return sprintf(
            '<div %s class="cfdev-checkboxes-wrap">%s</div>%s',
            $this->outputId(),
            $html,
            $this->outputExplanation()
        );
    }

    private function buildCheckbox(object $post, string|array $value): string
    {
        $inputId = $this->id . $this->after_id . '_' . Str::uglify($post->post_title);

        $input = sprintf(
            '<input type="checkbox" %s %s %s value="%s" %s/>',
            $this->outputName(),
            $this->outputId($inputId),
            $this->outputCssClass(),
            $post->ID,
            $this->resolveChecked($post->ID, $value)
        );

        $label = sprintf(
            '<label for="%s">%s</label>',
            $inputId,
            htmlspecialchars($post->post_title, ENT_QUOTES, 'UTF-8')
        );

        return $input . $label . '<br />';
    }

    private function resolveChecked(int $id, string|array $value): string
    {
        $isChecked = match (true) {
            is_array($value) => in_array($id, $value),
            $value === '-1'  => false,
            default          => in_array($id, $this->default_value),
        };

        return $isChecked ? 'checked="checked"' : '';
    }

    public function saveValue(string|array $value): string|array
    {
        return empty($value) ? '-1' : $value;
    }
}
