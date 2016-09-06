<?php
/* Module de gestion des frais de port
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

require('../config.php');
dol_include_once('/fraisdeport/class/fraisdeport.class.php');

$PDOdb=new TPDOdb;

global $db;

// Libraries
//dol_include_once("fraisdeport/core/lib/admin.lib.php");
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

switch ($action) {
   
		
	case 'saveIDServiceToUse':
		if(_saveIDServiceToUse($db, $_REQUEST['idservice'])) {
			
			setEventMessage($langs->trans('IDServiceSaved'));
			
		} else {
			
			setEventMessage($langs->trans('IDServiceNotSaved'), 'errors');
			
		}
		
		break;
		
	case 'save':
		$TDivers = isset($_REQUEST['TDivers']) ? $_REQUEST['TDivers'] : array();
        
        foreach($TDivers as $name=>$param) {
        
            dolibarr_set_const($db, $name, $param);
            
        }
        if(!empty($TDivers)) setEventMessage( $langs->trans('RegisterSuccess') );
		break;
	
	default:
		
		break;
}
 
/*
 * View
 */ 

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


function _saveIDServiceToUse($db, $idservice_to_use) {
	
	if(!empty($idservice_to_use)) {
		
		dolibarr_set_const($db, 'FRAIS_DE_PORT_ID_SERVICE_TO_USE', $idservice_to_use);
		return true;
		
	}
	
	return false;
	
}
   

print '<form name="formIDServiceToUse" method="POST" action="" />';

$form = new Form($db);

$form->select_produits(dolibarr_get_const($db, 'FRAIS_DE_PORT_ID_SERVICE_TO_USE'),'idservice',1,$conf->product->limit_size,$buyer->price_level);

print '<input type="hidden" name="action" value="saveIDServiceToUse" />';

print '<input type="SUBMIT" name="subIDServiceToUse" value="Utiliser ce service" />';

print '</form>';

?>
<br />
<table width="100%" class="noborder" style="background-color: #fff;">
    <tr class="liste_titre">
        <td colspan="2"><?php echo $langs->trans('Parameters') ?></td>
    </tr>
<tr>
    <td><?php echo $langs->trans('UseWeight') ?></td><td><?php
    
        if($conf->global->FRAIS_DE_PORT_USE_WEIGHT==0) {
            
             ?><a href="?action=save&TDivers[FRAIS_DE_PORT_USE_WEIGHT]=1"><?php echo img_picto($langs->trans("Disabled"),'switch_off'); ?></a><?php
            
        }
        else {
        	
             ?><a href="?action=save&TDivers[FRAIS_DE_PORT_USE_WEIGHT]=0"><?php echo img_picto($langs->trans("Activated"),'switch_on'); ?></a><?php
            
        }
    
    ?></td>             
</tr>
</table><?php

$db->close();

llxFooter();
