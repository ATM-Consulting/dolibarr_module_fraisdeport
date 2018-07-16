<?php
require('../config.php');
dol_include_once('/core/class/html.form.class.php');
dol_include_once('/core/class/html.formcompany.class.php');

dol_include_once('/fraisdeport/class/fraisdeport.class.php');

$PDOdb=new TPDOdb;
$form = new Form($db);
$formcompany = new FormCompany($db);

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
$newPalier = GETPOST('newPalier', 'array');
$paliers = GETPOST('paliers', 'array');
$fk_palier = GETPOST('fk_palier');
$fk_trans = GETPOST('transport', 'int');
$fk_pays = GETPOST('pays', 'int');
$departement = GETPOST('dpt');
$zipcode = GETPOST('zip');
$pricestoUpdate = GETPOST('pricesToUpdate', 'array');
$prices = GETPOST('prices', 'array');
$line_state = GETPOST('line_state', 'int');
$line_zip = GETPOST('line_zip');
$line_prices = GETPOST('line_prices', 'array');
$addline = GETPOST('addline');
$confirm = GETPOST('confirm');

/**
 * Actions
 */
if ($action == "update")
{
//     echo '<pre>'; print_r($_POST);
//     exit;
//     if($addline)
//     {
//     var_dump($_POST);
    if(!empty($line_state))
    {
        if (empty($line_zip)) $line_zip = 0;
        foreach ($line_prices as $id_palier => $prix)
        {
            if(empty($prix)) $prix = 0;
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_tarifs_transporteurs (fk_palier, fk_pays, departement, zipcode,tarif, active) VALUES (".$id_palier.",".$fk_pays.",".(int)$line_state.",'".$line_zip."',".(float)$prix.", 1)";
            $db->query($sql);
        }
    }
//     }
    
    foreach ($newPalier as $id => $palier)
    {
        if ($palier !== ''){
            $tmp = explode('-', $id);
            $trans = $tmp[0];
            $pays = $tmp[1];
            $error = 0;
            $db->begin();
            
            $sql = "SELECT p.rowid FROM ".MAIN_DB_PREFIX."c_paliers_transporteurs as p";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_tarifs_transporteurs as t on t.fk_palier = p.rowid";
            $sql.= " WHERE p.fk_trans = ". $trans;
            $sql.= " AND t.fk_pays = ".$pays;
            $sql.= " AND p.poids = ".$palier;
            $res = $db->query($sql);
            if ($res){
                
                $num = $db->num_rows($res);
                if($num){
                    
                    $obj = $db->query($sql);
                    $fk_palier = $obj->rowid;
                } else {
                    
                    $sqlInsert = "INSERT INTO ".MAIN_DB_PREFIX."c_paliers_transporteurs (fk_trans, poids, active) VALUES ('".$trans."','".$palier."', 1)";
                    $resinsert = $db->query($sqlInsert);
                    
                    if(!$resinsert){
                        
                        $db->rollback();
                        print "erreur ";
                        print $db->lasterror;
                        $error++;
                    }
                    else {
                        $fk_palier = $db->last_insert_id(MAIN_DB_PREFIX.'c_paliers_transporteurs');
                    }
                }
                
            }
            
            if(!$error){
                
                $sqlzone = "SELECT DISTINCT t.departement, t.zipcode FROM ".MAIN_DB_PREFIX."c_tarifs_transporteurs as t";
                $sqlzone.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paliers_transporteurs as p ON p.rowid = t.fk_palier";
                $sqlzone.= " WHERE t.fk_pays = " . $pays;
                $sqlzone.= " AND p.fk_trans = " . $fk_trans;
                $reszone = $db->query($sqlzone);
                if ($reszone)
                {
                    while ($obj = $db->fetch_object($reszone))
                    {
                        $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_tarifs_transporteurs (fk_palier, fk_pays, departement, zipcode, tarif, active) VALUES ('".$fk_palier."','".$pays."','".$obj->departement."','".$obj->zipcode."', 0, 1)";
                        $resql = $db->query($sql);
                    }
                }
            }
            $db->commit();
        }
        
    }
    
    if(!empty($paliers))
    {
        $sql = "SELECT rowid, poids FROM ".MAIN_DB_PREFIX."c_paliers_transporteurs WHERE rowid IN ('".implode("','", array_keys($paliers[$fk_trans]))."')";
        $res = $db->query($sql);
        $toUpdate = array();
        if($res){
            while($obj = $db->fetch_object($res))
            {
                if ((int)$paliers[$fk_trans][$obj->rowid] !== (int) $obj->poids)
                    $toUpdate[] = $obj->rowid;
            }
        }
        
        foreach ($paliers[$fk_trans] as $palid => $val)
        {
            if(in_array($palid, $toUpdate))
            {
                $sql = "UPDATE ".MAIN_DB_PREFIX."c_paliers_transporteurs";
                $sql.= " SET poids = ".$val;
                $sql.= " WHERE rowid=".$palid;
                $res = $db->query($sql);
            }
        }
    }
    
    if(!empty($prices))
    {
//         var_dump($pricestoUpdate);
        foreach ($prices as $id => $val)
        {
//             print $id.' - '.$prices[$id].'<br>';
            $sql = "UPDATE ".MAIN_DB_PREFIX."c_tarifs_transporteurs";
            $sql.= " SET tarif = '".$val."'";
            $sql.= " WHERE rowid=".$id;
            $res = $db->query($sql);
        }
    }
    
    
}

