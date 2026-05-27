# Priorisation des tâches — CFDev

> Synthèse actionnable de [AFAIRE.md](./AFAIRE.md).
> Logique : débloquer d'abord ce qui bloque le reste, puis maximiser le ROI.

---
FAire un depot gitlab +++ 

repeatable, ajax, test ok ? est ce que je garde ???
Si on garde c'est quoi les limtes etest ce que cela vaut vraiment le coup ??? 

===========
Mieux erire le js faire une passe dessus ? 

=> Js ne pas utiliser jquery est ce une bonn eidée , sachant que cela marche bien
js full vanilla ??, utilisation de vite.js,  et js polyfills
==============
Ajouter un numero dans un ?bundle pour connaitre le nombre d'element ddedans 
=================


=========

========================
trouver un logo ++ perso ????


test cypress peut t'on aller encore plus loin ?
est ce que tous les champs sont bien testé ??

Tous les champs sont tester , unitaire, integration et fonctionnel ??? 

=> Test woocommerce isOK ?? compatible woocommerce ???

=> Ok en terme de test ??
=> duplicate code ????, interface ajouter ? architecture is ok ??? A refaire +++


=> faire un test avec les champs de type repetable

=> Mettre en place ci , phpcs , linter etc , phpstan , eslint ,
-> reste eslint ??? et ci


Gaps dans les tests unitaires existants :, refaire une demande pour completer ++

=> Utiliser plugins traduction loco translate pour générer fichier .mo et .po
allemand, espagnol, chinois


=> reverifier m/d/Y =>  'args' => ['date_format' => 'm/d/Y']]),  ou d/m/Y, mieux de rien mettre ???

// lancer un truc security vulnerabilté via ia , faire la formation ia dyma


## Test
=> Faire un test avec les champ repetable sur tous pour faire le tests
=> tester si cela marche si j'ajoute champ à woocommerce ??

X=> Faire tests unitaire pour Admin à la fin quand terminé, car test sur html sinon on va devoir changer souvent

## 🧪 Règle d'or — Les tests sont une priorité permanente

> La base de tests est déjà solide : +1200 assertions existantes.
> **Chaque nouvelle fonctionnalité = tests écrits en même temps**, pas après.
> Exception : tests Admin HTML — à faire en dernier car liés au HTML qui change souvent.

**Gaps à combler en continu :**
- [ ] Tests repeatable sur tous les types de champs
- [ ] Tests bundle dans term et user
- [ ] Tests accordéon et tabs dans term, user, bundle
- [ ] Tests validation dans tous les contextes
- [ ] Test WooCommerce
- [ ] Tests Admin HTML ← en dernier uniquement

---

## 🏁 Sprint 1 — Finir ce qui est commencé

> Rien de nouveau tant que l'existant n'est pas solide.

| # | Tâche | Tests associés |
|---|-------|----------------|
| 1 | **Tests unitaires API** — refaire la vérif + compléter | ← c'est le test lui-même |
| 2 | **Champs DEMO** — tester dans tous les sens | Valider chaque champ existant |
| 3 | **Repeatable** — test complet fonctionnel | Tests repeatable tous types |
| 4 | **Inspecter groupes de champs** — modale code + données | — |

---

## 🚀 Sprint 2 — ROI rapide, valeur immédiate

> Fonctionnalités courtes, fort impact, tests inclus.

| # | Tâche | Tests associés |
|---|-------|----------------|
| 5 | **Options page** | Tests lecture/écriture `wp_options` |
| 6 | **REST API endpoint** — cache déjà là = 80% fait | Tests endpoints, auth, format JSON |
| 7 | **Admin : IDs uniques** — détection doublons | Tests détection collision |
| 8 | **Admin CFDev visible admins uniquement** | Tests de capacité/rôle |


---

## 🏗️ Sprint 3 — Page admin & DX

> Construire l'interface admin avant d'y ajouter des fonctionnalités.
> Tests Admin HTML reportés à la fin de ce sprint, une fois l'UI stable.

| # | Tâche | Tests associés |
|---|-------|----------------|
| 10 | **Page admin CFDev** — tabs (Cache, Groupes, Réglages) | Tests unitaires logique métier (pas HTML) |
| 11 | **Profiler Symfony-style** — modale + cache | Tests cache invalidation |
| 12 | **Hooks init-custom** — vue d'ensemble groupes | Tests hooks + registre des groupes |
| 13 | **Harmoniser la largeur des champs** | — |
| 14 | **Design metaboxes** — couleur, style custom | — |
| ↳ | **Tests Admin HTML** — une fois l'UI figée | Tests rendu, structure HTML |

---

## 🔧 Sprint 4 — Qualité & outillage

