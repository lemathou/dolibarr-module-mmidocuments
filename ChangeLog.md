# CHANGELOG MMIDOCUMENTS FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## 1.0

Initial version. Hooks on PDF generation: VAT exemption mentions, info table extras (shipping mode), column widths, situation invoice cumulated columns, custom contact fields (TVA intra, SIREN, SIRET) on PDFs.

## 1.1

Extended behaviour of displaying product references on documents.

New global options to:
* Choose on which document types to display own product references
* Choose on which document types to display supplier product references
* Hide product references on public markets (in particular situation invoices)

New specific options on each document, to:
* Force displaying or hiding of own product or supplier references.

Other additions in the 1.x series:
* PDF filename renaming on download (with optional uppercase, MySoc / thirdparty / customer ref components).
* Shipping PDF options: hide weight/volume, hide volume only, hide batch numbers, hide delivery date, show product images, show commercial in sender block, show amount HT.
* Supplier price request: option to hide product description and references.
* Situation invoices: layout improvements, retained warranty cumulated, total HT cumulated column.
* Alternative extrafields display in document lines.
* Hide thirdparty company name on contact block (extrafield `societe_name_hide`).
* Show legal product mentions only in export documents.

## 1.2.0

* PDF duplex merge: new **Fusion PDF (recto/verso)** mass action on the shipment list — merges the selected shipments' PDFs and inserts a blank page after each shipment with an odd page count, so the next shipment always starts on a recto when printing in duplex mode.
* Auto-generates missing shipment PDFs on the fly before the duplex merge (uses the shipment's `model_pdf`, falling back to `EXPEDITION_ADDON_PDF`, then `'rouget'`).
* New hook context `shipmentlist`; new hooks `addMoreMassActions` and `doMassActions`.
