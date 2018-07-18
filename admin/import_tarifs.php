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
        $ext = substr($_FILES['f1']['name'], strrpos($_FILES['f1']['name'], '.')+1);
        
        //var_dump($_FILES['f1']);
        if ($ext == "xls" || $ext == "xlsx")
        {
            require_once PHPEXCEL_PATH.'/PHPExcel/IOFactory.php';
            $excel = PHPExcel_IOFactory::load($_FILES['f1']['tmp_name']);
            $writer = PHPExcel_IOFactory::createWriter($excel, 'CSV');
            $writer->setDelimiter(";");
            $writer->setEnclosure("");
            $writer->save("/tmp/converted.csv");
        }
        
        if(is_file("/tmp/converted.csv")) $f1 = fopen("/tmp/converted.csv",'r');
        
        //var_dump($_FILES['f1'], is_file("/tmp/converted.csv")); exit;
        //unlink("/tmp/converted.csv");
        $TTransporteur = array();
        $TTranches = array();
        $i = 0;
        
        // vide les paliers
        $sql = 'DELETE FROM '.MAIN_DB_PREFIX.'c_paliers_transporteurs';
        $resql = $db->query($sql);
        
        while($ligne = fgetcsv($f1,4096,';', '"') ) {
            if($i > 0)
            {
                // var_dump($ligne); exit;
                $transport = $ligne[0];
                //if ($transport !== 'DACHSER') break;
                // créer le transporteur
                if(!in_array($transport, array_keys($TTransporteur)))
                {
                    $sql = "SELECT rowid, active FROM ".MAIN_DB_PREFIX."c_shipment_mode WHERE code = '".$transport."'";
                    $res = $db->query($sql);
                    if ($res)
                    {
                        if($db->num_rows($res)) {
                            $obj = $db->fetch_object($res);
                            $TTransporteur[$transport] = $obj->rowid;
                            if(empty((int)$obj->active))
                            {
                                $sql2 = "UPDATE ".MAIN_DB_PREFIX."c_shipment_mode SET active=1 WHERE rowid=".$obj->rowid;
                                $res2 = $db->query($sql2);
                            }
                        } else {
                            $sql2 = "INSERT INTO ".MAIN_DB_PREFIX."c_shipment_mode (code, libelle, description, tracking, active) VALUES ('$transport', '$transport', '$transport', '', 1)";
                            $res2 = $db->query($sql2);
                            if ($res2) $TTransporteur[$transport] = $db->last_insert_id(MAIN_DB_PREFIX."c_shipment_mode");
                            // 		                    else var_dump($db->lasterror);
                        }
                    }
                }
                
                $pays = $ligne[1];
                $dept = $ligne[2];
                $timbre = (float) trim($ligne[43]);
                $loc = $ligne[44];
                
                $localite = '';
                if($pays == "FR")
                {
                    if($loc == "AUTRES LOCALITES") $localite = "0";
                    else $localite = $dept."000";
                }
                
                $id = $transport.'-'.$pays.'-'.$dept.'-'.(($localite !== "0") ? $localite.'-' : '').'0';
                if (!empty($timbre)) $TData[$id] = array($transport, $pays, $dept, 0, $timbre, $localite);
                $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_paliers_transporteurs WHERE fk_trans = ".$TTransporteur[$transport]." AND poids = 0";
                $res = $db->query($sql);
                if ($res)
                {
                    if(!$db->num_rows($res))
                    {
                        $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_paliers_transporteurs (fk_trans, poids, active) VALUES (".$TTransporteur[$transport].", 0, 1)";
                        $ret = $db->query($sql);
                    }
                }
                for($j = 0; $j < 20; $j++){
                    $index = $j+3;
                    
                    // créer les paliers
                    $palier = $transport.'-'.(int)$ligne[$index];
                    if(!in_array($palier, array_keys($TTranches)))
                    {
                        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_paliers_transporteurs WHERE fk_trans = ".$TTransporteur[$transport]." AND poids = " . (int)$ligne[$index];
                        $res = $db->query($sql);
                        if ($res)
                        {
                            if(!$db->num_rows($res))
                            {
                                $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_paliers_transporteurs (fk_trans, poids, active) VALUES (".$TTransporteur[$transport].", '".((int)$ligne[$index])."', 1)";
                                $ret = $db->query($sql);
                            }
                        }
                    }
                    
                    
                    $id = $transport.'-'.$pays.'-'.$dept.'-'.(($localite !== "0") ? $localite.'-' : '').(int)$ligne[$index];
                    //var_dump((int)$ligne[$index]); exit;
                    if (empty($TData[$id])) {
                        $TData[$id] = array($transport, $pays, $dept, (int)$ligne[$index], (float)$ligne[$index+20], $localite);
                    }
                    
                }
            }
            $i++;
            
        }
        if(is_file("/tmp/converted.csv")) unlink("/tmp/converted.csv");
        //var_dump($TData); exit;
        
    }
    else if($_REQUEST['bt_import'] && !empty($_REQUEST['data'])) {
        
        // vider le dictionnaire des tarifs
        $sql = 'DELETE FROM '.MAIN_DB_PREFIX.'c_tarifs_transporteurs';
        $resql = $db->query($sql);
        
        $TData = unserialize($_REQUEST['data']);
        $TTransporteur = unserialize($_REQUEST['transport']);
        $etape = 3;
        
        $TCountry = array();
        $sql = 'SELECT rowid, code FROM '.MAIN_DB_PREFIX.'c_country';
        $resql = $db->query($sql);
        if ($resql){
            while ($obj = $db->fetch_object($resql)) $TCountry[$obj->code] = $obj->rowid;
        }
        
        $TPaliers = array();
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_paliers_transporteurs WHERE active = 1";
        $res = $db->query($sql);
        if ($res){
            while ($obj = $db->fetch_object($res)) $TPaliers[$obj->fk_trans.'-'.$obj->poids] = $obj->rowid;
        }
        
        foreach($TData as $k => &$data) {
            
//             if ($TTransporteur[$data[0]] !== '23') break;
            $data['ok'] = 1;
            if ($data[1] == 'PB') $data[1] = "NL";
            $fk_palier = $TPaliers[$TTransporteur[$data[0]].'-'.$data[3]];
            
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_tarifs_transporteurs (rowid, fk_palier, fk_pays, departement, zipcode, tarif, active) VALUES (NULL, '".$fk_palier."', '".$TCountry[$data[1]]."', '".$data[2]."', '".$data[5]."', '".$data[4]."', '1');";
            $resql = $db->query($sql);
            if(! $resql) {
                var_dump($fk_palier, $data[0].'-'.$data[3]);
                print $k.' : '.$db->lasterror.'<br>';exit;
            }
            // 			$o = new TFraisDePort;
            // 			$o->zip = str_pad($data[0],2,'0',STR_PAD_LEFT);
            // 			$o->type = $data[1]>0 ? 'WEIGHT' : 'AMOUNT';
            // 			$o->palier = $data[1]>0 ? $data[1] : $data[2];
            // 			$o->fdp = price2num($data[3],5);
            // 			$o->save($PDOdb);
            
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
        'Transport',
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
<br /><small>(Colonnes : nom transporteur, pays, n° département, "D" à "W" : poids, "X" à "AQ" : montants correspondant, "AR" : tarif timbre, "AS": localité - séparateur : ';')</small> 
<!--<br /><label ><input type="checkbox" name="clearamount" value="1" <?php echo !empty($_REQUEST['clearamount'])?'checked':'' ?> /> <?php $langs->trans("DelAmountBefortInport") ?> Supprimer les montants avant import</label>
<br /><label ><input type="checkbox" name="clearweight" value="1" <?php echo !empty($_REQUEST['clearweight'])?'checked':'' ?> /> <?php $langs->trans("DelAmountWeightInport") ?> Supprimer les poids avant import</label>
-->

<?php

if($etape>1) {
	print_titre('Etape 2');
	
	echo $form->zonetexte('', 'data', serialize($TData), 80,5, ' style="display:none;" ');
	echo $form->zonetexte('', 'transport', serialize($TTransporteur), 80,5, ' style="display:none;" ');
	?>
	<table class="liste">
		<tr class="liste_titre">
			<td>Transporteur</td>
			<td>Pays</td>
			<td>Département</td>
			<td>Code postal</td>
			<td>Poids</td>
			<td>Montant</td>
		</tr>
		
	
	<?php
	
	foreach($TData as &$data) {
		
		?>
		<tr class="pair" <?php if(!empty($data['ok'])) echo ' style="background-color:green;" ' ?>>
			<td><?php echo $data[0] ?></td>
			<td><?php echo $data[1] ?></td>
			<td><?php echo $data[2] ?></td>
			<td><?php echo $data[5] ?></td>
			<td><?php echo $data[3] ?></td>
			<td><?php echo $data[4] ?></td>
		</tr>
		<?php
		
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
