# DevOps — CFDev

Toutes les commandes pour développer, tester et livrer le plugin.

---
```php
// wp-config.php
define('CFDEV_DEMO', true);
// src/demo/demo-notices.php — activé uniquement quand :
define('CFDEV_DEMO',         true);
define('CFDEV_DEMO_NOTICES', true);
```


## Dépendances

```bash

# PHP (dev)
composer install

# PHP (production — pas de dev, autoloader optimisé)
composer install --no-dev --optimize-autoloader --classmap-authoritative

# JavaScript / Cypress
npm install
```

```php
define('CFDEV_DEMO', true);
```
---

## Tests unitaires (PHPUnit)

Pas besoin de WordPress — Brain/Monkey mocke les fonctions WP.

```bash

# Tous les tests
./vendor/bin/phpunit

# Suite unitaire uniquement
./vendor/bin/phpunit --testsuite Unit

# Suite d'intégration uniquement (requiert une instance WP)
./vendor/bin/phpunit --testsuite Integration

# Un fichier précis
./vendor/bin/phpunit tests/Unit/Fields/TextTest.php

# Avec affichage des deprecations
./vendor/bin/phpunit --display-deprecations

# Avec couverture de code (nécessite Xdebug ou PCOV)
./vendor/bin/phpunit --coverage-text
```

---

## Linting PHP (PHPCS / PHPCBF)

Standard : `WordPress-VIP-Go` + `PSR12` (voir `phpcs.xml`). Longueur max : 160 caractères.

```bash
# Vérifier uniquement (lecture seule)
vendor/bin/phpcs
vendor/bin/phpcs -s

# Correction automatique
vendor/bin/phpcbf

# Vérifier un fichier précis
vendor/bin/phpcs src/Admin/DashboardPage.php
```

---

## Linting JS (ESLint)

Config : `eslint.config.js`. Règles : `no-shadow` + `no-undef` en **error** (bloquant), `no-var` / `eqeqeq` en warning.

```bash
# Vérifier (lecture seule)
npm run lint:js

# Correction automatique des warnings fixables
npm run lint:js:fix

# Un fichier précis
npx eslint assets/js/functions.js
```

> `cypress.config.cjs` (et non `.js`) — renommé pour coexister avec `"type": "module"` dans `package.json`.

---

## Analyse statique (PHPStan)

Niveau configuré dans `phpstan.neon`. La baseline `phpstan-baseline.neon` contient les suppressions connues.

```bash
# Analyse complète
vendor/bin/phpstan analyse

# Avec limite mémoire explicite
vendor/bin/phpstan analyse --memory-limit=512M

# Régénérer la baseline (après avoir corrigé ou accepté de nouvelles erreurs)
vendor/bin/phpstan analyse --generate-baseline
```

---

## Tests E2E (Cypress)

Requiert une instance WordPress locale active avec le plugin activé et les champs DEMO chargés.

```bash
# Ouvrir le Test Runner interactif
npm run cy:open

# Lancer tous les specs en headless
npm run cy:run

# Lancer tous les specs avec navigateur visible
npm run cy:run:headed

# Un spec précis
npx cypress run --spec "cypress/e2e/06-page-accordion.cy.js" --browser chrome
npx cypress run --spec "cypress/e2e/05-page-tabs.cy.js" --browser chrome
npx cypress run --spec "cypress/e2e/03-validation.cy.js" --browser chrome
npx cypress run --spec "cypress/e2e/09-rest-api.cy.js" --browser chrome
npx cypress run --spec "cypress/e2e/16-options-pages.cy.js" --browser chrome
```

### Specs disponibles

| Fichier | Couverture |
|---|---|
| `01-login.cy.js` | Connexion admin |
| `02-flat-fields.cy.js` | Champs plats (text, image, select…) |
| `03-validation.cy.js` | Règles de validation |
| `04-bundle.cy.js` | Bundle (lignes répétables) |
| `05-post-tabs.cy.js` | Tabs sur post |
| `05-page-tabs.cy.js` | Tabs sur page |
| `06-post-accordion.cy.js` | Accordion sur post |
| `06-page-accordion.cy.js` | Accordion sur page |
| `07-term-meta.cy.js` | Term meta |
| `08-user-meta.cy.js` | User meta |
| `09-rest-api.cy.js` | Endpoints REST |
| `10-admin-pages.cy.js` | Pages admin CFDev |
| `11-front-end.cy.js` | Lecture côté front |
| `12-code-modal.cy.js` | Modale code snippet |
| `13-field-icons.cy.js` | Icônes de champs |
| `14-media-fields.cy.js` | Image, File, Gallery, Link (bypass media picker via hidden input) |
| `15-skipped-fields.cy.js` | Datetime, Time, MultiSelect, PostSelect/Checkboxes, TermSelect/Checkboxes, UserSelect/Checkboxes |

