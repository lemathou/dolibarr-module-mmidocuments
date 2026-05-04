<?php

/**
 * Copyright © 2023 Mathieu Moulin iProspetcive <contact@iprospective.fr>
 *
 * This file is part of MMIDOcuments.
 *
 * MMIDOcuments is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * MMIDOcuments is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MMIDOcuments.  If not, see <http://www.gnu.org/licenses/>.
 */

dol_include_once('custom/mmicommon/class/mmi_actions.class.php');
dol_include_once('/mbietransactions/class/mmi_etransactions.class.php');

require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';

class ActionsMMIDocuments extends MMI_Actions_1_0
{
	const MOD_NAME = 'mmidocuments';

	function VATNotificationOnPDF($parameters, &$pdf, &$action, $hookmanager)
	{
		global $langs, $user, $conf;
		
		$error = '';
		
		// VATIsNotUsedForInvoice
		if (getDolGlobalInt('MMIDOCUMENTS_VAT_NOTIF_PDF_DISPLAY')) {
			$mysoc = $parameters['mysoc'];
			$emetteur = $parameters['emetteur'];
			$object = $parameters['object'];

			$client = $object->thirdparty;
			$contacts = $object->liste_contact();
			foreach($contacts as $contact) {
				// Adresse livraison
				if (in_array($contact['fk_c_type_contact'], [102, 42, 61])) {
					$adresse_liv = new Contact($this->db);
					$adresse_liv->fetch($contact['id']);
				}
				// Adresse facturation
				elseif (in_array($contact['fk_c_type_contact'], [100, 40, 60])) {
					$adresse_fac = new Contact($this->db);
					$adresse_fac->fetch($contact['id']);
				}
			}
			if (empty($adresse_liv))
				$adresse_liv = $client;
			if (empty($adresse_fac))
				$adresse_fac = $client;
			//var_dump($adresse_fac->country_code); die();
			//var_dump($client->tva_intra && in_array($adresse_fac->country_code, $countries_eu)); die();

			// Mentions TVA
			$countries_eu = explode(',', ($MAIN_COUNTRIES_IN_EEC=getDolGlobalString('MAIN_COUNTRIES_IN_EEC')) ?$MAIN_COUNTRIES_IN_EEC :'AT,BE,BG,CY,CZ,DE,DK,EE,ES,FI,FR,GB,GR,HR,NL,HU,IE,IM,IT,LT,LU,LV,MC,MT,PL,PT,RO,SE,SK,SI,UK');
			// Pas de TVA

			//var_dump($client); die();
			//var_dump($object); die();
			//var_dump($mysoc); die();
			//var_dump($object->total_tva==0); die();

			// Emétteur en France uniquement !
			if ($emetteur->country_code == 'FR') {
				// Emetteur non assujetti
				if (empty($mysoc->tva_assuj)) {
					// Asso
					if ($mysoc->forme_juridique_code == 92)
						$vat_info = $langs->transnoentities("VATIsNotUsedForInvoiceAsso");
					// Société (AE, etc.)
					else
						$vat_info = $langs->transnoentities("VATIsNotUsedForInvoice");
				}
				// TOT = 0 (brouillon)
				elseif ($object->total_ttc == 0) {
					$vat_info = '';
				}
				// TVA
				elseif (!($object->total_tva == 0)) {
					$vat_info = '';
				}
				// Exonération de de TVA
				else {
					// Transitaire
					if (!empty($object->array_options['options_transitaire'])) {
						$vat_info = $langs->transnoentities("VATIsNotUsedForTransitaire");
					}
					// DOM : Guadeloupe, Guyane, Martinique, Mayotte ou La Réunion
					elseif (in_array($adresse_fac->country_code, ['FR', 'GF']) && substr($adresse_liv->zip, 0, 2)=='97') {
						$vat_info = $langs->transnoentities("VATIsNotUsedForDOM");
					}
					// TOM
					elseif (in_array($adresse_fac->country_code, ['FR', 'PF']) && substr($adresse_liv->zip, 0, 2)=='98') {
						$vat_info = $langs->transnoentities("VATIsNotUsedForTOM");
					}
					// FR avec code intra et tout qui va bien
					elseif ($client->tva_intra && $adresse_fac->country_code == 'FR') {
						if (!empty($object->array_options['options_appeloffre']) && !empty($object->array_options['options_appeloffre_soustraitant']))
							$vat_info = $langs->transnoentities("VATIsNotUsedForFRAppelOffresSSTraitant");
						else
							$vat_info = $langs->transnoentities("VATIsNotUsedForFR");
					}
					// UE avec code intra et tout qui va bien
					elseif ($client->tva_intra && in_array($adresse_fac->country_code, $countries_eu)) {
						$vat_info = $langs->transnoentities("VATIsNotUsedForEU");
					}
					elseif ($client->tva_intra) {
						$error = 'Exonération de TVA art. 262 ter, I du CGI => TVA Intra MAIS pays à spécifier';
					}
					// UE PRO sans code intra => a spécifier
					elseif (($client->idprof1 || $client->idprof2) && in_array($adresse_fac->country_code, $countries_eu)) {
						$error = 'Exonération de TVA art. 262 ter, I du CGI => N°TVA intracom à spécifier';
					}
					// Îles (Canaries, etc.)
					elseif (false) {
						$vat_info = $langs->transnoentities("VATIsNotUsedForIslands");
					}
					// UE sans code intra => particulier => tva du pays => ERREUR PAS TVA
					elseif (in_array($adresse_fac->country_code, $countries_eu)) {
						$error = 'Exoneration de TVA pour un PARTICULIER en UE !';
					}
					// Hors UE
					elseif ($adresse_fac->country_code && !in_array($adresse_fac->country_code, $countries_eu)) {
						$vat_info = $langs->transnoentities("VATIsNotUsedForExport");
					}
					// PRO Pays non spécifié
					elseif ($client->idprof1 || $client->idprof2) {
						$vat_info = '';
						$error = 'Exoneration de TVA pour un PRO, MAIS le pays du client n\'est pas spécifié !';
					}
					// Pays non spécifié
					else {
						$vat_info = '';
						$error = 'Exoneration de TVA pour un PARTICULIER, ET le pays du client n\'est pas spécifié';
					}
				}
			}
			//var_dump($vat_info); die();
			
			$this->resprints = $vat_info;
			$ret = 1;
		}

		if (!$error) {
			return isset($ret) ?$ret :0; // or return 1 to replace standard code
		} else {
			$this->errors[] = $error;
			return -1;
		}
	}

