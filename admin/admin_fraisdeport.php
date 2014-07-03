<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/mymodule.php
 * 	\ingroup	mymodule
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
    $res = @include("../../../main.inc.php"); // From "custom" directory
}

global $db;

// Libraries
dol_include_once("fraisdeport/core/lib/admin.lib.php");
dol_include_once('fraisdeport/lib/fraisdeport.lib.php');
dol_include_once('core/lib/admin.lib.php');
//require_once "../class/myclass.class.php";
// Translations
$langs->load("fraisdeport@fraisdeport");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */

$action = $_REQUEST['action'];

/*echo "<pre>";
print_r($_REQUEST);
echo "</pre>";
exit;*/

switch ($action) {
	case 'save':
		$TPallier = $_REQUEST['TPallier'];
		$TFpd = $_REQUEST['TFdp'];
		if(_saveFDP($db, $TPallier, $TFpd)) {
			
			setEventMessage($langs->trans('FDPSaved'));
			
		}
		break;
		
	case 'saveIDServiceToUse':
		if(_saveIDServiceToUse($db, $_REQUEST['idservice'])) {
			
			setEventMessage($langs->trans('IDServiceSaved'));
			
		} else {
			
			setEventMessage($langs->trans('IDServiceNotSaved'), 'errors');
			
		}
		
		break;
	
	default:
		
		break;
}
 
/*
 * View
 */ 

$TFraisDePort = unserialize(dolibarr_get_const($db, 'FRAIS_DE_PORT_ARRAY'));
//print_r($TFraisDePort);
 
$page_name = "FraisDePortSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = fraisdeportAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104150Name"),
    0,
    "fraisdeport@fraisdeport"
);

// Setup page goes here
//echo $langs->trans("FraisDePortSetup");

function _saveFDP(&$db, $TPallier, $TFpd) {
	
	$i = 0;
	$TFraisDePort = array();
	
	while($i < count($TPallier)) {
		
		if(!empty($TPallier[$i]) && !empty($TFpd[$i]) && is_numeric($TPallier[$i]) && is_numeric($TFpd[$i])) {

			$TFraisDePort[$TPallier[$i]] = $TFpd[$i];

		}
		
		$i++;
		
	}
	
	return dolibarr_set_const($db, 'FRAIS_DE_PORT_ARRAY', serialize($TFraisDePort));
	
}

function _saveIDServiceToUse($db, $idservice_to_use) {
	
	if(!empty($idservice_to_use)) {
		
		dolibarr_set_const($db, 'FRAIS_DE_PORT_ID_SERVICE_TO_USE', $idservice_to_use);
		return true;
		
	}
	
	return false;
	
}

print '<form name="formFraisDePortLevel" method="POST" action="'.dol_buildpath('/fraisdeport/admin/admin_fraisdeport.php', 2).'" />';
print '<table class="noborder" width="100%">';
	
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('EurosPallier').'</td>';
print '<td>'.$langs->trans('TarifFraisDePort').'</td>';
print '</tr>';

print '<input type="hidden" name="action" value="save" />';

$i = 0;

if(is_array($TFraisDePort) && count($TFraisDePort) > 0) {
	
	foreach($TFraisDePort as $pallier => $fdp) {
		print '<tr>';
		
		$pallier = array_keys($TFraisDePort);

		print '<td><input type="text" name="TPallier['.$i.']" value="'.$pallier[$i].'" /></td>';
		print '<td><input type="text" name="TFdp['.$i.']" value="'.$TFraisDePort[$pallier[$i]].'" /></td>';
		print '</tr>';
		$i++;
	}	
	
}

print '<tr>';

print '<td><input type="text" name="TPallier['.$i.']" /></td>';
print '<td><input type="text" name="TFdp['.$i.']" /></td>';

print '</tr>';

print '</table>';

print '<div class="tabsAction"><input class="butAction" type="SUBMIT" name="subSaveFDP" value="'.$langs->trans('SaveFDP').'" /></div>';

print '</form>';

print '<form name="formIDServiceToUse" method="POST" action="" />';

$form = new Form($db);

$form->select_produits(dolibarr_get_const($db, 'FRAIS_DE_PORT_ID_SERVICE_TO_USE'),'idservice',1,$conf->product->limit_size,$buyer->price_level);

print '<input type="hidden" name="action" value="saveIDServiceToUse" />';

print '<input type="SUBMIT" name="subIDServiceToUse" value="Utiliser ce service" />';

print '</form>';

llxFooter();

$db->close();