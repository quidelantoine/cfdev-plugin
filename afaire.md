# AFAIRE — CFDev

---

## 🔴 En cours / Prioritaire

- [ ] **API** — Refaire une vérification, écrire les tests unitaires pour la partie API
- [ ] **Inspecter les groupes de champs** — Modale avec code + données (`foreach`, `esc_html`…), présentation standard par champ
- [ ] **Champs DEMO** — Revoir et tester dans tous les sens (à terminer ++)
- [ ] **Repeatable** — Test complet avec les champs de type repeatable

---

## 🚧 Gap ACF — Fonctionnalités manquantes

> Classées par ROI. Détail dans [README.md](./README.md#ce-quacf-a-que-cfdev-na-pas-encore).

- [ ] **REST API endpoint** — Le cache est déjà là, c'est 80% du travail fait. Indispensable headless
- [ ] **Options page** — Rapide à implémenter, fort ROI. Page de réglages globaux dans `wp_options`
- [ ] **Conditional logic** — Gros chantier JS + PHP. Afficher/masquer un champ selon un autre
- [ ] **Flexible Content** — Variante du Bundle avec type de ligne variable (hero, texte+image, galerie…)
- [ ] **Règles de localisation avancées** — Par rôle, par auteur, par valeur de champ, par statut
- [ ] **Champ Relationship** — Relation bidirectionnelle entre posts
- [ ] **Champ Group** — Conteneur nommé pour grouper visuellement des champs (sans collapse UI)

---

## 🟡 Fonctionnalités

### Interface & UX
- [ ] Menu admin CFDev plus bas dans la sélection
- [ ] Admin CFDev visible par les **administrateurs uniquement**
- [ ] Harmoniser la largeur des champs (post / term / user, accordéon, tabs)
- [ ] Changer la couleur / design des metaboxes +++
- [ ] Revoir `/src/demo/helpers.php` (récupérer les bonnes pratiques du thème)

### API REST
- [ ] Activable/désactivable depuis l'admin — voir aussi [🚧 Gap ACF](#-gap-acf--fonctionnalit%C3%A9s-manquantes) pour les détails d'implémentation
- [ ] Documenter pour les usages **headless** (ajouter à la doc et à l'overview)

### Page admin CFDev (tabs, style ACF)
- [ ] Onglet **Cache** — vider le cache
- [ ] Onglet **Groupes de champs** — liste + lecture des hooks
- [ ] Onglet **Réglages**
- [ ] Vérification des **IDs uniques** (éviter les doublons de noms de champs)
- [ ] Profiler à la Symfony : modale avec onglets, support cache
- [ ] Hooks pour init-custom : groupes de champs + vue d'ensemble dans l'admin

### JavaScript & Build
- [ ] Passer en **JS vanilla** (supprimer jQuery) — confirmer si c'est une bonne idée
- [ ] Évaluer **Vite.js** : préfixage CSS, polyfills JS
- [ ] Minifier le code — ajouter un mode `dev` / `prod` dans la config

### Champs
- [ ] Champ `hidden` — documenter les cas d'usage réels
- [ ] `Select user` — filtrer par rôle(s), sélection multi-rôles possible ?
- [ ] Vérifier le format de date : `m/d/Y` vs `d/m/Y` vs rien

### i18n & Traduction
- [ ] Tous les textes `__('')` doivent être en **anglais** dans le code
- [ ] Générer les fichiers `.mo` / `.po` avec **Loco Translate** (FR, DE, ES, ZH)

### CI / Qualité
- [ ] Mettre en place CI complet : PHPCS, PHPStan, ESLint
- [ ] Reste : **ESLint** + pipeline CI
- [ ] Lancer un audit de sécurité (vulnérabilités)
- [ ] Variables CSS dans `style.css` — améliorer / vérifier le responsive

### Compatibilité
- [ ] Définir les versions **PHP** et **WordPress** minimales supportées
- [ ] Les documenter dans le README et dans `composer.json`

---

## 🟢 Tests

- [ ] Tests unitaires **Admin** (à faire en dernier, liés au HTML)
- [ ] Compléter les gaps dans les tests unitaires existants
- [ ] Tester les champs repeatable sur tous les types
- [ ] Tester bundle dans **term** et **user**
- [ ] Tester **accordéon** et **tabs** dans term, user, bundle
- [ ] Tester la validation dans tous les contextes
- [ ] Vérifier si les champs fonctionnent avec **WooCommerce**
- [ ] Tester sur `custom-meta.dev` — tous les champs, y compris ceux absents de la doc

---

## 📚 Documentation

- [ ] Revoir la doc dans son ensemble
- [ ] `README.md` = point d'entrée unique (installation, liens)
- [ ] Version **FR** et **EN** (gérer la dualité MD dépôt / admin)
- [ ] Afficher la doc en back-office (lecture des fichiers `.md`)
- [ ] Créer un design avec logo pour la mise en avant du plugin

---

## ⏳ À voir plus tard

- Nettoyage des données en base si un nom de champ est modifié (comparaison déclaration ↔ table, suppression des entrées orphelines)

---

## 💡 Idées & Références

- Inspiration UI : [PureMetaFields - Switch](https://themepure.net/plugins/puremetafields/docs/switch/)