# HELLOASSOPAY FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## Features

Description of the module...

<!--
![Screenshot helloassopay](img/screenshot_helloassopay.png?raw=true "HelloAssoPay"){imgmd}
-->

Other external modules are available on [Dolistore.com](https://www.dolistore.com).

## Translations

Translations can be completed manually by editing files into directories *langs*.

<!--
This module contains also a sample configuration for Transifex, under the hidden directory [.tx](.tx), so it is possible to manage translation using this service.

For more informations, see the [translator's documentation](https://wiki.dolibarr.org/index.php/Translator_documentation).

There is a [Transifex project](https://transifex.com/projects/p/dolibarr-module-template) for this module.
-->

<!--

## Installation

### From the ZIP file and GUI interface

If the module is a ready to deploy zip file, so with a name module_xxx-version.zip (like when downloading it from a market place like [Dolistore](https://www.dolistore.com)),
go into menu ```Home - Setup - Modules - Deploy external module``` and upload the zip file.

Note: If this screen tell you that there is no "custom" directory, check that your setup is correct:

- In your Dolibarr installation directory, edit the ```htdocs/conf/conf.php``` file and check that following lines are not commented:

    ```php
    //$dolibarr_main_url_root_alt ...
    //$dolibarr_main_document_root_alt ...
    ```

- Uncomment them if necessary (delete the leading ```//```) and assign a sensible value according to your Dolibarr installation

    For example :

    - UNIX:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';
        ```

    - Windows:
        ```php
        $dolibarr_main_url_root_alt = '/custom';
        $dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';
        ```

### From a GIT repository

Clone the repository in ```$dolibarr_main_document_root_alt/helloassopay```

```sh
cd ....../custom
git clone git@github.com:gitlogin/helloassopay.git helloassopay
```

### <a name="final_steps"></a>Final steps

From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module

-->

## Licenses

GPLv3 or (at your option) any later version. See file COPYING for more information.

# Documentation

## Principe général de fonctionnement

Le principe de fonctionnement est le suivant :

1) l'utilisateur effectue un appel à l'url de début : dolibarr_root/helloassopay/public/start.php.

Les paramètres GET associés à la page start.php sont :

- ref=xxxxx : id de la facture à payer (paramètre obligatoire)
-tracemode=true : demande au module d'enregistrer toutes les informations dans le fichier return_helloasso.log (paaramètre facultatif).

- start.php récupère les informations de la facture (Pour le montant, il détermine le reste à payer pour cette facture) puis effectue un appel à helloasso (nommé checkout), qui lui renvoie un lien de paiement, l'utilisateur est ensuite redirigé vers ce lien.
Le lien de paiement mène à une page de paiement Helloasso dans laquelle, sont automatiquement renseignés : les noms, prénoms, email, le description et le montant du paiement.

2) Sur le site HElloasso, l'utilisateur procède au paiement, en saisissant sa carte.

3) Lorque l'utilisateur a terminé son paiement, il est redirigé par Helloasso vers la page /public/backurl.php. Les paramètres GET d'appel de cette page permettent de choisir le message a afficher (le paiement est accepté, le paiement est refusé).

4) En parrallele, Helloasso renvoie toutes les informations (fonction IPN) concernant le paiement en appelant la page /public/ipnreturn.php (L'url de cette page est configurable sur le site helloasso). Cette page crée le paiement dans la base de données Dolibarr.

## paramètres de la page start.php

## Logs

- ipn_return.log : contient toutes les réponses IPN de helloqsso pour tous les paiements.
- return_helloasso.log : contient
  - 1) les erreurs rencontrées lors du fonctionnement du module
  - 2)les traces demandées dans la page start.php (tracemode=true). Les traces contiennent : le token retourné par HElloasso, la chaine de checkout envoyéee à Helloasso, l'url de paiement retournée par helloasso.

## configuration du module

HELLOASSOPAY_BASEURL : fourni par HelloAsso</br>";
HELLOASSOPAY_CLIENT_ID : fourni par HelloAsso</br>";
HELLOASSOPAY_CLIENT_SECRET : fourni par HelloAsso</br>";
HELLOASSOPAY_ORGANISM_SLUR : fourni par HelloAsso</br>";
HELLOASSO_PAYMENTMODE : Id dolibarr du mode de paiement par CB</br>";
HELLOASSO_BANK_ACCOUNT_FOR_PAYMENTS : Id dolibarr du compte bancaire sur lequel les paiements doivent être enregistrés</br>";
HELLOASSO_HEADER_AFTER_PAYMENT : Header de la page backurl.php, à afficher avant le message de succès/refus du paiement
HELLOASSO_URL_AFTER_PAYMENT : : After de la page backupurl.php, à afficher après le message de succès/refus du paiement
HELLOASSO_URL_AFTER_PAYMENT_PAGE : url de la page à appeler en remplacement de la page backurl.php
HELLOASSO_DOLIKEY_FOR_PAYMENTCREATE : Api key de l'utilisateur qui crée le paiement (celui qui sera identifié comme utilisateur créateur du paiement)

## Note

- La version 1 ne permet de gérer qu'un paiement par transaction (pas de paiement multi-facture).

# Projet V1

## Todo

NEW : prendre le reste à payer d'une facture au lieu du montant total de la facture.
NEW : permettre de configurer la page de retour