if ($action == "confirmdelpalier" && $confirm == "yes")
{
    
//     var_dump($fk_palier);
    if(!empty($fk_palier)){
        $error = 0;
        $db->begin();
        
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."c_tarifs_transporteurs WHERE fk_palier = ".$fk_palier;
        $res = $db->query($sql);
        if(!$res) $error++;
        
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."c_paliers_transporteurs WHERE rowid = ".$fk_palier;
        $res = $db->query($sql);
        if(!$res) $error++;
        
        if($error) $db->rollback();
        else $db->commit();
    }
    
}

// delligne&transport='.$tid.'&pays='.$k.'&dpt='.$dpt.'&zip='.$code.'
if ($action == 'confirmdelligne' && $confirm == "yes")
{
    $sql = "SELECT t.rowid FROM ".MAIN_DB_PREFIX."c_tarifs_transporteurs as t";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paliers_transporteurs as p ON p.rowid = t.fk_palier";
    $sql.= " WHERE p.fk_trans=".$fk_trans;
    $sql.= " AND t.fk_pays=".$fk_pays;
    $sql.= " AND t.departement=".$departement;
    $sql.= " AND t.zipcode=".$zipcode;
    
    $tarifs = $PDOdb->ExecuteAsArray($sql);
    $Trowid =array();
    foreach ($tarifs as $result)
    {
        $Trowid[] = $result->rowid;
    }
    
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."c_tarifs_transporteurs";
    $sql.= " WHERE rowid IN ('".implode("','",$Trowid)."')";
    $db->query($sql);
}

$page_name = "Grilles transport";
llxHeader('', $langs->trans($page_name));
if($action == "delpalier")
{ 
    print $form->formconfirm($_SERVER["PHP_SELF"] . "?fk_palier=".$fk_palier, 'Suppression de la tranche', "Souhaitez-vous réellement supprimer le palier de ".(int)$poids." kg ?", 'confirmdelpalier', '', 0, 1);
}
if($action == "delligne")
{
    print $form->formconfirm($_SERVER["PHP_SELF"] . "?transport=".$fk_trans."&pays=".$fk_pays."&dpt=".$departement."&zip=".$zipcode, 'Suppression de ligne tarifaire', "Souhaitez-vous réellement supprimer la ligne ", 'confirmdelligne', '', 0, 1);
}

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
    
print_fiche_titre($langs->trans($page_name), $linkback);
    
// Configuration header
$head = fraisdeportAdminPrepareHead();
dol_fiche_head(
    $head,
    'GrillesTransport',
    $langs->trans("Module104150Name"),
    0,
    "fraisdeport@fraisdeport"
    );

$TCountry = array();
$sql = 'SELECT rowid, label FROM '.MAIN_DB_PREFIX.'c_country';
$resql = $db->query($sql);
if ($resql){
    while ($obj = $db->fetch_object($resql)) $TCountry[$obj->rowid] = $obj->label;
}

