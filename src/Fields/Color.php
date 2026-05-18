<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;

class Color extends Field
{
    public bool $supports_ajax        = true;
    public bool $supports_bundle      = true;
    public bool $supports_repeatable  = true;
    /** @var array<string> */
    public array $css_classes = array( 'js-cfdev-colorpicker', 'cfdev-colorpicker', 'colorpicker', 'cfdev-input' );
}