| # | Tâche | Tests associés |
|---|-------|----------------|
| 15 | **i18n** — `__('')` en anglais + `.mo`/`.po` (FR, DE, ES, ZH) | — |
| 16 | **CI complet** — ESLint + pipeline | Les tests existants tournent en CI |
| 17 | **JS vanilla** — supprimer jQuery, évaluer Vite.js | Tests JS (ESLint + comportement) |
| 18 | **Audit sécurité** | — |
| 19 | **Mode dev/prod** + minification | — |
| 20 | **Variables CSS / responsive** | — |

---

## 🏔️ Sprint 5 — Fonctionnalités majeures (Gap ACF)

> Gros chantiers. Tests écrits en parallèle du développement, pas après.

| # | Tâche | Complexité | Impact |
|---|-------|-----------|--------|
| 21 | **Conditional logic** | 🔴 Élevée (JS + PHP) | 🔴 Critique |
| 22 | **Flexible Content** | 🟠 Moyenne (variante Bundle) | 🔴 Critique |
| 23 | **Règles de localisation avancées** | 🟡 Moyenne | 🟡 Important |
| 24 | **Champ Relationship** | 🟠 Moyenne | 🟡 Important |
| 25 | **Champ Group** | 🟢 Faible | 🟡 Important |

---

## 📚 Documentation — en continu

- [ ] README = point d'entrée unique
- [ ] Version FR + EN
- [ ] Doc visible en back-office (lecture `.md`)
- [ ] Design + logo pour la mise en avant

---

## ⏳ Backlog / Plus tard

- Nettoyage automatique en base si un nom de champ est modifié
- `Select user` multi-rôles
- Champ `hidden` — documenter les cas d'usage
- Format de date par défaut (`m/d/Y` vs `d/m/Y`)
- Export JSON/PHP des définitions de champs
- Formulaires frontend


# CFDev — Code-First Custom Meta Fields for WordPress

Plugin WordPress pour déclarer des champs personnalisés (meta fields) entièrement par le code.

---

## CFDev vs ACF

### Ce que CFDev fait mieux

| Aspect | CFDev | ACF |
|--------|-------|-----|
| **API code-first** | PHP fluent, lisible, versionnable | `acf_add_local_field_group()` = tableau énorme et verbeux |
| **Validation serveur** | Système de Rules complet (Required, MinLength, Regex, ImageMinDimensions…) | Quasi inexistant — valide seulement `required` |
| **Cache intégré** | CacheManager/CacheStore avec invalidation automatique | Rien — `get_field()` tape la DB à chaque appel |
| **Poids** | Léger, zéro bloat | ACF Free = 3 Mo+, Pro = encore plus |
| **Déployable** | Code PHP = pas de migration de config en DB | Config stockée en DB → problème deploy dev→prod |

### Ce qu'ACF a que CFDev n'a pas encore

#### 🔴 Critique (manque vraiment)

1. **Conditional logic** — afficher/masquer un champ selon la valeur d'un autre. Feature la plus demandée dans tous les plugins de champs. Sans ça, le UX admin est limité.

2. **Options pages** — page de réglages globaux stockée dans `wp_options` (ex : infos du site, réseaux sociaux, header/footer global). Cas d'usage quotidien.

3. **Flexible Content** — comme le `bundle` mais chaque ligne peut être d'un *type différent* (ex : ligne "hero", ligne "texte+image", ligne "galerie"). C'est le fondement du page builder sans page builder.

4. **REST API** — exposer les champs résolus via `/wp-json`. Indispensable pour les headless / Next.js.

#### 🟡 Important

5. **Règles de localisation plus riches** — CFDev a `onlyForTemplate()` mais ACF permet : par rôle utilisateur, par auteur, par valeur de champ existant, par statut de post.

6. **Champ Relationship** — relation bidirectionnelle entre posts. ACF maintient le lien dans les deux sens.

7. **Champ Group** — conteneur nommé (comme une section accordion mais sans UI de collapse, juste pour grouper visuellement les champs).

#### ⚪ Mineur

8. Champs `password`, `oembed`, `button_group`, `page_link` — niche mais parfois nécessaires.
9. Export JSON/PHP des définitions — snapshot portable.
10. Formulaires frontend — rendre les champs hors admin.

### Verdict

CFDev est meilleur qu'ACF sur tout ce qu'il fait : validation, cache, lisibilité du code. Le problème c'est le périmètre fonctionnel — ACF couvre des cas d'usage que CFDev ignore encore.

### Priorités d'amélioration

