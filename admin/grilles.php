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
                print '</tr>';
                
//                 var_dump($k, $TTarifs[$k]);
//             	exit;
//             	foreach ($TTarifs as $pays => $depts)
//             	{
            	    foreach ($TTarifs[$k] as $dpt => $zip)
                	{
                	   foreach ($zip as $code => $prices)
                	   {
                	       print '<tr>';
                	       //var_dump($prices);
                	       print '<td align="left">'.$TCountry[$k].'</td>';
                	       print '<td align="center">'.$dpt.'</td>';
                	       print '<td align="center">'.((!empty($code)) ? $code : "").'</td>';
                	       
                	       foreach ($prices as $id => $prix){
                	           print '<td align="center">'.$prix.' €</td>';
                	       }
                	       
                	       print '</tr>';
                	   }
                	}
//             	}
            	
            print '</table>';
            }
        }
        
//         $sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_grilles_transporteurs WHERE fk_trans = ".$tid;
//         $res = $db->query($sql);
//         if($res)
//         {
//             if($db->num_rows($res)) { 
                
//                 print_titre($label);
//                 $TData = array();
                
// //                 while ($obj = $db->fetch_object($res))
// //                 {
// //                     $TData[$obj->fk_pays][$obj->departement][$obj->zipcode][$obj->poids] = $obj->tarif;
// //                 }
                
                 
//                 	$pays = 0;
//                 	while ($obj = $db->fetch_object($res))
//                 	{
//                 	    $pays = $obj->fk_pays;
//                 	    $dpt = $obj->departement;
//                 	    $zip = $obj->zipcode;
//                 	    $TData[$obj->fk_pays][$obj->departement][$obj->zipcode][$obj->poids] = $obj->tarif;
//                 	}
//                 	$entete = false;
//                 	foreach ($TData as $pays => $depts)
//                 	{ 
//                 	    foreach ($depts as $dpt => $zip)
//                     	{ 
//                     	   foreach ($zip as $code => $prices)
//                     	   {
//                     	       if(!$entete)
//                     	       {
//                     	           ?>
<!--                 <table class="noborder" width="100%"> -->
<!--                 	<tr class="liste_titre"> -->
<!--                 		<td align="left">Pays</td> -->
<!--                 		<td align="center">Département</td> -->
<!--                 		<td align="center">Code postal</td> -->
<!--                 		<td align="center">Timbre</td> -->
                		<?php 
//                 		$oldpds = 0;
//                     	foreach ($prices as $pds => $prix){
//                     	    ?>
                    	    <!-- <td align="center"><?php //echo 'de '. $oldpds . ' à '; ?><input type="text" name="newPalier[[view.contrat]]" value="<?php //echo $pds ?>" size="5" />kg</td> -->
                    	<?php 
//                     	   $oldpds = $pds;
//                     	}?>
                <!-- 		<td align="center">de [palier.lastMontant; block=td] &euro; à [palier.montant;strconv=no] &euro; [palier.toDelete;strconv=no]</td> -->
<!--                 		<td><input type="text" name="newPalier[[view.contrat]]" value="" size="5" />&nbsp;kg</td> -->
<!--                 	</tr> -->
                	
                	<?php
//                     	           $entete = true;
//                     	       }
//                     	    ?>
<!--                 	    <tr class="oddeven"> -->
                	    
                    	    <td align="left"><?php //echo $TCountry[$pays] ?></td>
                    	    <td align="center"><?php //echo $dpt; ?></td>
                    	    <td align="center"><?php //echo (!empty($code)) ? $code : ""; ?></td>
<!--                     	    <td align="center">Timbre</td> -->
                    	    <?php 
//                     	    foreach ($prices as $pds => $prix){
//                     	    ?>
                    	    <!--<td align="center"><input type="text" name="newPalier[[view.contrat]]" value="<?php //echo $prix ?>" size="5" />&nbsp;€</td>-->
                    	    <?php }?>
                    	    <!-- 		<td align="center">de [palier.lastMontant; block=td] &euro; à [palier.montant;strconv=no] &euro; [palier.toDelete;strconv=no]</td> -->
<!--                     	    <td>&nbsp;</td> -->
                	    
<!--                 	    </tr> -->
                	    <?php 
//                     	   }
//                     	}
//                 	}
//                 	?>
                	
<!--                 </table> -->
                <?php
//             }
//         }
        
    //}
}


?>


<div class="tabsAction">
<input type="submit" name="save" value="Enregistrer" class="button" />
</div>

<?php 
print $form->selectarray('transports', $TTransport, '', 1);
?>
