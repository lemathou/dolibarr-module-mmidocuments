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
		
		if (!empty($conf->global->MMIDOCUMENTS_VAT_NOTIF_PDF_DISPLAY)) {
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
			$countries_eu = explode(',', !empty($conf->global->MAIN_COUNTRIES_IN_EEC) ?$conf->global->MAIN_COUNTRIES_IN_EEC :'AT,BE,BG,CY,CZ,DE,DK,EE,ES,FI,FR,GB,GR,HR,NL,HU,IE,IM,IT,LT,LU,LV,MC,MT,PL,PT,RO,SE,SK,SI,UK');
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
			if (!empty($conf->global->MAIN_DOCUMENTS_VAT_COL_WIDTH)) {
				$pdf->cols['vat']['width'] = $conf->global->MAIN_DOCUMENTS_VAT_COL_WIDTH;
			}

			// Largeur colonne Quantité
			if (!empty($conf->global->MAIN_DOCUMENTS_QTY_COL_WIDTH)) {
				$pdf->cols['qty']['width'] = $conf->global->MAIN_DOCUMENTS_QTY_COL_WIDTH;
			}

			// Unité juste après quantité
			$pdf->cols['unit']['rank'] = $pdf->cols['qty']['rank']+1;

			// Factures de situation
			if ($pdf->situationinvoice) {
				if ($conf->global->MMIDOCUMENT_SITUATION_SHOW_CUMUL) {
					$pdf->cols['progress']['title'] = ['textkey'=>'ProgressAndCumulated'];

					$pdf->cols['totalexcltax']['title'] = ['textkey'=>'TotalHTSituation'];
				}
				if ($conf->global->SITUATION_DISPLAY_100P_PER_LINE_PDF) {
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
					if (!empty($conf->global->SITUATION_DISPLAY_DIFF_ON_PDF)) {
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
		if ($pdftpl->getColumnStatus('situationtotal') && !empty($conf->global->SITUATION_DISPLAY_100P_PER_LINE_PDF)) {
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
		global $langs, $user, $conf;
		
		$error = '';

		if ($this->in_context($parameters, 'document')) {
			//var_dump($parameters); die();
			if (in_array($parameters['modulepart'], ['propal', 'commande', 'facture'])
				&& !empty($parameters['refname']) && $parameters['original_file'] == $parameters['refname'].'/'.$parameters['refname'].'.pdf'
				&& !empty($conf->global->MMIDOCUMENT_PDF_RENAME)) {
				global $db;
				if ($parameters['modulepart'] == 'propal') {
					require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
					$object = new Propal($db);
				}
				elseif ($parameters['modulepart'] == 'commande') {
					require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
					$object = new Commande($db);
				}
				elseif ($parameters['modulepart'] == 'facture') {
					require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
					$object = new Facture($db);
				}
				$object->fetch(NULL, $parameters['refname']);
				//var_dump($object); die();
				$parameters['filename'] = $this->pdf_filename($object).'.pdf';
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
	 * Get filename for PDF
	 *
	 * @return string
	 */
	public function pdf_filename($object)
	{
		global $conf;
		
		if (empty($conf->global->MMIDOCUMENT_PDF_RENAME))
			return;
		
		if (empty($object->thirdparty))
			$object->fetch_thirdparty();
		
		$thirdparty = $object->thirdparty;
		$file_e = [];
		$file_e[] = dol_sanitizeFileName($object->ref);
		if (!empty($conf->global->MMIDOCUMENT_PDF_RENAME_MYSOC)) {
			global $mysoc;
			$file_e[] = $mysoc->name;
		}
		if (!empty($conf->global->MMIDOCUMENT_PDF_RENAME_THIRDPARTY)) {
			$file_e[] = $thirdparty->name;
		}
		if (!empty($conf->global->MMIDOCUMENT_PDF_RENAME_REF_CUSTOMER) && !empty($object->ref_customer)) {
			$file_e[] = $object->ref_customer;
		}
		$filename = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', iconv('UTF-8','ASCII//TRANSLIT', implode('-', $file_e))));
		return !empty($conf->global->MMIDOCUMENT_PDF_RENAME_UPPERCASE) ?strtoupper($filename) :$filename;
	}
}
