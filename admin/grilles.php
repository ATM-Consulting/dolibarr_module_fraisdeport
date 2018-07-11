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

/**
 * Actions
 */
if ($action == "addpalier")
{
//     var_dump($newPalier);
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
                //             $TTarifs[$obj->fk_palier]['pays'] = $obj->fk_pays;
                //             $TTarifs[$obj->fk_palier]['dpt'] = $obj->departement;
                //             $TTarifs[$obj->fk_palier]['zip'] = $obj->zipcode;
                //             $TTarifs[$obj->fk_palier]['tarif'] = $obj->tarif;
                $TTarifs[$obj->fk_pays][$obj->departement][$obj->zipcode][$obj->fk_palier] = $obj->tarif;
            }

        }
//         var_dump($TTarifs); exit;

        if(count($TTranches))
        {
            
            foreach ($TTranches['pays'] as $k => $country)
            {
                print_titre($label.' - '.$TCountry[$k]);
            ?>
            
            <table class="noborder" width="100%">
                <!-- entête -->
                <tr class="liste_titre">
            	<td align="left">Pays</td>
                <td align="center">Département</td>
            	<td align="center">Code postal</td>
            <?php
                $i = 0;
                $num = count($country);
                foreach ($country as $seuil){
//                     var_dump($country);
                    if((int)empty($TTranches['poids'][$seuil])) {
                        print '<td align="center">Timbre</td>';
                        print '<td align="center">';
                        $first = true;
                    }
                    else {
                        
                        if($first) {
                            print 'de '. $TTranches['poids'][$seuil] . ' à ';
                            $first = false;
                        } elseif ($i < ($num -1) ) {
                            print $TTranches['poids'][$seuil] . 'kg</td>';
                            print '<td align="center">de '. $TTranches['poids'][$seuil] . ' à ';
                        } elseif ($i == $num -1 ) {
                            print $TTranches['poids'][$seuil] . 'kg</td>';
                            print '<td align="center">plus de '. $TTranches['poids'][$seuil] . ' kg</td>';
                        }
                        
                    }
                    
                    $i++;
                }
                print '<td>';
                print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
                print '<input type="hidden" name="action" value="addpalier">';
                print '<input type="text" name="newPalier['.$tid.'-'.$k.']">'
                     .'<input type="submit" value="ajouter">';
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
            	           print '<td align="center">'.$prices[$tr].' €</td>';
            	       }
            	       
            	       print '</tr>';
            	   }
            	}
            	
            print '</table>';
            }
        }
        
