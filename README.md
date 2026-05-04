# MMIDOCUMENTS FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## Features

Ensemble d'options d'affichage et de personnalisations sur les documents commerciaux (propales, commandes, factures, expéditions, demandes de prix fournisseur), regroupées sous une page de configuration unique (`Setup → Modules → MMIDocuments`).

### Mentions de TVA et exonérations

Sélection automatique d'une mention légale d'exonération de TVA en bas de PDF en fonction du tiers et du contexte (option `MMIDOCUMENTS_VAT_NOTIF_PDF_DISPLAY`) :

- Transitaire (article CGI dédié).
- DOM (art. 294 CGI).
- TOM (art. 262 I CGI).
- France métropolitaine — autoliquidation (art. 262 ter, I CGI).
- France — sous-traitance appel d'offre (art. 242 nonies A annexe II).
- UE intracommunautaire (art. 262 ter, I CGI).
- Îles non-soumises (art. 259-1 CGI).
- Export hors UE (art. 259-1 CGI).

Détection automatique selon le pays du tiers facturé / livré et son numéro de TVA intra ; surcharge possible via les champs ajoutés sur les contacts (TVA intra, SIRET, SIREN, et flag « point relais »).

### Bordereaux d'expédition

- Cacher la colonne poids/volume, ou le volume seul (`SHIPPING_PDF_HIDE_WEIGHT_AND_VOLUME`, `SHIPPING_PDF_HIDE_VOLUME`).
- Cacher les numéros de lot (`SHIPPING_PDF_HIDE_BATCH`).
- Cacher la date d'expédition (`SHIPPING_PDF_HIDE_DELIVERY_DATE`).
- Afficher les images produit (`MAIN_GENERATE_SHIPMENT_WITH_PICTURE`).
- Message HTML configurable dans le cadre note (`MMI_SHIPPING_PDF_MESSAGE`).
- Afficher le montant HT (`SHIPPING_PDF_DISPLAY_AMOUNT_HT`).
- **Fusion PDF (recto/verso)** : nouvelle action de masse sur la liste des expéditions qui fusionne les PDF sélectionnés en insérant une page blanche après chaque expédition à pages impaires. L'expédition suivante repart toujours sur un recto à l'impression duplex. Les PDF manquants sont auto-générés via `Expedition::generateDocument()` avant fusion.

### Factures de situation

- Afficher les cumuls (`MMIDOCUMENT_SITUATION_SHOW_CUMUL`).
- Retenue de garantie cumulée (`INVOICE_RETAINED_WARRANTY_CUMULATED_SHOW`).
- Colonne « Total 100% » par ligne (`SITUATION_DISPLAY_100P_PER_LINE_PDF`) — utile en marchés publics.

### Renommage des PDF lors du téléchargement

Quand un utilisateur télécharge un PDF de propale / commande / facture, le fichier est renommé selon des composants paramétrables (`MMIDOCUMENT_PDF_RENAME`) :

- Référence du document (toujours).
- Nom de la société émettrice (`_MYSOC`).
- Nom du tiers client (`_THIRDPARTY`).
- Référence client (`_REF_CUSTOMER`).
- Forçage en majuscules (`_UPPERCASE`).

Tout est translittéré ASCII pour éviter les caractères problématiques dans les noms de fichiers.

### Affichage des références produit dans les lignes de document

Activable via `MMI_DOCUMENTS_DISPLAY_REF_ACTIVE`, configurable par type de document :

- Référence propre du produit : choisir parmi propale / commande / facture / commande fournisseur (`MMI_DOCUMENTS_DISPLAY_REF_OWN`).
- Référence fournisseur : idem (`MMI_DOCUMENTS_DISPLAY_REF_SUPPLIER`).
- Cacher la référence sur les appels d'offre / marchés publics (`MMI_DOCUMENTS_DISPLAY_REF_MARCHE_HIDE`), souvent demandé par les acheteurs publics.

Surcharge ligne par ligne via les extrafields `pdf_show_productline_ref` et `pdf_show_productline_supplier_ref` (Yes / No / Default).

### Demandes de prix fournisseur

- Cacher la description produit (`MAIN_DOCUMENTS_HIDE_DESCRIPTION_FOR_SUPPLIER_PROPOSAL`).
- Cacher la référence produit (`MAIN_GENERATE_SUPPLIER_PROPOSAL_HIDE_REF`).

### Mise en page commune à tous les documents

- Afficher le commercial dans le bloc émetteur (`MMIDOCUMENTS_PDF_COMMERCIAL`).
- Afficher l'origine dans les lignes pour l'export (`MMIDOCUMENTS_PDF_EXPORT_ORIGINE`).
- Largeur des colonnes Quantité et TVA configurables (`MAIN_DOCUMENTS_QTY_COL_WIDTH`, `MAIN_DOCUMENTS_VAT_COL_WIDTH`).
- Séparation visuelle des contacts livraison et facturation (`MMI_DOCUMENT_PDF_SEPARATE_CONTACTS`).
- Calcul précis de la hauteur des zones du PDF en fonction des champs présents — évite les chevauchements (`MMI_DOCUMENT_PDF_HEIGHT_CALC`).
- Affichage alternatif des extrafields dans les lignes de document (`MMI_DOCUMENT_LINE_EXTRAFIELDS_ALTVIEW`).

### Conditions générales / particulières (CGV / CPV)

Extrafield `cgv_cpv` ajouté sur propales, commandes et factures (activable via `MMI_FIELD_CGV_CPV`) : champ HTML libre affiché en bas de document PDF. Utile pour les conditions particulières propres à chaque marché.

Le titre « Conditions particulières » apparaît automatiquement (`MMIDOCUMENT_CGP_TITLE`) ; même chose pour le bloc « Informations complémentaires » (`DOCUMENT_SHOW_COMPLEMENT`, `DOCUMENT_COMPLEMENT_TITLE`).

### Acomptes et avoirs

- Extrafield `acompte_aff` sur propale : forcer l'affichage de l'acompte sur le PDF.
- Extrafield `avoirs_as_acompte` sur facture : afficher les avoirs comme un acompte (« Acomptes précédemment réglés ») au lieu de la mention standard. Pratique pour les factures d'avancement après utilisation d'un avoir issu d'un acompte.

### Champs supplémentaires sur les contacts

Extrafields ajoutés sur les contacts (`socpeople`) :

- `tva_intra` — numéro de TVA intracommunautaire du contact (utilisé pour les mentions d'exonération).
- `siren`, `siret` — identifiants entreprise du contact.
- `societe_name_hide` — masquer le nom de la société dans le bloc destinataire (cas livraison particulier).
- `p_company` — nom alternatif de société pour les points relais (consommé par MMIWorkflow).

## Dependencies

- `modMMICommon`

PHP ≥ 7.4, Dolibarr ≥ 11.

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

- If you get the module in a zip file (like when downloading it from the market place [Dolistore](https://www.dolistore.com)), go into
menu ```Home - Setup - Modules - Deploy external module``` and upload the zip file.

Note: If this screen tell you there is no custom directory, check your setup is correct:

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

- Clone the repository in ```$dolibarr_main_document_root_alt/mmidocuments```

```sh
cd ....../custom
git clone git@github.com:gitlogin/mmidocuments.git mmidocuments
```

### <a name="final_steps"></a>Final steps

From your browser:

  - Log into Dolibarr as a super-administrator
  - Go to "Setup" -> "Modules"
  - You should now be able to find and enable the module

-->

## Licenses

### Main code

GPLv3 or (at your option) any later version. See file COPYING for more information.

### Documentation

All texts and readmes are licensed under GFDL.
