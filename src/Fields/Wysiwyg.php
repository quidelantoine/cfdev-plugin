<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;

class Wysiwyg extends Field
{
    public bool $supports_ajax         = true;
    public bool $supports_bundle       = true;
    
    /** @param array<mixed> $field */
    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);

        $this->args = array_merge( 
            array(
                'textarea_name' => 'cfdev[' . $this->id . ']',
                'editor_class'  => ''
            ),
            $this->args
        );
        
        $this->args['editor_class'] .= ' cfdev-input';
    }

    /** @param string|array<mixed> $value */
    public function outputHtml(string|array $value): string
    {
        $this->args['textarea_name'] = sprintf(
            'cfdev%s[%s]%s',
            $this->pre,
            $this->id,
            $this->after
        );

        $content = is_string($value) && !empty($value) ? $value : (is_string($this->default_value) ? $this->default_value : '');
        $editorId = $this->pre_id . $this->id . $this->after_id;

        return wp_editor($content, $editorId, $this->args) . $this->outputExplanation();
    }
}
