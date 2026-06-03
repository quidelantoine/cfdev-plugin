# Priorisation des tâches — CFDev
---
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


## Améliorations Futurs & Backlog
1. **Conditional logic** (gros chantier JS + PHP) **Conditional logic** — afficher/masquer un champ selon la valeur d'un autre. C'est la feature la plus demandée dans tous les plugins de champs. Sans ça, le UX admin est limité.
2. Champs `password`, `oembed`, `button_group`, `page_link` — niche mais parfois nécessaires.
3. Ajouter un numero dans un ?bundle pour connaitre le nombre d'element ddedans
# CI reste SonarQube
# test de release 1.0.8 sur nouvelle installation ++ et tester le pluging +++ 
# A REFAIRE
=> Mettre a jour valeur des badges
=> Docs en adéquations avec le code ??
=> duplicate code ????, interface ajouter ? architecture is ok ??? A refaire +++
=> Tous les champs sont tester , unitaire, integration et fonctionnel ??? a reposer encore, meme sur partie non field admin par exemple !!!
=> Audit sécurité
