# HELLOASSOPAY FOR [DOLIBARR ERP CRM] - Version 1.0.3 -

# Installation

- Copier le contenu du répertoire dans le répertoire Dolibarr suivant : DolibarrRoot/custom/helloassopay
- activer et Configurer le module dans la gestion de la configuration de Dolibarr

## configuration des paramètres du module

- Url d'accès à HelloAsso : fourni par HelloAsso</br>;
- Code client configuré dans la page API de l'admin HelloAsso : fourni par HelloAsso</br>;
- Code secret configuré dans la page API de l'admin HelloAsso : fourni par HelloAsso</br>;
- Nom générique de l'association dans l'admin HelloAsso : fourni par HelloAsso</br>;
- Id (rowid) du mode de paiement Dolibarr de type Carte HelloAsso</br>;
- Id de la banque Dolibarr à utiliser pour l'enregistrement du paiement </br>;
- Url de la page affichée après paiement : After de la page backupurl.php, à afficher après le message de succès/refus du paiement
- Code API Dolibarr de l'utilisateur qui sera identifié comme créateur du paiement
- Désignation présente dans le fichier des paiements HelloAsso

## configuration des messages

Cela se fait dans le fichier lang du module : helloassopay\langs\fr_FR

# Documentation

## Principe général de fonctionnement

Le principe de fonctionnement est le suivant :

1) l'utilisateur effectue un appel à l'url de début : dolibarr_root/helloassopay/public/start.php?ref=xxxx&tracemode=yyyyyy.

Les paramètres GET associés à la page start.php sont :

- ref=xxxxx : id de la facture à payer (paramètre obligatoire)
-tracemode=true : demande au module d'enregistrer toutes les informations dans le fichier return_helloasso.log (paaramètre facultatif).

- start.php récupère les informations de la facture (Pour le montant, il détermine le reste à payer pour cette facture) puis effectue un appel à helloasso (nommé checkout), qui lui renvoie un lien de paiement, l'utilisateur est ensuite redirigé vers ce lien.
Le lien de paiement mène à une page de paiement Helloasso dans laquelle, sont automatiquement renseignés : les noms, prénoms, email, le description et le montant du paiement.

2) Sur le site HElloasso, l'utilisateur procède au paiement, en saisissant sa carte.

3) Lorque l'utilisateur a terminé son paiement, il est redirigé par Helloasso vers la page /public/backurl.php. Les paramètres GET d'appel de cette page permettent de choisir le message a afficher (le paiement est accepté, le paiement est refusé).

4) En parrallele, Helloasso renvoie toutes les informations (fonction IPN) concernant le paiement en appelant la page /public/ipnreturn.php (L'url de cette page est configurable sur le site helloasso). Cette page crée le paiement dans la base de données Dolibarr.

## Logs

- ipn_return.log : contient toutes les réponses IPN de helloqsso pour tous les paiements.
- return_helloasso.log : contient
  1) les erreurs rencontrées lors du fonctionnement du module
  2) les traces demandées dans la page start.php (tracemode=true). Les traces contiennent : le token retourné par HElloasso, la chaine de checkout envoyéee à Helloasso, l'url de paiement retournée par helloasso.

## Note

- La version 1 ne permet de gérer qu'un paiement par transaction (pas de paiement multi-facture).

# Release notes

## A faire

## v1.0.3

- NEW : Ajouté fonction de récupération des paiements de Hello et création d'une table dans la base de données Dolibarr
- FIX : Correction bug d'affichage d'erreur d'identification à HelloAsso

## v1.0.2

FIX : Utilise les mécanismes de traduction pour les textes.
NEW : Change méthode de création des paiements (utilise les fonctions API plutôt que les APIs)
NEW : Création du paiement, sauve le numéro de paiement retourné par Dolibarr.
NEW :  Textes de retour de paiement Hello Asso sont configurables.

## v1.0.1

NEW : gestion des langues pour l'interface administrateur
NEW : prendre le reste à payer d'une facture au lieu du montant total de la facture.
NEW : permettre de configurer la page de retour
