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

$TTransport = array();
$sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."c_shipment_mode WHERE active = 1";
$res = $db->query($sql);
if($res)
{
    while($obj = $db->fetch_object($res)) $TTransport[$obj->rowid] = $obj->libelle;
}

if(count($TTransport))
{
    foreach ($TTransport as $tid => $label)
    {
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."c_grilles_transporteurs WHERE fk_trans = ".$tid;
        $res = $db->query($sql);
        if($res)
        {
            if($db->num_rows($res)) { 
                print_titre($label);
                
            }
        }
    }
}

print_titre("methode");

?>

<table class="noborder" width="100%">
	<tr class="liste_titre">
		<td align="left">Pays</td>
		<td align="center">Département</td>
		<td align="center">Code postal</td>
		<td align="center">Timbre</td>
<!-- 		<td align="center">de [palier.lastMontant; block=td] &euro; à [palier.montant;strconv=no] &euro; [palier.toDelete;strconv=no]</td> -->
		<td><input type="text" name="newPalier[[view.contrat]]" value="" size="10" /> kg</td>
	</tr>
	<tr class="oddeven">
		<td align="left">
			<input type="text" class="flat" name="TPeriode[[view.contrat]][[coefficient.#]]" size="3" value="[coefficient.$; block=tr;strconv=no;sub1]" />
			
		</td>
		<td align="center">
			<input type="hidden" name="TCoeff[[view.contrat]][[coefficient.#]][[coefficient_sub1.#]][rowid]" value="[coefficient_sub1.rowid; block=td]" />
			<input type="text" class="flat" name="TCoeff[[view.contrat]][[coefficient.#]][[coefficient_sub1.#]][coeff]" size="5" value="[coefficient_sub1.coeff;]" />
		</td>
		
		<td>
			<input type="text" class="flat" name="TCoeff[[view.contrat]][[coefficient.#]][[coefficient_sub1.#]][coeff]" size="5" value="" />
		</td>
		<!--<td><input type="text" name="TNewCoeff[[coefficient.$]]" size="5" value="" /> %[onshow;block=td;when [view.mode]=='edit']</td> -->
	</tr>
	<tr class="oddeven"><td></td><td colspan="2"></td></tr>
	<tr class="oddeven">
		<td><?php print $form->select_country('', 'country_id') ?></td>
		<td colspan='2' align="left"> <input type="text" class="flat" name="newPeriode[[view.contrat]]" size="3" value="" /></td>
	</tr>
	
</table>

<div class="tabsAction">
<input type="submit" name="save" value="Enregistrer" class="button" />
</div>

<?php 
print $form->selectarray('transports', $TTransport, '', 1);
?>
