<?php

namespace CFDev\Fields;

use CFDev\Field;

class PostSelect extends Field
{
    public bool $supports_repeatable   = true;
    public bool $supports_ajax         = true;
    public bool $supports_bundle       = true;

    public array $css_classes            = array( 'cfdev-input cfdev-select cfdev-post-select' );
    public $posts = [];   

    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);

        $this->args = array_merge(
            array(
                'post_type'         => 'post',
                'posts_per_page'    => -1,
                'cache_results'     => false, 
                'no_found_rows'     => true,
            ),
            $this->args
        );

        $this->posts = get_posts($this->args);
    }

    public function outputHtml(string|array $value): string
    {
        $selected_value = ! empty($value) ? $value : $this->default_value;

        $options = '';

        if (isset($this->args['show_option_none'])) {
            $options .= sprintf(
                '<option value="0" %s>%s</option>',
                empty($value) ? 'selected="selected"' : '',
                esc_html($this->args['show_option_none'])
            );
        }

        if (is_array($this->posts)) {
            foreach ($this->posts as $post) {
                $options .= sprintf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr($post->ID),
                    selected($selected_value, $post->ID, false),
                    esc_html($post->post_title)
                );
            }
        }

        return sprintf(
            '<select %s %s %s>%s</select>%s',
            $this->outputName(),
            $this->outputId(),
            $this->outputCssClass(),
            $options,
            $this->outputExplanation()
        );
    }
}
