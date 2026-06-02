# Priorisation des tâches — CFDev
---
Parcours complet

dev  →  git tag v1.0.0  →  GitHub Actions  →  Release ZIP
→  WordPress.org SVN (quand prêt)

Étape 2 — WordPress.org (plus tard)
Même workflow, une job supplémentaire qui push le zip vers SVN via 10up/action-wordpress-plugin-deploy.
Prérequis WP.org :
- readme.txt au format WP.org (différent du readme.md)
- Screenshots dans assets/ du SVN
- Review manuelle ~2 semaines
Le workflow WP.org peut être ajouté plus tard quand tu es prêt à soumettre.

## JS & npm ?
Mieux erire le js faire une passe dessus ?
=> Js ne pas utiliser jquery est ce une bonn eidée , sachant que cela marche bien
js full vanilla ??, utilisation de vite.js,  et js polyfills

# design de la page admin
trouver un logo ++ perso ????

# Plus tard mais imporant
Ajouter un numero dans un ?bundle pour connaitre le nombre d'element ddedans

reverifier m/d/Y =>  'args' => ['date_format' => 'm/d/Y']]),  ou d/m/Y, mieux de rien mettre ???

# Traduction
=> Utiliser plugins traduction loco translate pour générer fichier .mo et .po
allemand, espagnol, chinois

## Test

| 3 | revoir les endpoind sur admin apirest ?? sont t'il vraiment fonctionel, est ce que c'est testé ?
=> revoir admin api, des trucs etrange sur les liens proposer pour acceder à l'api A VERIFIER ++ et TESTER

| ↳ | **Tests Admin HTML** — une fois l'UI figée | Tests rendu, structure HTML | il manque quoi à tester sur cette partie ?

TEsts sur les champs => couverture complete ??? 

| 15 | **i18n** — `__('')` en anglais + `.mo`/`.po` (FR, DE, ES, ZH) | — |
| 18 | **Audit sécurité** | — |
- admin, effacer les données des tables , si un nom de champ a etais modifié, comparaison declaraison et ce qu'il y a dans la table eteffecer ce qui n'est pas bon

=> covergae des tests est ok ?  Pourcentage ??? afficher sur le read_me ??? 

- Nettoyage automatique en base si un nom de champ est modifié
- Format de date par défaut (`m/d/Y` vs `d/m/Y`)
- Export JSON/PHP des définitions de champs

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