	/**
	 * Calcul précis de la hauteur des zones du PDF
	 * en prenant en compte des champs supplémentaires
	 */
	function beforePDFCalculation($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user, $conf;
		
		$error = '';

		if ($this->in_context($parameters, 'pdfgeneration')) {
			$object = $parameters['object'];
			$object_type = is_object($object) ?$object->element :'';

			if ($object_type=='commande') {
				$infottot_height = &$parameters['infottot_height'];
				// Champs de base
				if (!empty($object->cond_reglement))
					$infottot_height += 4;
				if (!empty($object->delivery_date) || !empty($object->availability_code) || !empty($object->availability))
					$infottot_height += 4;
				if (empty($object->mode_reglement_code))
					$infottot_height += 36;
				elseif (in_array($object->mode_reglement_code, ['VIR']))
					$infottot_height += 22;
				elseif (in_array($object->mode_reglement_code, ['CHQ']))
					$infottot_height += 14;
				else
					$infottot_height += 4;
				// Champs supplémentaires
				if (!empty($object->shipping_method_id))
					$infottot_height += 4;
			}
		}

		if (!$error) {
			return isset($ret) ?$ret :0; // or return 1 to replace standard code
		} else {
			$this->errors[] = $error;
			return -1;
		}
	}

	function drawInfoTable($parameters, &$pdf, &$action, $hookmanager)
	{
		global $langs, $user, $conf;
		
		$error = '';

		if ($this->in_context($parameters, 'pdfgeneration')) {

			$pdf = $parameters['pdf'];
			$object = $parameters['object'];
			$object_type = is_object($object) ?$object->element :'';
			//var_dump($object);
			//var_dump($object_type);
			$outputlangs = $parameters['outputlangs'];
			$posxval = $parameters['posxval'];
			$default_font_size = $parameters['default_font_size'];
			$marge_gauche = $parameters['marge_gauche'];
			$posy = $parameters['posy'];
	
			if ($object_type=='commande' && !empty($object->shipping_method_id)) {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->SetXY($marge_gauche, $posy);
				$titre = html_entity_decode($outputlangs->trans("SendingMethod")).':';
				$pdf->MultiCell(80, 4, $titre, 0, 'L');
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posxval, $posy);
				$code = $outputlangs->getLabelFromKey($this->db, $object->shipping_method_id, 'c_shipment_mode', 'rowid', 'code');
				$label = $outputlangs->trans("SendingMethod".strtoupper($code));
				$pdf->MultiCell(80, 4, $label, 0, 'L');

				$parameters['posy'] = $pdf->GetY() + 2;
			}
		}

