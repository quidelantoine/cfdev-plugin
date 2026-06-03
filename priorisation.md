# Priorisation des tâches — CFDev
---

# Plus tard mais important
Ajouter un numero dans un ?bundle pour connaitre le nombre d'element ddedans
==========
=> Utiliser plugins traduction loco translate pour générer fichier .mo et .po
allemand, espagnol, chinois
=> ajouter à la docs
| 15 | **i18n** — `__('')` en anglais + `.mo`/`.po` (FR, DE, ES, ZH) | — |

=> doc dans toutes les langues ?? 
===============

=> dans cfdev-plgins ajouter une description ++ et autre si possible ??

- Export JSON/PHP des définitions de champs

# Parcours complet WP
dev  →  git tag v1.0.0  →  GitHub Actions  →  Release ZIP
→  WordPress.org SVN (quand prêt)
Étape 2 — WordPress.org (plus tard)
Même workflow, une job supplémentaire qui push le zip vers SVN via 10up/action-wordpress-plugin-deploy.
Prérequis WP.org :
- readme.txt au format WP.org (différent du readme.md)
- Screenshots dans assets/ du SVN
- Review manuelle ~2 semaines
  Le workflow WP.org peut être ajouté plus tard quand tu es prêt à soumettre.
  trouver un logo ++ perso ????

## Améliorations Futurs
1. **Conditional logic** (gros chantier JS + PHP) **Conditional logic** — afficher/masquer un champ selon la valeur d'un autre. C'est la feature la plus demandée dans tous les plugins de champs. Sans ça, le UX admin est limité.
5. **Règles de localisation plus riches** — CFDev a `onlyForTemplate()` mais ACF permet : par rôle utilisateur, par auteur, par valeur de champ existant, par statut de post.
8. Champs `password`, `oembed`, `button_group`, `page_link` — niche mais parfois nécessaires.
9. Export JSON/PHP des définitions — snapshot portable.
10. Formulaires frontend — rendre les champs hors admin.
# CI reste SonarQube
# test de release 1.0.6 ?
# A REFAIRE
=> duplicate code ????, interface ajouter ? architecture is ok ??? A refaire +++
=> Tous les champs sont tester , unitaire, integration et fonctionnel ??? a reposer encore, meme sur partie non field admin par exemple !!!
=> Audit sécurité