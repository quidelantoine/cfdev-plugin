<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;

class Link extends Field
{
    public bool $supports_repeatable = false;
    public bool $supports_bundle     = true;
    public bool $supports_ajax       = false;

    public function outputHtml(string|array $value): string
    {
        $url    = is_array($value) ? (string) ($value['url']    ?? '') : '';
        $text   = is_array($value) ? (string) ($value['text']   ?? '') : '';
        $target = is_array($value) ? (string) ($value['target'] ?? '_self') : '_self';

        $base = "cfdev{$this->pre}[{$this->id}]";

        return sprintf(
            '<div class="cfdev-link-wrap" %10$s>'
                . '<input type="url" name="%1$s[url]" value="%2$s" placeholder="https://" class="cfdev-link-url" />'
                . '<input type="text" name="%1$s[text]" value="%3$s" placeholder="%4$s" class="cfdev-link-text" />'
                . '<select name="%1$s[target]" class="cfdev-link-target">'
                    . '<option value="_self"%5$s>%6$s</option>'
                    . '<option value="_blank"%7$s>%8$s</option>'
                . '</select>'
            . '</div>%9$s',
            esc_attr($base),
            esc_attr($url),
            esc_attr($text),
            esc_attr(__('Link text', 'cfdev')),
            $target === '_self'  ? ' selected' : '',
            esc_html(__('Same tab', 'cfdev')),
            $target === '_blank' ? ' selected' : '',
            esc_html(__('New tab', 'cfdev')),
            $this->outputExplanation(),
            $this->outputId()
        );
    }

    /**
     * @param string|array<mixed> $value
     * @return string|array<mixed>
     */
    public function saveValue(string|array $value): string|array
    {
        if (! is_array($value)) {
            return ['url' => '', 'text' => '', 'target' => '_self'];
        }

        $target = (string) ($value['target'] ?? '_self');

        return [
            'url'    => esc_url_raw((string) ($value['url']  ?? '')),
            'text'   => sanitize_text_field((string) ($value['text'] ?? '')),
            'target' => in_array($target, ['_blank', '_self'], true) ? $target : '_self',
        ];
    }
}