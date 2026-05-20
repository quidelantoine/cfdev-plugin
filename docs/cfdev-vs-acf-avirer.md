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