$TTransport = array();
$sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."c_shipment_mode WHERE active = 1 and rowid > 3";
$res = $db->query($sql);
if($res)
{
    while($obj = $db->fetch_object($res)) $TTransport[$obj->rowid] = $obj->libelle;
}
?>
<script>
    $(document).on('click', '.prixpalier', function(e){
        name = $(this).attr('data-name');
        val = $(this).html();
        parent = $(this).parent();
        parent.html('<input size="6" type="text" name="'+name+'" value="'+val+'">');
        parent.find('input').focus();
    });
    
    $(document).on('click', '.add', function(e){
        e.preventDefault();
        $(this).prev().attr('checked', true).closest('form').submit();
    });
</script>
<?php

if(count($TTransport))
{
    foreach ($TTransport as $tid => $label)
    {
        // récupérer toutes les tranches de poids du transporteur
        $sql = "SELECT DISTINCT p.rowid, t.fk_pays, p.poids FROM ".MAIN_DB_PREFIX."c_paliers_transporteurs as p";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_tarifs_transporteurs as t ON t.fk_palier = p.rowid";
        $sql.= " WHERE p.fk_trans = ".$tid." ORDER BY t.fk_pays, p.poids ASC";
//          var_dump($sql);
        $res = $db->query($sql);
        if($res)
        {
            $TTranches = array();
            while($obj = $db->fetch_object($res))
            {
                $TTranches['poids'][$obj->rowid] = $obj->poids;
                $TTranches['pays'][$obj->fk_pays][] = $obj->rowid;
            }
//             var_dump($TTranches); exit;
        }
        // pour chaque tranche récupérer les tarifs correspondants par pays/dpt/ville
        $TTarifs = array();
        
        // TODO modifier la requte pour suivre le nouveau format d'import 
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_tarifs_transporteurs WHERE fk_palier IN ('".implode("','", array_keys($TTranches['poids']))."') ORDER BY fk_pays, departement, zipcode ASC";
//          var_dump($sql);
        $res = $db->query($sql);
        if($res)
        {
            while($obj = $db->fetch_object($res)){
                $TTarifs[$obj->fk_pays][$obj->departement][$obj->zipcode][$obj->fk_palier] = $obj->tarif;
                $TTarifsId[$obj->fk_pays][$obj->departement][$obj->zipcode][$obj->fk_palier] = $obj->rowid;
            }

        }
//         var_dump($TTarifs); exit;

        if(count($TTranches))
        {
            
            foreach ($TTranches['pays'] as $k => $country)
            {
                print_titre($label.' - '.$TCountry[$k]);
//                 var_dump($TTranches['pays'][$k], $TTranches['poids']);//exit;
                print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
                print '<input type="hidden" name="action" value="update">';
                print '<input type="hidden" name="transport" value="'.$tid.'">';
                print '<input type="hidden" name="pays" value="'.$k.'">';
            ?>
            
            <table class="noborder" width="100%">
                <!-- entête -->
                <tr class="liste_titre">
                <?php 
                
                ?>
            	<td align="left">Pays</td>
                <td align="center">Département</td>
            	<td align="center">Code postal</td>
            <?php
                $i = 0;
                $num = count($country);
                foreach ($country as $seuil){
//                     var_dump($country);
                    if((int)empty($TTranches['poids'][$seuil])) {
                        print '<td align="center">Timbre';
                        print '<br>de <input type="text" name="paliers['.$tid.']['.$seuil.']" size="5" value="'. $TTranches['poids'][$seuil] . '"> à ';
                        
                        $first = true;
                    }
                    else {
                        
                        if($first) {
                            print $TTranches['poids'][$seuil] . 'kg <a href="?action=delpalier&fk_palier='.$country[$i-1].'&poids='.$TTranches['poids'][$country[$i-1]].'">'.img_delete('supprimer le palier').'</a></td>';
                            print '<td align="center">';
                            print 'de <input type="text" name="paliers['.$tid.']['.$seuil.']" size="5" value="'. $TTranches['poids'][$seuil] . '"> à ';
                            $first = false;
                        } elseif ($i < ($num - 1) ) {
                            print $TTranches['poids'][$seuil] . 'kg <a href="?action=delpalier&fk_palier='.$country[$i-1].'&poids='.$TTranches['poids'][$country[$i-1]].'">'.img_delete('supprimer le palier').'</a></td>';
                            print '<td align="center">de <input type="text" name="paliers['.$tid.']['.$seuil.']" size="5" value="'. $TTranches['poids'][$seuil] . '"> à ';
                        } elseif ($i == $num -1 ) {
                            print $TTranches['poids'][$seuil] . 'kg <a href="?action=delpalier&fk_palier='.$country[$i-1].'&poids='.$TTranches['poids'][$country[$i-1]].'">'.img_delete('supprimer le palier').'</a></td>';
                            print '<td align="center">plus de <input type="text" name="paliers['.$tid.']['.$seuil.']" size="5" value="'. $TTranches['poids'][$seuil] . '"> kg <a href="?action=delpalier&fk_palier='.$country[$i].'">'.img_delete('supprimer le palier').'</a></td>';
                        }
                        
                    }
                    
                    $i++;
                }
                print '<td>';
                print '<input type="text" name="newPalier['.$tid.'-'.$k.']">';
                print '</td>';
                print '</tr>';
                
        	    foreach ($TTarifs[$k] as $dpt => $zip)
            	{
            	   foreach ($zip as $code => $prices)
            	   {
            	       
            	       print '<tr>';
            	       print '<td align="left">'.$TCountry[$k].'</td>';
            	       print '<td align="center">'.$dpt.'</td>';
            	       print '<td align="center">'.((!empty($code)) ? $code : "").'</td>';
            	       foreach ($country as $tr){
            	           print '<td align="center">';
            	           //print '<input type="checkbox" name="pricesToUpdate['.$TTarifsId[$k][$dpt][$code][$tr].']" style="display:none"> ';
            	           //print '<input class="prixpalier" size="6" type="text" name="prices['.$TTarifsId[$k][$dpt][$code][$tr].']" value="'.(float)$prices[$tr].'"> €</td>';
            	           print '<span class="prixpalier" data-name="prices['.$TTarifsId[$k][$dpt][$code][$tr].']">'.(float)$prices[$tr].'</span>';
            	       }
            	       print '<td align="center"><a href="?action=delligne&transport='.$tid.'&pays='.$k.'&dpt='.$dpt.'&zip='.((empty($code)) ? 0 : $code).'">'.img_delete('supprimer la ligne').'</a></td>';
            	       print '</tr>';
            	   }
            	}
            	print '<tr><td colspan="'.($i+4).'">Ajouter une ligne</td><tr><tr class="liste_titre">';
            	
            	?>
<!--             	<td align="left">Pays</td> -->
                <td colspan="2" align="center">Département</td>
            	<td align="center">Code postal</td>
            <?php
                $i = 0;
                $num = count($country);
                foreach ($country as $seuil){
//                     var_dump($country);
                    if((int)empty($TTranches['poids'][$seuil])) {
                        print '<td align="center">Timbre';
                        print '<br>de '. $TTranches['poids'][$seuil] . ' à ';
                        
                        $first = true;
                    }
                    else {
                        
                        if($first) {
                            print $TTranches['poids'][$seuil] . 'kg </td>';
                            print '<td align="center">';
                            print 'de '. $TTranches['poids'][$seuil] . ' à ';
                            $first = false;
                        } elseif ($i < ($num - 1) ) {
                            print $TTranches['poids'][$seuil] . 'kg </td>';
                            print '<td align="center">de '. $TTranches['poids'][$seuil] . ' à ';
                        } elseif ($i == $num -1 ) {
                            print $TTranches['poids'][$seuil] . 'kg </td>';
                            print '<td align="center">plus de '. $TTranches['poids'][$seuil] . ' kg</td>';
                        }
                        
                    }
                    
                    $i++;
                }
                print '<td>';
            	print '<tr>';
            	//print '<td align="left">'.$TCountry[$k].'</td>';
//             	print '<td colspan="2" align="center">'.$formcompany->select_state('', $k, 'line_state').'</td>';
            	print '<td colspan="2" align="center"><input type="text" placeholder="departement" size="10" name="line_state"></td>';
            	print '<td align="center"><input type="text" placeholder="code postal" size="10" name="line_zip"></td>';
            	$i = 0;
            	foreach ($country as $tr){
            	    print '<td align="center"><input size="6" type="text"name="line_prices['.$country[$i].']"></td>';
            	    $i++;
            	}
            	print '<td align="center">';
            	print '<input type="checkbox" name="addline" style="display:none"> ';
            	print '<input class="add" type="submit" value="Ajouter une ligne"></td>';
            	print '<tr>';
            print '</table>';
            print '<div class="tabsAction">';
            print '<input type="submit" value="'.$langs->trans('Save').'">';
            print '</div>';
            print '</form>';
            }
        }

    }
    
}