---

# 1. Committer tout ton travail
git add .
git commit -m "..."

# 2. Pusher les commits
git push origin main

# 3. Créer le tag et le pusher → déclenche le workflow
git tag v1.0.6
git push origin v1.0.6

#####
git add .github/workflows/release.yml
git commit -m "fix(ci): build dans /tmp pour éviter conflit rsync source/dest + Node 24"
git tag v1.0.1
git push origin main v1.0.1

## Résumé rapide

```bash
# Vérification complète avant commit
./vendor/bin/phpunit --testsuite Unit && vendor/bin/phpcs && vendor/bin/phpstan analyse
```


## Development

```bash
# Tests
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Integration --bootstrap tests/Integration/bootstrap.php

# Code quality
vendor/bin/phpcs -s
vendor/bin/phpstan analyse src tests

# E2E (requires docker compose up -d)
npm run cy:run
npm run cy:open
```

---

## Réinstallation sur un nouveau poste

Tout ce qu'il faut pour que les tests Cypress passent sur une installation fraîche du projet FrankenPHP.

---

### 1. Prérequis

- Docker + Docker Compose
- Node.js ≥ 20 (pour Cypress)
- PHP 8.2+ + Composer (pour les tests unitaires / PHPCS)

---

### 2. Structure du projet hôte

```
test5_frankenphp/
├── app/                  ← bind-monté sur /app/public dans le conteneur
│   ├── wp-config.php
│   ├── wp-content/
│   │   ├── mu-plugins/   ← à créer manuellement (voir § 6)
│   │   ├── plugins/cfdev-plugin/
│   │   └── themes/webvite/
│   └── ...
├── docker-compose.yml
└── Dockerfile
```

---

### 3. Lancer les conteneurs

```bash
cd test5_frankenphp
docker compose up -d
```

Services démarrés : `php` (FrankenPHP, port 443), `db` (MySQL 8.4, port 3306), `phpmyadmin` (port 8080).

---

### 4. `wp-config.php` — constantes requises

Ajouter dans la section "custom values" (avant le `/* That's all */`) :

```php
// Mémoire PHP — obligatoire pour les tests avec beaucoup de champs (bundle/tabs/accordion).
// Sans ça : Fatal error "Allowed memory size of 134217728 bytes exhausted" sur les saves.
define('WP_MEMORY_LIMIT',     '256M');
define('WP_MAX_MEMORY_LIMIT', '256M');

// Écriture directe sans FTP (obligatoire dans Docker)
define('FS_METHOD', 'direct');

// Mode démo CFDev — charge tous les champs de démonstration (demo-*.php)
// et les notices de debug dans l'admin. Requis pour tous les specs Cypress.
define('CFDEV_DEMO',         true);
define('CFDEV_DEMO_NOTICES', true);

// Debug WP (facultatif mais pratique en dev local)
define('WP_DEBUG',     true);
define('WP_DEBUG_LOG', true);
```

---

### 5. Plugins à activer

Via WP Admin → Extensions, ou WP-CLI :

| Plugin | Rôle |
|---|---|
| `cfdev-plugin` | Le plugin à tester — **obligatoire** |
| `classic-editor` | Désactive Gutenberg — **obligatoire** pour les specs qui interagissent avec les meta boxes |

Après activation de Classic Editor, forcer le mode classic pour tous les utilisateurs :

```sql
-- Via phpMyAdmin (http://localhost:8080) ou directement en DB
INSERT INTO wp_options (option_name, option_value) VALUES
  ('classic-editor-replace',     'classic'),
  ('classic-editor-allow-users', 'disallow')
ON DUPLICATE KEY UPDATE option_value = VALUES(option_value);
```

---

### 6. Créer le dossier `mu-plugins` et le symlink

Le mu-plugin `ci-disable-block-editor.php` :
- désactive Gutenberg indépendamment du Classic Editor
- enregistre les templates de test dans le sélecteur de page
- expose le endpoint `/?cfdev_render=ID` pour le spec 11

```bash
# Depuis la racine du projet (test5_frankenphp/)
mkdir -p app/wp-content/mu-plugins
ln -s ../plugins/cfdev-plugin/tests/mu-plugins/ci-disable-block-editor.php \
      app/wp-content/mu-plugins/ci-disable-block-editor.php
```

