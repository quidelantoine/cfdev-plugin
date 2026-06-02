# Priorisation des tâches — CFDev
---

ok => Dans admin apirest CFDev endpoint la coloone est vide , comment faire ? , faire un endpoint aussi pour recuperer les donées formater comme les url des images au lieu de l'id de l'image
ok => revoir les endpoind sur admin apirest ??

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



###



## JS & npm ?
Mieux erire le js faire une passe dessus ?
=> Js ne pas utiliser jquery est ce une bonn eidée , sachant que cela marche bien
js full vanilla ??, utilisation de vite.js,  et js polyfills



# design de la page admin
trouver un logo ++ perso ????



# Plus tard mais imporant
Ajouter un numero dans un ?bundle pour connaitre le nombre d'element ddedans

=> revoir admin api, des trucs etrange sur les liens proposer ++
reverifier m/d/Y =>  'args' => ['date_format' => 'm/d/Y']]),  ou d/m/Y, mieux de rien mettre ???



# Traduction
=> Utiliser plugins traduction loco translate pour générer fichier .mo et .po
allemand, espagnol, chinois
## Test
#### Repeatable field ?? 
=> Faire un test avec les champ repetable sur tous pour faire le tests
X=> Faire tests unitaire pour Admin à la fin quand terminé, car test sur html sinon on va devoir changer souvent
=> faire un test avec les champs de type repetable
Tests Admin HTML ← en dernier uniquement
  => demander si tout est bien teter aussi sur cette partie 
## Voir apres les tester ??

les propriete de field repeatable et ajax, test ok ? est ce que je garde ???
Si on garde c'est quoi les limtes etest ce que cela vaut vraiment le coup ???

- admin, effacer les données des tables , si un nom de champ a etais modifié, comparaison declaraison et ce qu'il y a dans la table eteffecer ce qui n'est pas bon

## 🏁 Finir ce qui est commencé
| 3 | **Repeatable** — test complet fonctionnel | Tests repeatable tous types |
=> Changer badge
=> push

proprieté ajax dans fields , est bien tester ? Pas possible de donée un autre nom à la propriété ? save ? save_unique ? plus clair ? non ?

| ↳ | **Tests Admin HTML** — une fois l'UI figée | Tests rendu, structure HTML |

| 15 | **i18n** — `__('')` en anglais + `.mo`/`.po` (FR, DE, ES, ZH) | — |
| 18 | **Audit sécurité** | — |
---

## ⏳ Backlog / Plus tard

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

# test de relase 1.0.6 ?

# A REFAIRE
=> duplicate code ????, interface ajouter ? architecture is ok ??? A refaire +++
=> Tous les champs sont tester , unitaire, integration et fonctionnel ??? a reposer encore, meme sur partie non field admin par exemple !!!
###################################

