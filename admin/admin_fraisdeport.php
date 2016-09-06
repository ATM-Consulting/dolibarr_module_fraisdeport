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

// Libraries
dol_include_once('fraisdeport/lib/fraisdeport.lib.php');
dol_include_once('core/lib/admin.lib.php');

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
$action = GETPOST('action', 'alpha');

if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}
	
if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * View
 */
 
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


$form=new Form($db);
$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("fraisdeport_label_service_to_use").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_FRAIS_DE_PORT_ID_SERVICE_TO_USE">';
$form->select_produits($conf->global->FRAIS_DE_PORT_ID_SERVICE_TO_USE, 'FRAIS_DE_PORT_ID_SERVICE_TO_USE', 1, $conf->product->limit_size, $buyer->price_level, 1, 2, '', 1);
print '&nbsp;<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$form->textwithpicto($langs->trans("UseWeight"), $langs->trans("UseWeightInfo")).'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('FRAIS_DE_PORT_USE_WEIGHT');
print '</td></tr>';
print '</table>';

print '<script type="text/javascript">
	$(function() {
		/* do refresh to show or hide weight tab */
		$("#set_FRAIS_DE_PORT_USE_WEIGHT, #del_FRAIS_DE_PORT_USE_WEIGHT").click(function() {
			window.location.href=window.location.href;
		});
	});
</script>';


llxFooter();

$db->close();