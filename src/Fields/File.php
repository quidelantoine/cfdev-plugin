<?php

namespace CFDev\Fields;

use CFDev\Field;

class File extends Field
{
    public bool $supports_ajax   = true;
    public bool $supports_bundle = true;

    public array $css_classes     = array( 'cfdev-hidden', 'cfdev-input' );

    public function outputHtml(string|array $value): string
    {
        return $this->buildHiddenInput($value)
            . $this->buildUploadButton()
            . $this->buildRemoveLink($value)
            . $this->buildPreview($value)
            . $this->outputExplanation();
    }

    private function buildHiddenInput(string|array $value): string
    {
        return sprintf(
            '<input type="hidden" %s %s %s value="%s" />',
            $this->outputName(),
            $this->outputId(),
            $this->outputCssClass(),
            !empty($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : ''
        );
    }

    private function buildUploadButton(): string
    {
        return sprintf(
            '<input id="upload-file-button" type="button" class="button js-cfdev-upload" data-cfdev-media-type="file" value="%s" />',
            esc_attr(__('Select file', 'cfdev'))
        );
    }

    private function buildRemoveLink(string|array $value): string
    {
        if (empty($value)) {
            return '';
        }

        return sprintf(
            '<a href="#" class="js-cfdev-remove-media cfdev-remove-media">%s</a>',
            __('Remove current file', 'cfdev')
        );
    }

    private function buildPreview(string|array $value): string
    {
        return sprintf(
            '<span class="cfdev-preview">%s</span>',
            !empty($value) ? $this->buildFileLink($value) : ''
        );
    }

    private function buildFileLink(string|array $value): string
    {
        $attachment = self::getAttachmentByUrl($value);
        $mime       = '';
        $name       = basename((string) $value);

        if (is_object($attachment)) {
            $mime = $attachment->post_mime_type;
            $name = $attachment->post_title;
        }

        $mime_class = $mime ? ' mime-' . str_replace('/', '_', $mime) : '';

        return sprintf(
            '<span class="cfdev-mime%s"><a target="_blank" href="%s">%s<span class="cfdev-file-name">%s</span></a></span>',
            $mime_class,
            htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
            self::getFileSvg($mime),
            htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
        );
    }

    public static function getFileSvg(string $mime): string
    {
        [$label, $color] = match (true) {
            $mime === 'application/pdf'                                              => ['PDF', '#e74c3c'],
            str_contains($mime, 'word')                                              => ['DOC', '#2980b9'],
            str_contains($mime, 'sheet') || str_contains($mime, 'excel')             => ['XLS', '#27ae60'],
            str_contains($mime, 'presentation') || str_contains($mime, 'powerpoint') => ['PPT', '#e67e22'],
            str_starts_with($mime, 'image/')                                         => ['IMG', '#8e44ad'],
            str_contains($mime, 'zip') || str_contains($mime, 'rar') || str_contains($mime, 'tar') => ['ZIP', '#7f8c8d'],
            str_starts_with($mime, 'video/')                                         => ['VID', '#c0392b'],
            str_starts_with($mime, 'audio/')                                         => ['AUD', '#16a085'],
            default                                                                  => ['FILE', '#95a5a6'],
        };

        return sprintf(
            '<svg class="cfdev-file-svg" xmlns="http://www.w3.org/2000/svg" width="24" height="29" viewBox="0 0 36 44" aria-hidden="true">'
            . '<path d="M0 4C0 1.8 1.8 0 4 0h18l14 14v26c0 2.2-1.8 4-4 4H4C1.8 44 0 42.2 0 40V4z" fill="%s"/>'
            . '<path d="M22 0l14 14H26c-2.2 0-4-1.8-4-4V0z" fill="rgba(0,0,0,.2)"/>'
            . '<text x="18" y="32" font-family="Arial,sans-serif" font-size="11" font-weight="bold" fill="#fff" text-anchor="middle">%s</text>'
            . '</svg>',
            esc_attr($color),
            esc_html($label)
        );
    }



    /**
     * Get attachment post by URL using WordPress's cached lookup.
     *
     * @param  string        $url
     * @return \WP_Post|null
     */
    public static function getAttachmentByUrl(string $url): ?object
    {
        if (empty($url)) {
            return null;
        }

        $cache_key = 'cfdev_att_' . md5($url);
        $id        = wp_cache_get($cache_key, 'cfdev');

        if ($id === false) {
            // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid
            $id = attachment_url_to_postid($url);
            wp_cache_set($cache_key, $id, 'cfdev', HOUR_IN_SECONDS);
        }

        if (! $id) {
            return null;
        }

        return get_post($id) ?: null;
    }
}
