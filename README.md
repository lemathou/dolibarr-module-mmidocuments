# MMIDocuments — Documents MMI : modèles PDF/ODT, champs complémentaires et options d'impression des documents de vente et d'expédition.

> Module Dolibarr **MMIDocuments** (version 1.2.0), branche `20.0`.

Options d'affichage documents et nouveaux modèles de PDF

## Fonctionnalités

### Fonctionnalités principales

- Champs complémentaires documents : acompte affiché, CGV/CPV, référence ligne produit, référence fournisseur, SIRET, nom société masqué, avoirs en acompte.
- Options PDF/ODT : affichage du complément, export origine, PDF commercial, largeur des colonnes quantité/TVA.
- Options d'expédition : masquer poids/volume/lot/date de livraison, afficher le montant HT, images, message.
- Hooks de documents et substitutions ODT.
- Modèles de documents du fork (`pdf_sponge`, `pdf_crabe`, `pdf_octopus`, `doc_generic_invoice_odt`).

### Pages

- `admin/about.php`
- `admin/setup.php`
- `mmidocumentsindex.php`

### Configuration

| Constante | Type | Rôle |
|---|---|---|
| `DOCUMENT_SHOW_COMPLEMENT` | yesno | Afficher les informations complémentaires |
| `DOCUMENT_COMPLEMENT_TITLE` | yesno |  |
| `MMIDOCUMENT_ADMIN_SHIPPING` | separator | Expéditions |
| `SHIPPING_PDF_HIDE_WEIGHT_AND_VOLUME` | yesno | Masquer la colonne "poids et volume" dans les expéditions |
| `SHIPPING_PDF_HIDE_VOLUME` | yesno | Masquer les volumes (conserver les poids) dans les expéditions |
| `SHIPPING_PDF_HIDE_BATCH` | yesno | Cacher les numéros de lot dans les expéditions |
| `SHIPPING_PDF_HIDE_DELIVERY_DATE` | yesno | Cacher la date d'expédition dans les expéditions |
| `MAIN_GENERATE_SHIPMENT_WITH_PICTURE` | yesno | Affiche les images produit dans les expéditions |
| `MMI_SHIPPING_PDF_MESSAGE` | html | Message affiché dans le cadre note des expéditions |
| `SHIPPING_PDF_DISPLAY_AMOUNT_HT` | yesno |  |
| `MMIDOCUMENT_ADMIN_DOCUMENTS_ALL` | separator | Tous les documents |
| `MMIDOCUMENTS_PDF_EXPORT_ORIGINE` | yesno | Afficher l'origine dans les lignes de documents pour l'Export |
| `MMIDOCUMENTS_PDF_COMMERCIAL` | yesno | Afficher le commercial dans l'encart expéditeur |
| `MAIN_DOCUMENTS_QTY_COL_WIDTH` | int | Largeur de colonne QTY en mm dans les documents (défaut 16, recommandé 11) |
| `MAIN_DOCUMENTS_VAT_COL_WIDTH` | int | Largeur de colonne VAT en mm dans les documents (défaut 16, recommandé 11) |
| `MMI_DOCUMENT_PDF_SEPARATE_CONTACTS` | yesno | Séparer l'affichage des contacts de livraison et facturation |
| `MMI_FIELD_CGV_CPV` | yesno | Ajout du champ CGV/CPV dans les propales, commandes et factures |
| `MMIDOCUMENT_CGP_TITLE` | yesno |  |
| `MMIDOCUMENTS_VAT_NOTIF_PDF_DISPLAY` | yesno | Gestion de l'exonération de TVA dans les documents (Propales, Commandes, Factures) |
| `MMI_DOCUMENT_PDF_HEIGHT_CALC` | yesno |  |
| `MMIDOCUMENT_ADMIN_PDF_RENAME` | separator | Renommage des PDF |
| `MMIDOCUMENT_PDF_RENAME` | yesno | Renommer les PDF des documents |
| `MMIDOCUMENT_PDF_RENAME_UPPERCASE` | yesno | Nom de fichier PDF en majuscule |
| `MMIDOCUMENT_PDF_RENAME_MYSOC` | yesno | Ajouter le nom de notre société dans le nom de fichier PDF |
| `MMIDOCUMENT_PDF_RENAME_THIRDPARTY` | yesno | Ajouter le nom du client dans le nom de fichier PDF |
| `MMIDOCUMENT_PDF_RENAME_REF_CUSTOMER` | yesno | AJouter la référence client dans le nom de fichier PDF |
| `MMIDOCUMENT_ADMIN_SUPPLIER_PROPOSAL` | separator | Demandes de prix fournisseur |
| `MAIN_DOCUMENTS_HIDE_DESCRIPTION_FOR_SUPPLIER_PROPOSAL` | yesno | Cacher les descriptions de produit dans les demandes de prix |
| `MAIN_GENERATE_SUPPLIER_PROPOSAL_HIDE_REF` | yesno | Cacher les références produit dans les demandes de prix |
| `MMIDOCUMENT_ADMIN_SITUATION_INVOICES` | separator | Factures de situation |
| `MMIDOCUMENT_SITUATION_SHOW_CUMUL` | yesno | Afficher les cumuls dans les factures de situation |
| `INVOICE_RETAINED_WARRANTY_CUMULATED_SHOW` | yesno | Afficher la retenue de garantie cumulée sur les factures |
| `SITUATION_DISPLAY_100P_PER_LINE_PDF` | yesno | Afficher la colonne du total 100%% dans les lignes de factures de situation |
| `MMI_DOCUMENT_DISPLAY_NOPDF` | separator | Options d'affichage dans les documents (pas PDF) |
| `MMI_DOCUMENT_LINE_EXTRAFIELDS_ALTVIEW` | yesno | Affichage alternatif des champs extrafields dans les lignes de document (on considère que dans la vue card, les lignes de documents sont en vue list et pas en vue view) |
| `MMI_DOCUMENTS_DISPLAY_REF` | separator | Affichage des références produit |
| `MMI_DOCUMENTS_DISPLAY_REF_ACTIVE` | yesno | Activer la gestion avancée de l'affichage des références produit |
| `MMI_DOCUMENTS_DISPLAY_REF_OWN` | multiarray | Afficher la référence propre du produit par défaut dans les types de document suivant |
| `MMI_DOCUMENTS_DISPLAY_REF_SUPPLIER` | multiarray | Afficher la référence fournisseur du produit par défaut dans les types de document suivant |
| `MMI_DOCUMENTS_DISPLAY_REF_MARCHE_HIDE` | yesno | Ne pas afficher la référence du produit sur les appels d'offre |

### Hooks

- Contextes : `pdfgeneration`, `document`, `shipmentlist`
- Callbacks : `VATNotificationOnPDF`, `beforePDFCalculation`, `drawInfoTable`, `defineColumnField`, `printPDFline`, `downloadDocument`, `addMoreMassActions`, `doMassActions`, `pdf_filename`

### Classes et composants

- `class/actions_mmidocuments.class.php`
- `lib/mmidocuments.lib.php`

## Prérequis

- Dolibarr >= 11.0 (branche `20.0` : Dolibarr 20.0).
- Modules MMI : `mmicommon`. **`mmicommon` doit être activé en premier.**

## Installation

1. Copier le module dans `htdocs/custom/mmidocuments` :

   ```sh
   cd htdocs/custom
   git clone <dépôt> mmidocuments
   ```

2. Dans Dolibarr : **Configuration → Modules**, activer d'abord `mmicommon`, puis ce module.
3. Vérifier les permissions dans la fiche des utilisateurs concernés.

## Licence

GPLv3 ou ultérieure — voir `COPYING`. Documentation sous GFDL.

## Changelog

Voir [CHANGELOG.md](CHANGELOG.md) (évolutions datées).
