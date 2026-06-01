#!/usr/bin/env bash
# Seeds WordPress options and test fixtures after a fresh wp-env install.
# Called automatically via .wp-env.json afterSetup; can also be run manually:
#   npm run wp-env:seed
set -euo pipefail

WP="npx wp-env run cli wp"

echo "→ cfdev options…"
$WP option update cfdev_cache_enabled 1

echo "→ Classic Editor options…"
$WP option update classic-editor-replace classic
$WP option update classic-editor-allow-users disallow

echo "→ Permalink structure…"
$WP rewrite structure '/%postname%/'
$WP rewrite flush

echo "→ Test page for spec 11 (front-end)…"
# Check first so the script is idempotent on repeated runs
PAGE_ID=$($WP post list \
  --post_type=page \
  --post_name=cfdev-test \
  --post_status=publish \
  --format=ids 2>&1 | grep -oE '[0-9]+' | head -1)

if [ -z "$PAGE_ID" ]; then
  PAGE_ID=$($WP post create \
    --post_type=page \
    --post_title="CFDev Test" \
    --post_name=cfdev-test \
    --post_status=publish \
    --porcelain 2>&1 | grep -oE '[0-9]+' | head -1)
fi

$WP post meta update "$PAGE_ID" _wp_page_template template-cfdev-test.php
echo "→ Done (page ID $PAGE_ID)."