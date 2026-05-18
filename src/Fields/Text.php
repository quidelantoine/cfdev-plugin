<?php

namespace CFDev\Fields;

use CFDev\Field;

class Text extends Field
{
    public bool $supports_repeatable   = true;
    public bool $supports_bundle       = true;
    public bool $supports_ajax         = true;

    /** @var array<string> */
    public array $css_classes            = array( 'cfdev-input' );

    /**
     * @param  string|array<mixed>  $value
     * @return string|array<mixed>
     */
    public function saveValue(string|array $value): string|array
    {
        if (is_array($value)) {
            array_walk_recursive($value, array( $this, 'doHtmlspecialchars' ));
        } else {
            $value = htmlspecialchars($value);
        }

        return $value;
    }

    public function doHtmlspecialchars(string &$value): void
    {
        $value = htmlspecialchars($value);
    }
}
