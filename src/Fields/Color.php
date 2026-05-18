<?php

namespace CFDev\Fields;

use CFDev\Field;

class Color extends Field
{
    public bool $supports_ajax = true;
    public bool $supports_bundle = true;
    public array $css_classes = array( 'js-cfdev-colorpicker', 'cfdev-colorpicker', 'colorpicker', 'cfdev-input' );
}
