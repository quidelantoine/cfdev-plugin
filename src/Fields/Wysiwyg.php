<?php

namespace CFDev\Fields;

use CFDev\Field;

class Wysiwyg extends Field
{
    public bool $supports_ajax         = true;
    public bool $supports_bundle       = true;
    
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

    public function outputHtml(string|array $value): string
    {
        $this->args['textarea_name'] = sprintf(
            'cfdev%s[%s]%s',
            $this->pre,
            $this->id,
            $this->after
        );

        $content = !empty($value) ? $value : $this->default_value;
        $editorId = $this->pre_id . $this->id . $this->after_id;

        return wp_editor($content, $editorId, $this->args) . $this->outputExplanation();
    }
}
