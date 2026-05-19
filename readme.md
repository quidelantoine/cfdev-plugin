# Pour CFDev : "CFDev – Code-First Custom Meta Fields For Wordpress" Custom Meta For Dev


# installation 

```bash
composer require quidelantoine/cfdev
```
# A faire 

=> tou sles chaps n'ont pas la meme largeur (depends de post, term ou user , accordeon, tabs ??? , faire un retour pour qu'il corrrige)
=> Mettre en place ci , phpcs , linter etc , phpstan , eslint ,
-> reste eslint ??? et ci 

=> change color of metabox , custom design metaBox +++

Avant de passer aux autres types, combler les gaps unitaires les plus utiles — notamment tester validateFields() et le flux ErrorBag. C'est de la pure logique PHP, rapide à écrire, et c'est ce qui protège le mieux contre
les régressions au quotidien. Les tests d'intégration viendront quand le plugin sera plus mature.

Gaps dans les tests unitaires existants :
- Meta::validateFields() + ErrorBag → le flux complet validation → erreur → affichage
- Bundle::save() / Bundle::renderField() avec de vraies données
- MetaBox::savePost(), TermMeta::saveTerm() — la boucle sauvegarde complète

=> Faire plugins traduction loco translate pour générer fichier .mo et .po
    allemand, espagnol, chinois 

Faire une page admin cfdev
    Sous forme de tabs.
- cache -> vider le cache 
- liste des groupes de champs
       lecture des hooks ???
- réglages
- -> truc pour verifier que tous les id sont unique , éviter les doublons ++
- _text_'.$str.'_main_image'

=> minifier le code ,
    => Faire une version prod 
        = Ajouter dans la config ??? 'mode'  dev/prod
=> Select user => comment prendre que certain role ???

// FAire une function AddMetaBoxAccordeon, AddMetaBoxTabs, AddMetaBoxBundle
//=> FAire un truc plus orienté objects ??
    // ->addField(), cx'est bien cela +++

## Rediger la docs

=> faire des docs pour les champs +++

## Test 

=> test bundle dans term et user ????  et accordeon et tabs ???? et validation
=> FAire tests de tous les champs dans un bundle , dans un accordeaon , dans tabs ??
=> meme choses sur meta dans term et user ++++
custom-meta.dev
=> vérifier si il marche , tous , tester les fiedls pas dans la doc ??
=> Voir si marche aussi dans bundle tabs , et accordeon
=> dans term et dans user ++
=> tester si cela marche si j'ajoute champ à woocommerce ??
# Faire une partie pour remplacer custom init via des hooks ? ou autres 
=> Faire un fichiers init-custom.php de base dans le plugins, mais avoir la possiblité de ecraser, (like woocommerce , ou theme enfant )
=>
// FAire une function AddMetaBoxAccordeon, AddMetaBoxTabs, AddMetaBoxBundle

# Partie recuperation de donées 
Creer un systeme de cache pour récupérer les donnees 

=> Pour les files
=> la donner stocker et le chemin du fichier mais si on est en local , cela garde localhost, et donc quand on passe en prod sur un serveur il faut refaire une passe sur les champ file pour mettre à jour +++


=> Faire des function pour recuperer les données => retrieve data
=> ajouter function plus cool plus les bundles , etc ... et les select serialize
=> serialize to json ?? posiible en base ded onées , cela donne quoi reelmment ??? => Cela Implique beaucoup de choses +++

=> attention les donées sont en json maintenant, // je crois que cela deconne a caudse de cela +++

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
