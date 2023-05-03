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

			// Emétter non assujetti
			if ($emetteur->country_code == 'FR' && empty($mysoc->tva_assuj)) {
				if ($mysoc->forme_juridique_code == 92)
					$vat_info = $langs->transnoentities("VATIsNotUsedForInvoiceAsso");
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
					$vat_info = 'Exonération de TVA - Transitaire';
				}
				// DOM : Guadeloupe, Guyane, Martinique, Mayotte ou La Réunion
				elseif (in_array($adresse_fac->country_code, ['FR', 'GF']) && substr($adresse_liv->zip, 0, 2)=='97') {
					$vat_info = 'Exonération de TVA en application de l’article 294 du code général des impôts (DOM)';
				}
				// TOM
				elseif (in_array($adresse_fac->country_code, ['FR', 'PF']) && substr($adresse_liv->zip, 0, 2)=='98') {
					$vat_info = 'Exonération de TVA article 262 I du CGI (TOM)';
				}
				// UE avec code intra et tout qui va bien
				elseif ($client->tva_intra && in_array($adresse_fac->country_code, $countries_eu)) {
					$vat_info = 'Exonération de TVA art. 262 ter, I du CGI';
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
					$vat_info = 'TVA non applicable – art. 259-1 du CGI (îles)';
				}
				// UE sans code intra => particulier => tva du pays => ERREUR PAS TVA
				elseif (in_array($adresse_fac->country_code, $countries_eu)) {
					$error = 'Exoneration de TVA pour un PARTICULIER en UE !';
				}
				// Hors UE
				elseif ($adresse_fac->country_code && !in_array($adresse_fac->country_code, $countries_eu)) {
					$vat_info = 'TVA non applicable – art. 259-1 du CGI (Export hors UE)';
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
			
			$this->resprints = $vat_info;
			$ret = 1;
			//var_dump($vat_info); die();
		}

		if (!$error) {
			return isset($ret) ?$ret :0; // or return 1 to replace standard code
		} else {
			$this->errors[] = $error;
			return -1;
		}
	}
}