> **Symlink relatif obligatoire.** Le chemin absolu hôte (`/home/…`) n'est pas valide
> à l'intérieur du conteneur Docker où le volume est monté sous `/app/public`.

---

### 7. Options WordPress à configurer

#### Via phpMyAdmin (http://localhost:8080) ou SQL direct

```sql
INSERT INTO wp_options (option_name, option_value) VALUES
  ('permalink_structure',        '/%postname%/'),
  ('cfdev_cache_enabled',        '1'),
  ('cfdev_rest_enabled',         '1'),
  ('cfdev_api_enabled',          '1')
ON DUPLICATE KEY UPDATE option_value = VALUES(option_value);
```

Puis **vider les règles de réécriture** (nécessaire après changement de permalink) :

```bash
# WP-CLI depuis le conteneur si disponible
docker compose exec php wp rewrite flush --path=/app/public

# Sinon : aller dans WP Admin → Réglages → Permaliens et cliquer "Enregistrer"
```

#### Thème actif

Le thème `webvite` doit être actif (il fournit `header.php` / `footer.php` appelés par les templates de test). Le mu-plugin injecte `template-home.php` et `template-cfdev-test.php` dans le sélecteur sans qu'il soit nécessaire de les placer dans le thème.

---

### 8. Page pour le spec 11 (front-end)

Créer une page WordPress avec :

| Champ | Valeur |
|---|---|
| Titre | `CFDev Test` |
| Slug | `cfdev-test` |
| Statut | Publié |
| Template | `CFDev Test` (exposé par le mu-plugin) |

Via WP Admin → Pages → Ajouter, ou SQL :

```sql
-- 1. Créer la page
INSERT INTO wp_posts (post_title, post_name, post_status, post_type, post_date, post_date_gmt, post_modified, post_modified_gmt)
VALUES ('CFDev Test', 'cfdev-test', 'publish', 'page', NOW(), UTC_TIMESTAMP(), NOW(), UTC_TIMESTAMP());

-- 2. Récupérer son ID (notez-le : X)
SELECT ID FROM wp_posts WHERE post_name = 'cfdev-test' AND post_type = 'page';

-- 3. Assigner le template
INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
VALUES (X, '_wp_page_template', 'template-cfdev-test.php')
ON DUPLICATE KEY UPDATE meta_value = 'template-cfdev-test.php';
```

> Le spec 11 n'utilise pas l'URL de la page — il passe par `/?cfdev_render=POST_ID`
> (géré par le mu-plugin). La page n'a donc pas besoin d'être accessible via son slug.

---

### 9. Compte admin WordPress

Le compte utilisé par Cypress est défini dans `cypress.env.json` (à la racine du plugin) :

```json
{
  "WP_USER": "quidelantoine",
  "WP_PASS": "weblitzer"
}
```

Ce compte doit exister dans WordPress avec le rôle **Administrateur**.

Pour changer les identifiants : modifier `cypress.env.json` (non commité si sensible — vérifier `.gitignore`).

---

### 10. Installer les dépendances du plugin

```bash
cd app/wp-content/plugins/cfdev-plugin

# PHP
composer install

# JavaScript / Cypress
npm install
```

---

### 11. Lancer les tests Cypress

```bash
cd app/wp-content/plugins/cfdev-plugin

# Tous les specs headless
npm run cy:run

# Un spec précis
npx cypress run --spec "cypress/e2e/11-front-end.cy.js" --browser chrome

# Mode interactif
npm run cy:open
```

Par défaut, Cypress pointe sur `https://localhost` (FrankenPHP avec certificat auto-signé —
ignoré automatiquement par le flag `--ignore-certificate-errors`).
Pour pointer ailleurs : `CYPRESS_BASE_URL=https://monsite.local npm run cy:run`.

---

### Récapitulatif checklist

```
[ ] docker compose up -d
[ ] wp-config.php : WP_MEMORY_LIMIT 256M, WP_MAX_MEMORY_LIMIT 256M, FS_METHOD direct, CFDEV_DEMO true
[ ] Plugins actifs : cfdev-plugin + classic-editor
[ ] Options DB : permalink /%postname%/, cfdev_cache/rest/api_enabled = 1
[ ] Options DB : classic-editor-replace = classic, classic-editor-allow-users = disallow
[ ] mu-plugins/ créé + symlink relatif vers tests/mu-plugins/ci-disable-block-editor.php
[ ] Page "CFDev Test" publiée avec template-cfdev-test.php
[ ] Permaliens flushés (Admin → Réglages → Permaliens)
[ ] Thème webvite actif
[ ] cypress.env.json avec les bons identifiants admin
[ ] composer install + npm install dans le plugin
```

