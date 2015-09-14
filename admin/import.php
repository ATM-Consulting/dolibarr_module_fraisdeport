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

$etape = 1;

if($action === 'import') {
	if(isset($_REQUEST['bt_preview']) && !empty($_FILES['f1'])  && $_FILES['f1']['error'] == 0) {
		$etape = 2;
		$TData=array();
		$f1 = fopen($_FILES['f1']['tmp_name'],'r') or die('Fichier illisible'); 
		while($ligne = fgetcsv($f1,4096,';', '"') ) {
			
			$TData[] = $ligne;
			
		}
		
	}	
	else if($_REQUEST['bt_import'] && !empty($_REQUEST['data'])) {
		$TData = unserialize($_REQUEST['data']);
		$etape = 3;
		
		foreach($TData as &$data) {
			
			$data['ok'] = 1;
			
			$o = new TFraisDePort;
			$o->zip = $data[0];
			$o->type = $data[1]>0 ? 'WEIGHT' : 'AMOUNT';
			$o->palier = $data[1]>0 ? $data[1] : $data[2];
			$o->fdp = price2num($data[3],5);
			$o->save($PDOdb);
			
		}
		
	}	
}




$page_name = "FraisDePortImport";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = fraisdeportAdminPrepareHead();
dol_fiche_head(
    $head,
    'import',
    $langs->trans("Module104150Name"),
    0,
    "fraisdeport@fraisdeport"
);

$form = new TFormCore('auto', 'formImport', 'post', true);
echo $form->hidden('action','import');

print_titre('Etape 1');

echo $form->fichier('Fichier à importer', 'f1', '', 50);
echo $form->btsubmit('Prévisualiser', 'bt_preview');
?>
<br /><small>(Colonnes : n° département,poids,palier,montant - séparateur : ';')</small>
<?php

if($etape>1) {
	print_titre('Etape 2');
	
	echo $form->zonetexte('', 'data', serialize($TData), 80,5, ' style="display:none;" ');
	
	?>
	<table class="liste">
		<tr class="liste_titre">
			<td>Département</td>
			<td>Poids</td>
			<td>ou Palier</td>
			<td>Montant</td>
		</tr>
		
	
	<?
	
	foreach($TData as &$data) {
		
		?>
		<tr class="pair" <?php if(!empty($data['ok'])) echo ' style="background-color:green;" ' ?>>
			<td><?php echo $data[0] ?></td>
			<td><?php echo $data[1] ?></td>
			<td><?php echo $data[2] ?></td>
			<td><?php echo $data[3] ?></td>
		</tr>
		<?
		
	}

	?>
	</table>
	<?php
	
	if($etape == 2)
		echo $form->btsubmit('Importer', 'bt_import');
	else 
		echo 'Import réalisé';
}



$form->end();

llxFooter();

$db->close();