# A virer 
┌────────────┬──────┬─────────────┬─────────┐
│   Champ    │ Unit │ Integration │ Cypress │
├────────────┼──────┼─────────────┼─────────┤
│ Text       │  ✓   │      ✓      │    ✓    │
├────────────┼──────┼─────────────┼─────────┤
│ Textarea   │  ✓   │      ✓      │    ✓    │                                                                                                                                                             
├────────────┼──────┼─────────────┼─────────┤
│ Number     │  ✓   │      ✓      │    ✓    │
├────────────┼──────┼─────────────┼─────────┤
│ Email      │  ✓   │      ✓      │    ✓    │
├────────────┼──────┼─────────────┼─────────┤
│ Url        │  ✓   │      ✓      │    ✓    │                                                                                                                                                             
├────────────┼──────┼─────────────┼─────────┤
│ Toggle     │  ✓   │      ✓      │    ✓    │
├────────────┼──────┼─────────────┼─────────┤
│ Checkbox   │  ✓   │      ✓      │    ✓    │
├────────────┼──────┼─────────────┼─────────┤
│ Checkboxes │  ✓   │      ✓      │    ✓    │                                                                                                                                                             
├────────────┼──────┼─────────────┼─────────┤
│ Select     │  ✓   │      ✓      │    ✓    │
├────────────┼──────┼─────────────┼─────────┤
│ Bundle     │  ✓   │      ✓      │    ✓    │
├─────────────┼──────┼─────────────┼─────────┤
│ Tabs        │  ✓   │      ✓      │    ✓    │                                                                                                                                                            
├─────────────┼──────┼─────────────┼─────────┤
│ Accordion   │  ✓   │      ✓      │    ✓    │
├─────────────┼──────┼─────────────┼─────────┤
│ Range       │  ✓   │      —      │    ✓    │
├──────────────────┼──────┼─────────────┼─────────┤
│ Tel              │  ✓   │      —      │    ✓    │                                                                                                                                                       
├──────────────────┼──────┼─────────────┼─────────┤
│ Color            │  ✓   │      —      │    ✓    │
├──────────────────┼──────┼─────────────┼─────────┤
│ Date             │  ✓   │      —      │    ✓    │
├──────────────────┼──────┼─────────────┼─────────┤
│ Radios           │  ✓   │      —      │    ✓    │                                                                                                                                                       
├──────────────────┼──────┼─────────────┼─────────┤
│ Yesno            │  ✓   │      —      │    ✓    │
├──────────────────┼──────┼─────────────┼─────────┤
│ Wysiwyg          │  ✓   │      ✓      │    —    │
├──────────────────┼──────┼─────────────┼─────────┤
│ Datetime         │  ✓   │      —      │    —    │
├──────────────────┼──────┼─────────────┼─────────┤
│ Time             │  ✓   │      —      │    —    │
├──────────────────┼──────┼─────────────┼─────────┤
│ MultiSelect      │  ✓   │      —      │    —    │                                                                                                                                                       
├──────────────────┼──────┼─────────────┼─────────┤
│ Image / ImageAlt │  ✓   │      —      │    —    │
├──────────────────┼──────┼─────────────┼─────────┤
│ File             │  ✓   │      —      │    —    │
├──────────────────┼──────┼─────────────┼─────────┤
│ Gallery          │  ✓   │      —      │    —    │                                                                                                                                                       
├──────────────────┼──────┼─────────────┼─────────┤
│ Link             │  ✓   │      —      │    —    │
├──────────────────┼──────┼─────────────┼─────────┤
│ PostSelect       │  ✓   │      —      │    —    │
├──────────────────┼──────┼─────────────┼─────────┤
│ TermSelect       │  ✓   │      —      │    —    │
├──────────────────┼──────┼─────────────┼─────────┤
│ UserSelect       │  ✓   │      —      │    —    │
├──────────────────┼──────┼─────────────┼─────────┤
│ PostCheckboxes   │  ✓   │      —      │    —    │                                                                                                                                                       
├──────────────────┼──────┼─────────────┼─────────┤
│ TermCheckboxes   │  ✓   │      —      │    —    │
├──────────────────┼──────┼─────────────┼─────────┤
│ UserCheckboxes   │  ✓   │      —      │    —    │
├──────────────────┼──────┼─────────────┼─────────┤
│ Heading / Hidden │  ✓   │      —      │    —    │                                                                                                                                                       
└──────────────────┴──────┴─────────────┴─────────┘

  ---
Bilan :

- 12 champs : couverture complète Unit + Integration + E2E
- 6 champs : Unit + E2E, pas d'intégration (Range, Tel, Color, Date, Radios, Yesno) — leur save/restore est validé via Cypress, l'intégration DB reste la lacune
- 1 champ : Unit + Integration uniquement (Wysiwyg — le picker média est difficile à automatiser)
- 14 champs : Unit uniquement — principalement les médias (Image, File, Gallery), les relations (PostSelect, TermSelect…, Checkboxes de relation), Datetime, Time, MultiSelect

Les vrais trous :
1. Médias — pas testables en Cypress sans le media picker WP (explicitement skippés dans les specs)
2. Relations (PostSelect/TermSelect/UserSelect et leurs variantes Checkboxes) — testables en intégration et Cypress, non encore couverts
3. Datetime / Time / MultiSelect — oubliés des specs Cypress malgré leur présence dans les démos
