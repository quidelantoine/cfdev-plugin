<?php

namespace Weblitzer\CFDev;

/**
 * Displays a WordPress admin notice
 *
 * @author  quidelantoine
 * @since   1.0.0
 */
class Notice
{
    /**
     * @since   1.0.0
     * @param string|string[] $message  Plain string or array of items rendered as <ul>
     */
    public function __construct(
        private readonly string|array $message,
        private readonly string $type = 'success',
        private readonly bool $dismissible = false,
    ) {
    }

    /**
     * Registers the notice to be rendered when admin_notices fires.
     * Use this when Notice is created BEFORE the admin_notices hook.
     *
     * @param list<string>|null $screen_ids Restrict to these screen IDs. null = all admin pages.
     */
    public function register(?array $screen_ids = null): void
    {
        if ($screen_ids === null) {
            add_action('admin_notices', $this->render(...));
            return;
        }

        add_action('admin_notices', function () use ($screen_ids): void {
            $screen = get_current_screen();
            if ($screen && in_array($screen->id, $screen_ids, true)) {
                $this->render();
            }
        });
    }

    /**
     * @since   1.0.0
     */
    public function render(): void
    {
        $classes = 'notice notice-' . $this->type
            . ($this->dismissible ? ' is-dismissible' : '');

        $body = is_array($this->message)
            ? $this->renderList($this->message)
            : '<p>' . esc_html($this->message) . '</p>';

        printf(
            '<div class="%s">%s</div>',
            esc_attr($classes),
            $body // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
    }

    /** @param array<string> $items */
    private function renderList(array $items): string
    {
        $lis = implode('', array_map(
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            fn(string $item) => '<li>' . wp_kses_post($item) . '</li>',
            $items
        ));

        return '<ul style="list-style:disc;margin-left:1.5em">' . $lis . '</ul>';
    }
}
