# Priorisation des tâches — CFDev

> Synthèse actionnable de [AFAIRE.md](./AFAIRE.md).
> Logique : débloquer d'abord ce qui bloque le reste, puis maximiser le ROI.

---

test fonctionelle  CYPRESS 

Mettre des icone au debut des champs pour aider à l'ui/ux

=> duplicate code ????


=> ask php et wp version limit
=> gerer version wordpress aussi  +++ 
=> Test woocommerce isOK ??
## 🧪 Règle d'or — Les tests sont une priorité permanente

> La base de tests est déjà solide : +1200 assertions existantes.
> **Chaque nouvelle fonctionnalité = tests écrits en même temps**, pas après.
> Exception : tests Admin HTML — à faire en dernier car liés au HTML qui change souvent.

**Gaps à combler en continu :**
- [ ] Compléter les tests unitaires existants (gaps identifiés)
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
| 9 | **Compatibilité PHP/WP** — définir + documenter | — |

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
