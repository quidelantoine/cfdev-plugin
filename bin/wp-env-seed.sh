#!/usr/bin/env bash
# Seeds WordPress options and test fixtures after a fresh wp-env install.
# Called automatically via .wp-env.json afterSetup; can also be run manually:
#   npm run wp-env:seed
set -euo pipefail

WP="npx wp-env run cli wp"

echo "→ cfdev options…"
$WP option update cfdev_cache_enabled 1
# Écrire explicitement en DB — les valeurs par défaut (1) sont dans le code
# mais get_option() lit la DB, et wp-env part d'une DB vide à chaque run.
$WP option update cfdev_rest_enabled 1
$WP option update cfdev_api_enabled 1

echo "→ Classic Editor options…"
$WP option update classic-editor-replace classic
$WP option update classic-editor-allow-users disallow

echo "→ Permalink structure…"
$WP rewrite structure '/%postname%/'
$WP rewrite flush

echo "→ Test page for spec 11 (front-end)…"
# grep -E '^[0-9]+$' : only match a line that is exclusively digits,
# which avoids grabbing years from Docker Compose timestamps (2026-06-01T…)
# or wp-env status lines that appear in the same stdout stream.
PAGE_ID=$($WP post list \
  --post_type=page \
  --post_name=cfdev-test \
  --post_status=publish \
  --field=ID 2>&1 | grep -E '^[0-9]+$' | head -1)

if [ -z "$PAGE_ID" ]; then
  PAGE_ID=$($WP post create \
    --post_type=page \
    --post_title="CFDev Test" \
    --post_name=cfdev-test \
    --post_status=publish \
    --porcelain 2>&1 | grep -E '^[0-9]+$' | head -1)
fi

if [ -z "$PAGE_ID" ]; then
  echo "✖ Could not resolve page ID for cfdev-test" >&2
  exit 1
fi

$WP post meta update "$PAGE_ID" _wp_page_template template-cfdev-test.php

echo "→ Demo child category for onlyIfParent…"
UNCAT_ID=$($WP term get category uncategorized --by=slug --field=term_id 2>/dev/null || true)
if [ -n "$UNCAT_ID" ]; then
  CHILD_EXISTS=$($WP term get category sous-categorie-demo --by=slug --field=term_id 2>/dev/null || true)
  if [ -z "$CHILD_EXISTS" ]; then
    $WP term create category "Sous-categorie DEMO" \
      --slug=sous-categorie-demo \
      --parent="$UNCAT_ID" \
      --porcelain > /dev/null
    echo "   Created child category under Uncategorized (ID $UNCAT_ID)."
  else
    echo "   Child category already exists (ID $CHILD_EXISTS)."
  fi
else
  echo "   Uncategorized not found — skipping child category."
fi

# Second flush après la création de la page : consolide les règles de réécriture
# pour inclure le nouveau slug cfdev-test (règle générique /%postname%/ suffit
# théoriquement, mais un flush garanti évite les edge-cases Docker/nginx).
echo "→ Final rewrite flush…"
$WP rewrite flush

echo "→ Done (page ID $PAGE_ID)."