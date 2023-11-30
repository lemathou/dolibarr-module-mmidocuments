<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2022 SuperAdmin <contact@calyclay.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    mmidocuments/admin/setup.php
 * \ingroup mmidocuments
 * \brief   MMIDocuments setup page.
 */

// Load Dolibarr environment
require_once '../env.inc.php';
require_once '../main_load.inc.php';

$arrayofparameters = array(
	'DOCUMENT_SHOW_COMPLEMENT'=>array('type'=>'yesno', 'enabled'=>1),
	'DOCUMENT_COMPLEMENT_TITLE'=>array('type'=>'yesno', 'enabled'=>1),
	'SHIPPING_PDF_HIDE_WEIGHT_AND_VOLUME'=>array('type'=>'yesno','enabled'=>1),
	'SHIPPING_PDF_HIDE_BATCH'=>array('type'=>'yesno','enabled'=>1), // MMI Hack
	'SHIPPING_PDF_HIDE_DELIVERY_DATE'=>array('type'=>'yesno','enabled'=>1), // MMI Hack
	'MAIN_GENERATE_SHIPMENT_WITH_PICTURE'=>array('type'=>'yesno','enabled'=>1),
	'MMI_SHIPPING_PDF_MESSAGE'=>array('type'=>'html','enabled'=>1),
	'MMI_DOCUMENT_PDF_SEPARATE_CONTACTS'=>array('type'=>'yesno','enabled'=>1),
	'MMI_FIELD_CGV_CPV'=>array('type'=>'yesno','enabled'=>1),
	'MMIDOCUMENT_CGP_TITLE'=>array('type'=>'yesno','enabled'=>1),
	'MMIDOCUMENTS_VAT_NOTIF_PDF_DISPLAY'=>array('type'=>'yesno','enabled'=>1),
	'MMI_DOCUMENT_PDF_HEIGHT_CALC'=>array('type'=>'yesno','enabled'=>1),
	'INVOICE_RETAINED_WARRANTY_CUMULATED_SHOW'=>array('type'=>'yesno','enabled'=>1),
	'MMIDOCUMENT_PDF_RENAME'=>array('type'=>'yesno','enabled'=>1),
	'MMIDOCUMENT_PDF_RENAME_UPPERCASE'=>array('type'=>'yesno','enabled'=>1),
	'MMIDOCUMENT_PDF_RENAME_MYSOC'=>array('type'=>'yesno','enabled'=>1),
	'MMIDOCUMENT_PDF_RENAME_THIRDPARTY'=>array('type'=>'yesno','enabled'=>1),
	'MMIDOCUMENT_PDF_RENAME_REF_CUSTOMER'=>array('type'=>'yesno','enabled'=>1),
	'MAIN_GENERATE_SUPPLIER_PROPOSAL_HIDE_DESC'=>array('type'=>'yesno','enabled'=>1),
	'MAIN_GENERATE_SUPPLIER_PROPOSAL_HIDE_REF'=>array('type'=>'yesno','enabled'=>1),
);

require_once('../../mmicommon/admin/mmisetup_1.inc.php');
