<?php

/**
 * Template Name: CFDev Test
 *
 * Renders CFDev demo flat fields and bundle rows as plain HTML for Cypress E2E tests.
 * Elements use [data-cfdev="field_id"] so selectors stay stable regardless of markup changes.
 *
 * Usage: cy.get('[data-cfdev="_text_demo_flat_text"]').should('have.text', 'expected')
 *        cy.get('[data-cfdev-bundle-row="0"] [data-cfdev="_text_demo_bundle_text"]').should('have.text', 'Row 1')
 *
 * ?post_id=X loads data for a specific post instead of the current page.
 */

$cache = new \Weblitzer\CFDev\Cache\CacheManager();

// ?post_id=X lets Cypress (or any caller) request data for a specific post.
// Falls back to the current page ID so the template also works as a real page.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$page_id   = get_the_ID();
$target_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : ($page_id !== false ? $page_id : 0);

$post_data = $cache->post($target_id);
$flat      = $post_data['groups']['cfdev_demo_flat'] ?? [];
$bundle    = $post_data['groups']['cfdev_demo_bundle']['_cfdev_demo_bundle'] ?? [];

get_header();
?>

<main id="cfdev-test-page">

    <section id="cfdev-flat">
        <?php
        // Plain scalar fields — output as <span data-cfdev="field_id">value</span>
        $scalar_fields = [
            '_text_demo_flat_text',
            '_text_demo_flat_textarea',
            '_text_demo_flat_qty',
            '_text_demo_flat_rate',
            '_text_demo_flat_range',
            '_text_demo_flat_email',
            '_text_demo_flat_website',
            '_text_demo_flat_phone',
            '_text_demo_flat_toggle',
            '_text_demo_flat_checkbox',
            '_text_demo_flat_yesno',
            '_text_demo_flat_select',
            '_text_demo_flat_color',
        ];
        foreach ($scalar_fields as $field_id) {
            $value = $flat[$field_id] ?? '';
            if ($value === '') {
                continue;
            }
            printf(
                '<span data-cfdev="%s">%s</span>' . "\n",
                esc_attr($field_id),
                esc_html((string) $value)
            );
        }

        // Date fields — stored as Unix timestamps; format for display and assertion
        $date_fields = [
            '_date_demo_flat_date'     => 'm/d/Y',
            '_date_demo_flat_datetime' => 'm/d/Y H:i',
            '_date_demo_flat_time'     => 'H:i',
        ];
        foreach ($date_fields as $field_id => $fmt) {
            $raw = $flat[$field_id] ?? '';
            if ($raw === '' || ! is_numeric($raw)) {
                continue;
            }
            printf(
                '<span data-cfdev="%s">%s</span>' . "\n",
                esc_attr($field_id),
                esc_html(gmdate($fmt, (int) $raw))
            );
        }

        // radios → singleValue() → string
        $radios = $flat['_text_demo_flat_radios'] ?? '';
        if ($radios !== '') {
            printf('<span data-cfdev="_text_demo_flat_radios">%s</span>' . "\n", esc_html($radios));
        }

        // checkboxes → multiValue() → array; join with comma for easy assertion
        $checkboxes = $flat['_text_demo_flat_checkboxes'] ?? [];
        if (!empty($checkboxes) && is_array($checkboxes)) {
            printf(
                '<span data-cfdev="_text_demo_flat_checkboxes" data-values="%s">%s</span>' . "\n",
                esc_attr(implode(',', $checkboxes)),
                esc_html(implode(',', $checkboxes))
            );
        }

        // multi_select → multiValue() → array
        $multiselect = $flat['_text_demo_flat_multiselect'] ?? [];
        if (!empty($multiselect) && is_array($multiselect)) {
            printf(
                '<span data-cfdev="_text_demo_flat_multiselect" data-values="%s">%s</span>' . "\n",
                esc_attr(implode(',', $multiselect)),
                esc_html(implode(',', $multiselect))
            );
        }
        ?>
    </section>

    <section id="cfdev-bundle">
        <?php foreach ($bundle as $index => $row) : ?>
            <div data-cfdev-bundle-row="<?php echo esc_attr($index); ?>">
                <?php
                $bundle_scalar = [
                    '_text_demo_bundle_text',
                    '_text_demo_bundle_textarea',
                    '_text_demo_bundle_qty',
                    '_text_demo_bundle_rate',
                    '_text_demo_bundle_range',
                    '_text_demo_bundle_email',
                    '_text_demo_bundle_website',
                    '_text_demo_bundle_phone',
                    '_text_demo_bundle_toggle',
                    '_text_demo_bundle_checkbox',
                    '_text_demo_bundle_yesno',
                    '_text_demo_bundle_select',
                    '_text_demo_bundle_color',
                    '_date_demo_bundle_time',
                ];
                foreach ($bundle_scalar as $field_id) {
                    $value = $row[$field_id] ?? '';
                    if ($value === '') {
                        continue;
                    }
                    printf(
                        '<span data-cfdev="%s">%s</span>' . "\n",
                        esc_attr($field_id),
                        esc_html((string) $value)
                    );
                }

                $b_date = $row['_date_demo_bundle_date'] ?? '';
                if ($b_date !== '' && is_numeric($b_date)) {
                    printf(
                        '<span data-cfdev="_date_demo_bundle_date">%s</span>' . "\n",
                        esc_html(gmdate('m/d/Y', (int) $b_date))
                    );
                }

                $b_radios = $row['_text_demo_bundle_radios'] ?? '';
                if ($b_radios !== '') {
                    printf('<span data-cfdev="_text_demo_bundle_radios">%s</span>' . "\n", esc_html($b_radios));
                }

                $b_checkboxes = $row['_text_demo_bundle_checkboxes'] ?? [];
                if (!empty($b_checkboxes) && is_array($b_checkboxes)) {
                    printf(
                        '<span data-cfdev="_text_demo_bundle_checkboxes" data-values="%s">%s</span>' . "\n",
                        esc_attr(implode(',', $b_checkboxes)),
                        esc_html(implode(',', $b_checkboxes))
                    );
                }
                ?>
            </div>
        <?php endforeach; ?>
    </section>

</main>

<?php get_footer(); ?>
