<?php

namespace CFDev\Fields;

use CFDev\Abstracts\FieldContainer;

class Tabs extends FieldContainer
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
                
        echo '<div class="js-cfdev-tabs cfdev-tabs">';
            echo '<ul>';
        foreach ($tabs as $title => $tab) {
            echo '<li><a href="#cfdev-' . esc_attr($tab->id) . '">' . esc_html($tab->title) . '</a></li>';
        }
            echo '</ul>';
        foreach ($tabs as $title => $tab) {
            $tab->output($post, 'tabs');
        }
        echo '</div>';
    }
}