		if (!$error) {
			return isset($ret) ?$ret :0; // or return 1 to replace standard code
		} else {
			$this->errors[] = $error;
			return -1;
		}
	}

	function defineColumnField($parameters, &$pdf, &$action, $hookmanager)
	{
		global $langs, $user, $conf;
		
		$error = '';

		if ($this->in_context($parameters, 'pdfgeneration')) {

			// Largeur colonne VAT
			if ($width=getDolGlobalString('MAIN_DOCUMENTS_VAT_COL_WIDTH')) {
				$pdf->cols['vat']['width'] = $width;
			}

			// Largeur colonne Quantité
			if ($width=getDolGlobalString('MAIN_DOCUMENTS_QTY_COL_WIDTH')) {
				$pdf->cols['qty']['width'] = $width;
			}

			// Unité juste après quantité
			$pdf->cols['unit']['rank'] = $pdf->cols['qty']['rank']+1;

			// Factures de situation
			if ($pdf->situationinvoice) {
				if (getDolGlobalInt('MMIDOCUMENT_SITUATION_SHOW_CUMUL')) {
					$pdf->cols['progress']['title'] = ['textkey'=>'ProgressAndCumulated'];

					$pdf->cols['totalexcltax']['title'] = ['textkey'=>'TotalHTSituation'];
				}
				if (getDolGlobalInt('SITUATION_DISPLAY_100P_PER_LINE_PDF')) {
					$pdf->cols['situationtotal'] = array(
						// Peu après qté & unité
						'rank' => $pdf->cols['unit']['rank']+5,
						'width' => 19, // in mm
						'status' => true,
						'title' => array(
							'textkey' => 'Total HT 100%'
						),
						'border-left' => true, // add left line separator
					);
				}
			}
		}

		if (!$error) {
			return isset($ret) ?$ret :0; // or return 1 to replace standard code
		} else {
			$this->errors[] = $error;
			return -1;
		}
	}

	// @todo terminer la migration proprement
	function NONONOprintStdColumnContent($parameters, &$pdf, &$action, $hookmanager)
	{
		global $langs, $user, $conf;
		
		// $parameters = array(
		// 	'curY' => &$curY,
		// 	'columnText' => $columnText,
		// 	'colKey' => $colKey,
		// 	'pdf' => &$pdf,
		// );

		$error = '';

		if ($this->in_context($parameters, 'pdfgeneration')) {
			if ($parameters['colKey'] == 'totalexcltax') {
				$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
				// MMI Hack
				if ($total_excl_tax != ' ' && $object->lines[$i]->situation_percent>0) {
					if (getDolGlobalInt('SITUATION_DISPLAY_DIFF_ON_PDF')) {
						$total_excl_tax = $total_excl_tax.'<br />('.number_format(round(str_replace(',', '.', $qty)*str_replace([' ', ','], ['', '.'], $up_excl_tax)*str_replace(',', '.', $object->lines[$i]->situation_percent)/100, 2), 2, ',', ' ').')';
					}
					else {
						$total_excl_tax = number_format(round($object->lines[$i]->total_ht, 2), 2, ',', ' ');
					}
				}
				
			}
		}

		if (!$error) {
			return isset($ret) ?$ret :0; // or return 1 to replace standard code
		} else {
			$this->errors[] = $error;
			return -1;
		}
	}

	function printPDFline($parameters, &$pdftpl, &$action, $hookmanager)
	{
		global $langs, $user, $conf;

		// $parameters = array(
		// 	'object' => $object,
		// 	'i' => $i,
		// 	'pdf' =>& $pdf,
		// 	'curY' =>& $curY,
		// 	'nexY' =>& $nexY,
		// 	'outputlangs' => $outputlangs,
		// 	'hidedetails' => $hidedetails
		// );

		$error = '';

		extract($parameters, EXTR_SKIP);

		// MMI Hack
		// Situation Total
		if ($pdftpl->getColumnStatus('situationtotal') && getDolGlobalInt('SITUATION_DISPLAY_100P_PER_LINE_PDF')) {
			if ($object->lines[$i]->qty>0 && $object->lines[$i]->subprice>0) {
				$situationtotal = number_format(round($object->lines[$i]->qty*$object->lines[$i]->subprice, 2), 2, ',', ' ');
			}
			else {
				$situationtotal = '';
			}
			$pdftpl->printStdColumnContent($pdf, $curY, 'situationtotal', $situationtotal);
			$nexY = max($pdf->GetY(), $nexY);
		}

		if (!$error) {
			return isset($ret) ?$ret :0; // or return 1 to replace standard code
		} else {
			$this->errors[] = $error;
			return -1;
		}
	}

	function downloadDocument($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user, $db;
		
		$error = '';

		if ($this->in_context($parameters, 'document')) {
			if (in_array($parameters['modulepart'], ['propal', 'propale', 'commande', 'facture'])) {
				$ref = array_shift(explode('/', $parameters['original_file']));
				// Rename PDF
				if (getDolGlobalInt('MMIDOCUMENT_PDF_RENAME') && $parameters['original_file'] == $ref.'/'.$ref.'.pdf') {
					if (in_array($parameters['modulepart'], ['propal', 'propale'])) {
						$object = new Propal($db);
					}
					elseif ($parameters['modulepart'] == 'commande') {
						$object = new Commande($db);
					}
					elseif ($parameters['modulepart'] == 'facture') {
						$object = new Facture($db);
					}
					$object->fetch(NULL, $ref);
					//var_dump($object); die();
					$parameters['filename'] = $this->pdf_filename($object).'.pdf';
				}
			}
		}

		if (!$error) {
			return isset($ret) ?$ret :0; // or return 1 to replace standard code
		} else {
			$this->errors[] = $error;
			return -1;
		}
	}

	/**
	 * Add a "PDF merge (duplex)" entry in the shipment list mass action menu.
	 * The standard "PDF merge" stays in place; duplex is a sibling.
	 */
	function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		if (!$this->in_context($parameters, 'shipmentlist'))
			return 0;

		$langs->load('mmidocuments@mmidocuments');

		$label = img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans('PDFMergeDuplex');
		$this->resprints = '<option value="builddoc_duplex" data-html="'.dol_escape_htmltag($label).'">'.$label.'</option>';

		return 0;
	}

	/**
	 * Handle the 'builddoc_duplex' mass action: same merge as core builddoc,
	 * but inserts a blank page after every shipment whose PDF has an odd
	 * page count, so subsequent shipments always start on a recto when
	 * printing duplex.
	 *
	 * Note: USE_PDFTK_FOR_PDF_CONCAT is intentionally not honored here —
	 * the duplex variant always uses the FPDI/TCPDI path.
	 */
	function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $db, $user, $langs;

		if (!$this->in_context($parameters, 'shipmentlist'))
			return 0;

		if (($parameters['massaction'] ?? '') !== 'builddoc_duplex')
			return 0;

		$error = '';

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

		$langs->load('mmidocuments@mmidocuments');
		$langs->load('exports');

		$toselect = $parameters['toselect'] ?? array();
		$uploaddir = $parameters['uploaddir'] ?? '';
		$diroutputmassaction = $parameters['diroutputmassaction'] ?? '';

		if (empty($toselect) || empty($uploaddir) || empty($diroutputmassaction))
			return 0;

		if (empty($user->rights->expedition->lire)) {
			$this->errors[] = $langs->trans('NotEnoughPermissions');
			return -1;
		}

		// Fetch all selected shipments and keep instances around — we may need
		// to (re)generate their PDF if it's missing on disk.
		$listofobjects = array();
		$listofobjectref = array();
		foreach ($toselect as $toselectid) {
			$tmp = new Expedition($db);
			if ($tmp->fetch($toselectid) > 0) {
				$listofobjects[$toselectid] = $tmp;
				$listofobjectref[$toselectid] = $tmp->ref;
			}
		}

		$arrayofinclusion = array();
		foreach ($listofobjectref as $tmppdf) {
			$arrayofinclusion[] = '^'.preg_quote(dol_sanitizeFileName($tmppdf), '/').'\.pdf$';
		}
		foreach ($listofobjectref as $tmppdf) {
			$arrayofinclusion[] = '^'.preg_quote(dol_sanitizeFileName($tmppdf), '/').'_[a-zA-Z0-9\-\_\'\&\.]+\.pdf$';
		}
		$listoffiles = dol_dir_list($uploaddir, 'all', 1, implode('|', $arrayofinclusion), '\.meta$|\.png', 'date', SORT_DESC, 0, true);

		// Per-ref lookup so we know which shipments are missing a PDF.
		// Iteration preserves the user's selection order in the merged output.
		// Expedition::generateDocument() handles the empty-model fallback itself
		// (object's model_pdf → EXPEDITION_ADDON_PDF → 'rouget'), so we just pass ''.
		$files = array();
		$generated = 0;
		foreach ($listofobjectref as $id => $ref) {
			$sanitized = dol_sanitizeFileName($ref);
			$found = null;
			foreach ($listoffiles as $filefound) {
				if (strstr($filefound['name'], $sanitized)) {
					$found = $uploaddir.'/'.$sanitized.'/'.$filefound['name'];
					break;
				}
			}

			if ($found === null) {
				$exp = $listofobjects[$id];
				if (empty($exp->thirdparty)) $exp->fetch_thirdparty();
				$r = $exp->generateDocument('', $langs);
				if ($r > 0) {
					$newlist = dol_dir_list($uploaddir.'/'.$sanitized, 'files', 0, '\.pdf$', '\.meta$|\.png', 'date', SORT_DESC);
					foreach ($newlist as $filefound) {
						if (strstr($filefound['name'], $sanitized)) {
							$found = $uploaddir.'/'.$sanitized.'/'.$filefound['name'];
							break;
						}
					}
					if ($found !== null) $generated++;
				} else {
					setEventMessages($exp->error, $exp->errors, 'warnings');
				}
			}

			if ($found !== null) $files[] = $found;
		}

		if (count($files) == 0) {
			setEventMessages($langs->trans('NoPDFAvailableForDocGenAmongChecked'), null, 'errors');
			return 1;
		}

		if ($generated > 0) {
			setEventMessages($langs->trans('PDFMergeDuplexGenerated', $generated), null, 'mesgs');
		}

		$formatarray = pdf_getFormat();
		$format = array($formatarray['width'], $formatarray['height']);

		$pdf = pdf_getInstance($format);
		if (class_exists('TCPDF')) {
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}
		$pdf->SetFont(pdf_getPDFFont($langs));
		if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION'))
			$pdf->SetCompression(false);

		foreach ($files as $file) {
			$pagecount = $pdf->setSourceFile($file);
			$lastsize = null;
			for ($i = 1; $i <= $pagecount; $i++) {
				$tplidx = $pdf->importPage($i);
				$s = $pdf->getTemplatesize($tplidx);
				$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
				$pdf->useTemplate($tplidx);
				$lastsize = $s;
			}
			// Duplex padding: blank page if this shipment has an odd page count
			if (($pagecount % 2) == 1 && $lastsize) {
				$pdf->AddPage($lastsize['h'] > $lastsize['w'] ? 'P' : 'L');
			}
		}

		dol_mkdir($diroutputmassaction);

		$filename = strtolower(dol_sanitizeFileName($langs->transnoentities('Sendings')));
		$filename = preg_replace('/\s/', '_', $filename).'_duplex';

		$now = dol_now();
		$outputfile = $diroutputmassaction.'/'.$filename.'_'.dol_print_date($now, 'dayhourlog').'.pdf';

		$pdf->Output($outputfile, 'F');
		dolChmod($outputfile);

		setEventMessages($langs->trans('FileSuccessfullyBuilt', $filename.'_'.dol_print_date($now, 'dayhourlog')), null, 'mesgs');

		if (!$error)
			return 1;

		$this->errors[] = $error;
		return -1;
	}

	/**
	 * Get filename for PDF
	 *
	 * @return string
	 */
	public function pdf_filename($object)
	{
		global $conf;
		
		if (!getDolGlobalInt('MMIDOCUMENT_PDF_RENAME'))
			return;
		
		if (empty($object->thirdparty))
			$object->fetch_thirdparty();
		
		$thirdparty = $object->thirdparty;
		$file_e = [];
		$file_e[] = dol_sanitizeFileName($object->ref);
		if (getDolGlobalInt('MMIDOCUMENT_PDF_RENAME_MYSOC')) {
			global $mysoc;
			$file_e[] = $mysoc->name;
		}
		if (getDolGlobalInt('MMIDOCUMENT_PDF_RENAME_THIRDPARTY')) {
			$file_e[] = $thirdparty->name;
		}
		if (getDolGlobalInt('MMIDOCUMENT_PDF_RENAME_REF_CUSTOMER') && !empty($object->ref_customer)) {
			$file_e[] = $object->ref_customer;
		}
		$filename = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', iconv('UTF-8','ASCII//TRANSLIT', implode('-', $file_e))));
		return getDolGlobalInt('MMIDOCUMENT_PDF_RENAME_UPPERCASE') ?strtoupper($filename) :$filename;
	}
}

ActionsMMIDocuments::__init();

