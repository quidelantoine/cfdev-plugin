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
npx cypress run --spec "cypress/e2e/04-bundle.cy.js" --browser chrome
npx cypress run --spec "cypress/e2e/11-front-end.cy" --browser chrome
npx cypress run --spec "cypress/e2e/09-rest-api.cy.js" --browser chrome
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

---

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