<?php

namespace CFDev\Fields;

use CFDev\Abstracts\FieldContainer;

class Accordion extends FieldContainer
{
    /** @var array<string, \CFDev\Fields\Tab> */
    public array $tabs = [];

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function output(object $post): void
    {
        $tabs = $this->tabs;

        echo '<div class="js-cfdev-accordion">';
        foreach ($tabs as $title => $tab) {
            $tab->output($post, 'accordion');
        }
        echo '</div>';
    }
}
