<?php

namespace CFDev\Fields;

use CFDev\Abstracts\FieldContainer;

class Accordion extends FieldContainer
{
    public array $tabs = [];

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function output($post)
    {
        $tabs = $this->tabs;

        echo '<div class="js-cfdev-accordion">';
        foreach ($tabs as $title => $tab) {
            $tab->output($post, 'accordion');
        }
        echo '</div>';
    }
}
