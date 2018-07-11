<?php
require('../config.php');
dol_include_once('/core/class/html.form.class.php');

dol_include_once('/fraisdeport/class/fraisdeport.class.php');

$PDOdb=new TPDOdb;
$form = new Form($db);

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
$fk_trans = GETPOST('transport', 'int');
$fk_pays = GETPOST('pays', 'int');
$toUpdate = GETPOST('pricesToUpdate', 'array');
$prices = GETPOST('prices', 'array');

/**
 * Actions
 */
if ($action == "update")
{
    var_dump($_POST);
    foreach ($newPalier as $id => $palier)
    {
        if (!empty($palier)){
            $tmp = explode('-', $id);
            $trans = $tmp[0];
            $pays = $tmp[1];
            
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
                    setEventMessage("Ce palier existe déjà !", "warning");
                    Header('location: '.$_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $sqlInsert = "INSERT INTO ".MAIN_DB_PREFIX."c_paliers_transporteurs (fk_trans, poids, active) VALUES ('".$trans."','".$palier."', 1)";
                    $resinsert = $db->query($sqlInsert);
                    if(!$resinsert){
                        $db->rollback();
                        print "erreur ";
                        print $db->lasterror;
                    }
                    else {
                        $fk_palier = $db->last_insert_id(MAIN_DB_PREFIX.'c_paliers_transporteurs');
                        
                        $sqlzone = "SELECT DISTINCT departement, zipcode FROM ".MAIN_DB_PREFIX."c_tarifs_transporteurs";
                        $sqlzone.= " WHERE fk_pays = " . $pays;
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
    
    if(!empty($toUpdate))
    {
        foreach ($toUpdate as $id => $dummy)
        {
            
        }
    }
    
    
}
if ($action == "delpalier")
{
    $fk_palier = GETPOST('fk_palier');
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

$page_name = "Grilles transport";
llxHeader('', $langs->trans($page_name));

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

print "<script>
        $(document).on('change', '.prixpalier', function(e){
            $(this).prev().attr('checked', true);
        });
       </script>";

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
                        print '<br>de '. $TTranches['poids'][$seuil] . ' à ';
                        
                        $first = true;
                    }
                    else {
                        
                        if($first) {
                            print $TTranches['poids'][$seuil] . 'kg <a href="?action=delpalier&fk_palier='.$country[$i-1].'">'.img_delete('supprimer le palier').'</a></td>';
                            print '<td align="center">';
                            print 'de <input type="text" name="paliers['.$tid.']['.$seuil.']" size="5" value="'. $TTranches['poids'][$seuil] . '"> à ';
                            $first = false;
                        } elseif ($i < ($num - 1) ) {
                            print $TTranches['poids'][$seuil] . 'kg <a href="?action=delpalier&fk_palier='.$country[$i-1].'">'.img_delete('supprimer le palier').'</a></td>';
                            print '<td align="center">de <input type="text" name="paliers['.$tid.']['.$seuil.']" size="5" value="'. $TTranches['poids'][$seuil] . '"> à ';
                        } elseif ($i == $num -1 ) {
                            print $TTranches['poids'][$seuil] . 'kg <a href="?action=delpalier&fk_palier='.$country[$i-1].'">'.img_delete('supprimer le palier').'</a></td>';
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
            	           print '<input type="checkbox" name="pricesToUpdate['.$TTarifsId[$k][$dpt][$code][$tr].']"> ';
            	           print '<input class="prixpalier" onchange="checkit('.$TTarifsId[$k][$dpt][$code][$tr].')" size="6" type="text" name="prices['.$TTarifsId[$k][$dpt][$code][$tr].']" value="'.$prices[$tr].'"> €</td>';
            	       }
            	       print '<td></td>';
            	       print '</tr>';
            	   }
            	}
            print '</table>';
            print '<div class="tabsAction">';
            print '<input type="submit" value="'.$langs->trans('Save').'">';
            print '</div>';
            print '</form>';
            }
        }
    }           	    
 
}