---


# Coverage — Unit (rapide, sans DB)
docker compose exec -w /app/public/wp-content/plugins/cfdev-plugin php \
php -d pcov.enabled=1 vendor/bin/phpunit --testsuite Unit --coverage-php coverage/unit.cov --no-progress

# Coverage — Integration (nécessite docker compose up -d db)
docker compose exec -w /app/public/wp-content/plugins/cfdev-plugin php \
php -d pcov.enabled=1 vendor/bin/phpunit --testsuite Integration --bootstrap tests/Integration/bootstrap-docker.php --coverage-php coverage/integration.cov --no-progress

# Fusion + rapport HTML (nécessite d'avoir lancé les deux commandes ci-dessus)
docker compose exec -w /app/public/wp-content/plugins/cfdev-plugin php \
php -d pcov.enabled=1 vendor/bin/phpcov merge --html coverage/html coverage/
# → ouvrir app/wp-content/plugins/cfdev-plugin/coverage/html/index.html

# Rapport texte rapide (Unit seul)
docker compose exec -w /app/public/wp-content/plugins/cfdev-plugin php \
php -d pcov.enabled=1 vendor/bin/phpunit --testsuite Unit --coverage-text --no-progress


## Gestion des versions

### Vérifier la compatibilité PHP

```bash
# Signale toute syntaxe absente avant la version cible (ex : 8.2-)
vendor/bin/phpcs --standard=PHPCompatibilityWP --runtime-set testVersion 8.2- src/

# Ou via phpcs.xml (testVersion déjà configuré à 8.2-)
vendor/bin/phpcs src/
```

Pour changer le plancher, modifier `testVersion` dans `phpcs.xml` :

```xml
<config name="testVersion" value="8.2-"/>
```

### Trouver le plancher WordPress

Pas d'outil automatique équivalent pour WP. Méthode manuelle :

```bash
# Lister toutes les fonctions/classes WP utilisées dans src/
grep -roh --include="*.php" -P '(?<!\w)(wp_\w+|WP_\w+|register_\w+|add_\w+|get_\w+|update_\w+|delete_\w+)\(' src/ \
  | sort -u
grep -roh --include="*.php" -P '\b(wp_\w+|WP_\w+|register_\w+|add_meta_box|get_term_meta|update_term_meta|delete_term_meta|get_user_meta|update_user_meta)\(' src/ | sort -u

# Puis vérifier chaque fonction sur https://developer.wordpress.org/reference/
# La plus récente détermine le plancher.
```

Fonctions déterminantes pour ce plugin :

| Fonction | Introduite |
|----------|-----------|
| `get_term_meta` / `update_term_meta` | WP 4.4 |
| `register_rest_route` / `WP_REST_*` | WP 4.4 |
| `register_meta` avec `object_subtype` | WP 4.9.8 |
| `wp_date()` | **WP 5.3** ← plancher actuel |


# prompt
#### 
Rewrite CLAUDE.md based on everything we've done so far — architecture, conventions, gotchas discovered. keep it under 500 words.

### recherche
grep -rn "render_post_filter" .
render_post_filter

# replace texte
# Prévisualiser sans modifier (dry run)
grep -rn "ancien_texte" . --include="*.php"
grep -rn "Gijs Jorissen" . --include="*.php"

grep -rn --exclude-dir=vendor "ancien_texte" .


# Tous types de fichiers
find . -type f -exec sed -i 's/ancien_texte/nouveau_texte/g' {} +
find . -type f -exec sed -i 's/Gijs Jorissen/quidelantoine/g' {} +
# Insensible à la casse
sed -i 's/ancien_texte/nouveau_texte/gi'


# Claude code

```bash
# Dans la session interactive
> Analyse ce fichier @src/utils/validation.js

# En mode non-interactif (-p)
claude -p "Explique cette fonction @src/auth.ts"

# Pipe direct — zéro exploration de repo
cat fichier | claude -p "..."
cat src/auth.ts | claude -p "Explique cette fonction"

# Ou avec redirection
claude -p "Trouve les bugs dans ce code" < src/auth.ts
```

## Recherche & remplacement (helpers)

```bash
# Rechercher
grep -rn "render_post_filter" .
grep -rn --exclude-dir=vendor "ancien_texte" .

# Remplacer
find . -type f -exec sed -i 's/ancien_texte/nouveau_texte/g' {} +
```