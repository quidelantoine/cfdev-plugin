# Pour CFDev : "CFDev – Code-First Custom Meta Fields For Wordpress" Custom Meta For Dev

# En cours

=> et le js dans le fichier function.js ?? c'est pas mieux
=> lancer les tests avant commit 
- => commit 

=> creation de l'api 
=> POur api, donner accés au element que si il on la metabox d'aatcher dans le registry
=> erire les test unitaire directe
=> ecrire la doc ,
# installation 

```bash
composer require quidelantoine/cfdev # c'est le projet
```

# test 
```bash
vendor/bin/phpcs -s
vendor/bin/phpstan analyse
vendor/bin/phpunit
```

# A faire 
tou sle stexte dans des __(''); devrais etre en anglais dans le code , je ferais un mo po pour les frenchy ensuite

=> Js ne pas utiliser jquery est ce une bonn eidée , sachant que cela marche bien

=> FAire une API aussi pour recuperer les donées , l'activer ou non dans l'administration
=> Pour les headless+++ a ajouter a la doc et à l'overview 

=> dans l'admin partie champ, il faudrais pouvoir afficher le resulats d'un print_r des données de ce metabox par exemple , ceci à la demande dans une modale, evite de le faire dans le front 
=> ok et les data du print r en mode profiler de symfony, avec onglet s'ouvre qui intelegement pour une lecture facilité. peux s'appuyer sur le cache aussi ++

=> revoir les champs DEMO , pour etre sur que je teste tous dans tous les sens +++
-> A terminer +++ 

=> j'ai fais les champs , et le reste (validation, sauvegarde) , repeatable , ajax, 
    -> il faut faire une partie object , via des hooks ou non , pour init-cuztom
        ->ajouter des groupes de champs, via des hooks , pourquoi pour pouvoir avoir acces a toutes les declaration, pour ensuite dans l'admin, donner accés à ces donéées, faire des stas et verifier que tous les champs n'ont pas le meme nomo (id) et pour le kiff d'avoir une vue d'ensemble ( un resume de tous les champs ajouter via le code afficher dans l'admin du plugins)
=> peut etre faire l'admin avant pour une meilleur integration ensuite


=> faire un test avec les champs de type repetable

=> tous les champs n'ont pas la meme largeur (depends de post, term ou user , accordeon, tabs ??? , faire un retour pour qu'il corrrige)

=> Mettre en place ci , phpcs , linter etc , phpstan , eslint ,
-> reste eslint ??? et ci 

=> change color of metabox , custom design metaBox +++


=> je garde le champ hidden , a quoi il peut servir (cas) , pour garder la données, pas vraiment car la donées est toujours 


Gaps dans les tests unitaires existants :, refaire une demande pour completer ++

=> Utiliser plugins traduction loco translate pour générer fichier .mo et .po
    allemand, espagnol, chinois 

Faire une page admin cfdev
    Sous forme de tabs. qui ressemble à ACF ???? 
- cache -> vider le cache 
- liste des groupes de champs
       lecture des hooks ???
- réglages
- -> truc pour verifier que tous les id sont unique , éviter les doublons ++
- _text_'.$str.'_main_image'

=> minifier le code ,
    => Faire une version prod 
        = Ajouter dans la config ??? 'mode'  dev/prod

=> Select user => comment prendre que certain role ???, possible d'en prendre plusieurs roles  ??

=> ecrire des tests pour la partie admin 
=> ecrire aussi la docs 

=> variable css sur style.css => comment améliiorer le css, est ok pour le responsive ?? 
est utile d'utiliser vite.js pour gerer le css et js le prefixe css , le js valide partout , , on garde jquery ? 

=> reverifier m/d/Y =>  'args' => ['date_format' => 'm/d/Y']]),  ou d/m/Y, mieux de rien mettre ???

// lancer un truc security vulnerabilté via ia , faire la formation ia dyma

=> revoir /home/quidel/Sites/2026/test5_frankenphp/app/wp-content/plugins/cfdev-plugin/src/demo/helpers.php 
car dans la function qui se trouve dans le theme il y avais des trucs bien +++


## Rediger la docs
=> revoir la doc dans son ensemble, le readme de base devra etre le point d'entree de toutes la doc avec installation
=> lui demander de faire un design avec logo pour mise en avant du plugin
=> Voir la docs dans le back-office, grace au fichier md
=> faire une doc en francais et une en anglais, comment bien gerer ceci
=> comment gerer cela avec md dans depot et aussi avec le md sur l'admin du site faire une partie documentation
## Test 
=> Faire un test avec les champ repetable sur tous pour faire le tests
=> test bundle dans term et user ????  et accordeon et tabs ???? et validation
=> FAire tests de tous les champs dans un bundle , dans un accordeaon , dans tabs ??
=> meme choses sur meta dans term et user ++++
custom-meta.dev
=> vérifier si il marche , tous , tester les fiedls pas dans la doc ??
=> Voir si marche aussi dans bundle tabs , et accordeon
=> dans term et dans user ++
=> tester si cela marche si j'ajoute champ à woocommerce ??

X=> Faire tests unitaire pour Admin à la fin quand terminé, car test sur html sinon on va devoir changer souvent


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
