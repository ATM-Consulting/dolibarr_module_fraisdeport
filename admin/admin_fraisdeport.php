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

switch ($action) {
    case 'save_weight':
        
        $TPallierWeight = GETPOST('TPallierWeight');
        
        foreach($TPallierWeight as $id=>$fdp) {
            
			$o=new TFraisDePort;
			if($id>0) $o->load($PDOdb, $id);
			
			$o->set_values($fdp);
			$o->type='WEIGHT';
			
			if(!empty($o->fdp)) {
				$o->save($PDOdb);	
			}
			else{
				$o->delete($PDOdb);
			}
        }
        
        
        setEventMessage($langs->trans('FDPSaved'));
        
        break;
    case 'save_amount':
        
        $TPallierAmount = GETPOST('TPallierAmount');
       
        foreach($TPallierAmount as $id=>$fdp) {
           $o=new TFraisDePort;
			if($id>0) $o->load($PDOdb, $id);
			
			$o->set_values($fdp);
			
			$o->type='AMOUNT';
			
			if(!empty($o->fdp)) {
				$o->save($PDOdb);	
			}
			else{
				$o->delete($PDOdb);
			}
			
        }
        
        setEventMessage($langs->trans('FDPSaved'));
        
        break;
	case 'save':
		$TPallier = $_REQUEST['TPallier'];
		
        $TDivers = isset($_REQUEST['TDivers']) ? $_REQUEST['TDivers'] : array();
        
        foreach($TDivers as $name=>$param) {
        
            dolibarr_set_const($db, $name, $param);
            
        }
        if(!empty($TDivers)) setEventMessage( $langs->trans('RegisterSuccess') );
        
        
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
/*
print '<form name="formFraisDePortLevel" method="POST" action="'.dol_buildpath('/fraisdeport/admin/admin_fraisdeport.php', 2).'" />';

print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<td width="20%">'.$langs->trans('EurosPallier').'</td>';
print '<td>'.$langs->trans('TarifFraisDePort').'</td>';
print '</tr>';

print '<input type="hidden" name="action" value="save_amount" />';

$i = 0;
$TFraisDePortAmount = TFraisDePort::getAll($PDOdb,'AMOUNT');

$TClass=array(1=>'pair', -1=>'impair' ); $class = 1;

if(is_array($TFraisDePortAmount) && count($TFraisDePortAmount) > 0) {
            
            foreach($TFraisDePortAmount as $i => $fdp) {
               print '<tr class="'.$TClass[$class].'">';
                
                print '<td><input type="text" name="TPallierAmount['.$fdp->getId().'][palier]" value="'.$fdp->palier.'" />'.$conf->global->currency.'</td>';
                print '<td><input type="text" name="TPallierAmount['.$fdp->getId().'][fdp]" value="'.$fdp->fdp.'" /></td>';
                print '</tr>';
                $class = -$class;
            }   
            
        }
        
        print '<tr class="'.$TClass[$class].'">';
        
        print '<td><input type="text" name="TPallierAmount[0][palier]" />'.$conf->global->currency.'</td>';
        print '<td><input type="text" name="TPallierAmount[0][fdp]" /></td>';
        
        print '</tr>';

print '</table>';

print '<div class="tabsAction"><input class="butAction" type="SUBMIT" name="subSaveFDP" value="'.$langs->trans('SaveFDP').'" /></div>';

print '</form>';

if($conf->global->FRAIS_DE_PORT_USE_WEIGHT) {
        print '<form name="formFraisDePortLevel2" method="POST" action="'.$_SERVER['PHP_SELF'].'" />';
        print '<input type="hidden" name="action" value="save_weight" />';
        print '<table class="noborder">';
        print '<tr class="liste_titre">';
        print '<td width="20%">'.$langs->trans('WeightPallier').'</td>';
        print '<td width="20%">'.$langs->trans('Zip').'</td>';
        print '<td>'.$langs->trans('TarifFraisDePort').'</td>';
		 print '<td>'.$langs->trans('ShipmentMode').'</td>';
        print '</tr>';
        
		$TFraisDePortWeight = TFraisDePort::getAll($PDOdb,'WEIGHT');
		$f=new Form($db);
        if(is_array($TFraisDePortWeight) && count($TFraisDePortWeight) > 0) {
            
            foreach($TFraisDePortWeight as $i => $fdp) {
               print '<tr class="'.$TClass[$class].'">';
                
                print '<td><input type="text" name="TPallierWeight['.$fdp->getId().'][palier]" value="'.$fdp->palier.'" />Kg</td>';
                print '<td><input type="text" name="TPallierWeight['.$fdp->getId().'][zip]" value="'.$fdp->zip.'" /></td>';
                
                print '<td><input type="text" name="TPallierWeight['.$fdp->getId().'][fdp]" value="'.$fdp->fdp.'" /></td>';
				
				print '<td>';
				$f->selectShippingMethod($fdp->fk_shipment_mode, 'TPallierWeight['.$fdp->getId().'][fk_shipment_mode]', '', 1);
				
                print '</td></tr>';
                $class = -$class;
            }   
            
        }
        
        print '<tr class="'.$TClass[$class].'">';
        
        print '<td><input type="text" name="TPallierWeight[0][palier]" />Kg</td>';
        print '<td><input type="text" name="TPallierWeight[0][zip]" /></td>';
        print '<td><input type="text" name="TPallierWeight[0][fdp]" /></td>';
        print '<td>';
				$f->selectShippingMethod(-1, 'TPallierWeight[0][fk_shipment_mode]', '', 1);
				
        print '</td></tr>';
        
        print '</table>';
        
        print '<div class="tabsAction"><input class="butAction" type="SUBMIT" name="subSaveFDP" value="'.$langs->trans('SaveFDP').'" /></div>';
        
        print '</form>';

}
*/        

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
            
             ?><a href="?action=save&TDivers[FRAIS_DE_PORT_USE_WEIGHT]=1"><?=img_picto($langs->trans("Disabled"),'switch_off'); ?></a><?php
            
        }
        else {
             ?><a href="?action=save&TDivers[FRAIS_DE_PORT_USE_WEIGHT]=0"><?=img_picto($langs->trans("Activated"),'switch_on'); ?></a><?php
            
        }
    
    ?></td>             
</tr>
</table><?

llxFooter();

$db->close();