1. **Conditional logic** (gros chantier JS + PHP)
2. **Options page** (rapide à implémenter, fort ROI)
3. **REST API endpoint** (le cache est déjà là — c'est 80% du travail fait)
4. **Flexible Content** (variante du Bundle avec type de ligne variable)

---

## Installation

```bash
composer require quidelantoine/cfdev
```

### Build production

```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

Ou dans `composer.json` :

```json
"config": {
    "optimize-autoloader": true,
    "classmap-authoritative": true
}
```

---

## Commandes de développement

```bash
# Qualité de code
vendor/bin/phpcs -s # vendor/bin/phpcbf
vendor/bin/phpstan analyse src tests
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Integration --bootstrap tests/Integration/bootstrap.php

npm run cy:run  
npx cypress open --browser chrome

# Tests fonctionnels E2E — Cypress (nécessite docker compose up -d)
npm install                                              # première fois seulement
npm run cy:open                                          # Test Runner interactif
npm run cy:run                                           # headless CI (Chrome)
npx cypress run --spec "cypress/e2e/02-flat-fields.cy.js" --browser chrome  # spec unique

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
```

```bash
# Claude Code
claude -p "Analyse @src/utils/validation.js"
cat src/auth.ts | claude -p "Explique cette fonction"
```

---

## Compatibilité

| | Minimum | Recommandé |
|---|---|---|
| **PHP** | 8.2 | 8.3+ |
| **WordPress** | 6.5 | 6.5+ |

Plancher PHP 8.2 : `readonly class` utilisé dans `FileMime` (`readonly` properties seules = 8.1).
Plancher WP technique : 5.3 (`wp_date()`). Minimum officiel fixé à 6.5 (avril 2024) pour cibler les installations actives.

---

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

### Mettre à jour les headers après un changement

Dans `cfdev-plugin.php` :

```php
/**
 * Requires PHP:      8.2
 * Requires at least: 6.5
 * Tested up to:      7.0
 */
```

Et vérifier que le runtime check est cohérent :

```php
if (version_compare(PHP_VERSION, '8.2', '<')) { ... }
```

---

## Recherche & remplacement (helpers)

```bash
# Rechercher
grep -rn "render_post_filter" .
grep -rn --exclude-dir=vendor "ancien_texte" .

# Remplacer
find . -type f -exec sed -i 's/ancien_texte/nouveau_texte/g' {} +
```

---

## Annotations PHPDoc

```php
@package    // Namespace principal
@subpackage // Sous-namespace
@author     // Nom <email>
@since      // Version d'introduction
@version    // Version actuelle
@param      // Paramètre de méthode
@return     // Valeur de retour
@throws     // Exception levée
@deprecated // Méthode obsolète + alternative
@see        // Référence vers une autre classe/méthode
@link       // URL de documentation externe
@todo       // Ce qui reste à faire
```

---

## Documentation

- Doc complète : *(lien à ajouter)*
- Disponible en FR et EN

###################################################################################


# Pour CFDev : "CFDev – Code-First Custom Meta Fields For Wordpress" Custom Meta For Dev
amelioration du code ( interafce , duplication content), ok pour l'architecture du pluging ?
# En cours

# installation

```bash
composer require quidelantoine/cfdev # c'est le projet
```

# test
```bash
vendor/bin/phpcs -s
vendor/bin/phpstan analyse
vendor/bin/phpunit

# ??? 
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Integration --bootstrap tests/Integration/bootstrap.php
```

# A faire






# A voir plus tard

- admin, effacer les données des tables , si un nom de champ a etais modifié, comparaison declaraison et ce qu'il y a dans la table eteffecer ce qui n'est pas bon

# autres plugins idee


https://themepure.net/plugins/puremetafields/docs/switch/

# hemper

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


@package    // Namespace ou package principal
@subpackage // Sous-namespace
@author     // Nom <email>
@since      // Version d'introduction
@version    // Version actuelle (surtout pour les classes)
@param      // Paramètre de méthode
@return     // Valeur de retour
@throws     // Exception levée
@deprecated // Méthode obsolète, indiquer l'alternative
@see        // Référence vers une autre classe/méthode
@link       // URL de documentation externe
@todo       // Ce qui reste à faire


# Test unitaire

./vendor/bin/phpunit
./vendor/bin/phpunit --display-deprecations

# PHPCS
vendor/bin/phpcs -i
vendor/bin/phpcs -s
vendor/bin/phpcbf

# PHPstan
vendor/bin/phpstan analyse src tests

=> treste test Bundle , accordeon, tab tabs

## Gestion de composer pour la prod
-> mettre le dossier vendor dans le pluging en prod
lancer ceci ```composer install --no-dev --optimize-autoloader --classmap-authoritative```

ou

Mettre dans le fichier composer.json

```bash
"config": {
    "optimize-autoloader": true,
    "classmap-authoritative": true
}
``
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

# CFDev vs ACF — Comparaison

## Ce que CFDev fait mieux qu'ACF

| Aspect | CFDev | ACF |
|--------|-------|-----|
| **API code-first** | PHP fluent, lisible, versionnable | `acf_add_local_field_group()` = tableau énorme et verbeux |
| **Validation serveur** | Système de Rules complet (Required, MinLength, Regex, ImageMinDimensions…) | Quasi inexistant — valide seulement `required` |
| **Cache intégré** | CacheManager/CacheStore avec invalidation automatique | Rien — `get_field()` tape la DB à chaque appel |
| **Poids** | Léger, zéro bloat | ACF Free = 3 Mo+, Pro = encore plus |
| **Déployable** | Code PHP = pas de migration de config en DB | Config stockée en DB → problème deploy dev→prod |

---

## Ce qu'ACF a que CFDev n'a pas

### Critique (manque vraiment)

1. **Conditional logic** — afficher/masquer un champ selon la valeur d'un autre. C'est la feature la plus demandée dans tous les plugins de champs. Sans ça, le UX admin est limité.

2. **Options pages** — une page de réglages globaux stockée dans `wp_options` (ex : infos du site, réseaux sociaux, header/footer global). Cas d'usage quotidien.

3. **Flexible Content** — comme le `bundle` mais chaque ligne peut être d'un *type différent* (ex : ligne "hero", ligne "texte+image", ligne "galerie"). C'est le fondement du page builder sans page builder.

4. **REST API** — exposer les champs résolus via `/wp-json`. Indispensable pour les headless / Next.js.

### Important

5. **Règles de localisation plus riches** — CFDev a `onlyForTemplate()` mais ACF permet : par rôle utilisateur, par auteur, par valeur de champ existant, par statut de post.

6. **Champ Relationship** — relation bidirectionnelle entre posts. ACF maintient le lien dans les deux sens.

7. **Champ Group** — conteneur nommé (comme une section accordion mais sans UI de collapse, juste pour grouper visuellement les champs).

### Mineur

8. Champs `password`, `oembed`, `button_group`, `page_link` — niche mais parfois nécessaires.
9. Export JSON/PHP des définitions — snapshot portable.
10. Formulaires frontend — rendre les champs hors admin.

---

## Verdict

CFDev est meilleur qu'ACF sur tout ce qu'il fait : la validation, le cache, la lisibilité du code. Le problème c'est le périmètre fonctionnel — ACF couvre des cas d'usage que CFDev ignore encore.

## Priorités d'amélioration

1. **Conditional logic** (gros chantier JS + PHP)
2. **Options page** (rapide à implémenter, fort ROI)
3. **REST API endpoint** (le cache est déjà là — c'est 80% du travail fait)
4. **Flexible Content** (variante du Bundle avec type de ligne variable)

###################################


est ce que mettre la doc dans l'admin du plugin est une bonne idées, possible que cela multilangue et basé sur les fichiers md du dossier docs ?

● Bonne idée, oui — pour un plugin code-first destiné aux devs, avoir la doc accessible dans l'admin évite les allers-retours GitHub. Voici l'analyse honnête :
                                                                                                                                                                                                         
---
Points forts

- Contextuelle — le dev est déjà dans l'admin quand il a une question
- Synchronisée avec le code — les .md sont dans le repo, la doc ne peut pas être en retard sur le code
- Zéro infra — pas de site de doc externe à maintenir
- GetDataPage existe déjà dans ton admin — tu as déjà l'ossature de pages d'admin

Points faibles / risques

- Multilangue sur des .md = complexité non négligeable — il faudrait soit des fichiers docs/fr/, docs/en/, soit un parser de frontmatter avec clé de langue. Pour l'instant tu n'as qu'une langue, c'est du sur-engineering
  précoce.
- Rendu Markdown — WordPress n'a pas de parser MD natif. Tu devrais embarquer une lib PHP (league/commonmark ou parsedown) ou parser côté JS. Ça ajoute une dépendance.
- Indexation / recherche — les .md statiques ne sont pas cherchables facilement dans l'admin.

  ---
Ma recommandation

Oui, mais simplement. Une page admin "Documentation" qui :
1. Liste les fichiers .md du dossier docs/
2. Les rend en HTML via league/commonmark (déjà dans beaucoup de projets PHP)
3. Avec un menu de navigation latéral généré depuis les noms de fichiers

Pas de multilangue pour l'instant — attends d'avoir un vrai besoin. Si tu veux l'ajouter plus tard, la structure docs/fr/*.md / docs/en/*.md avec détection de get_locale() s'ajoutera proprement.