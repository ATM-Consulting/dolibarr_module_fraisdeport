<?php


	require '../config.php';
	dol_include_once('/fraisdeport/lib/fraisdeport.lib.php');
	dol_include_once('/fraisdeport/class/fraisdeport.class.php');
	
	$langs->load("admin");
    $langs->load("deliveries");
	
	$type = GETPOST('type');
	if(empty($type)) $type = 'AMOUNT';
	
	$action = GETPOST('action');
	$fdp = new TFraisDePort;
	$PDOdb=new TPDOdb;
	
	switch ($action) {
		case 'save':
			
			if(GETPOST('bt_cancel')!='') {
				header('location:'.dol_buildpath('/fraisdeport/admin/fdp.php?type='.GETPOST('type'),1) );
			}
			else{
				$fdp->load($PDOdb, GETPOST('id'));
				$fdp->set_values($_POST);
				$fdp->save($PDOdb);		
				
				setEventMessage($langs->trans('FraisDePortSaved'));
				header('location:'.dol_buildpath('/fraisdeport/admin/fdp.php?type='.GETPOST('type').'&TListTBS[lPrice][orderBy][date_maj]=DESC',1) );
			}
		
		case 'edit':
			$fdp->load($PDOdb, GETPOST('id'));
			fiche($fdp, $type, 'edit');
			
			break;
		case 'new':
			
			fiche($fdp, $type, 'edit');
			
			break;
		default:
			liste($type);
				
			break;
	}
	
function fiche(&$fdp, $type, $mode) {
	global $conf, $langs, $db;
	
	$page_name = "FraisDePortSetup";
	llxHeader('', $langs->trans($page_name));	
	$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	    . $langs->trans("BackToModuleList") . '</a>';
	print_fiche_titre($langs->trans($page_name), $linkback);
	
	// Configuration header
	$head = fraisdeportAdminPrepareHead();
	dol_fiche_head(  $head,  $type,  $page_name,   0,   "fraisdeport@fraisdeport" );	
	$form = new TFormCore('auto', 'form1','post');
	$form->Set_typeaff($mode);
	
	echo $form->hidden('type', $type);
	echo $form->hidden('id', $fdp->getId());
	echo $form->hidden('action', 'save');
	
	$f=new Form($db);
	
	?>
	<table class="border" width="100%">
		<tr>
			<td  width="20%"><?php echo $langs->trans('Palier') ?></td><td><?php echo $form->texte('','palier', $fdp->palier, 10,255) ?></td>
		</tr>
		<tr>
			<td><?php echo $langs->trans('FraisDePort') ?></td><td><?php echo $form->texte('','fdp', $fdp->fdp, 10,255) ?></td>
		</tr>
		<tr>
			<td><?php echo $langs->trans('Zip') ?></td><td><?php echo $form->texte('','zip', $fdp->zip, 5,255) ?></td>
		</tr>
		<tr>
			<td><?php echo $langs->trans('ShipmentMode') ?></td><td><?php $f->selectShippingMethod($fdp->fk_shipment_mode, 'fk_shipment_mode', '', 1) ?></td>
		</tr>
		
	</table>
	<div class="tabsAction">
		<?php
			echo $form->btsubmit($langs->trans('Save'), 'bt_save');
			echo $form->btsubmit($langs->trans('Cancel'), 'bt_cancel','','butAction butActionCancel');
		?>
	</div>
	<?php
	
	
	
	$form->end();
	
	dol_fiche_end();
	llxFooter();
}
	
function liste($type) {
	global $conf, $langs;
	
	$page_name = "FraisDePortSetup";
	llxHeader('', $langs->trans($page_name));	
	
	$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	    . $langs->trans("BackToModuleList") . '</a>';
	print_fiche_titre($langs->trans($page_name), $linkback);
	
	// Configuration header
	$head = fraisdeportAdminPrepareHead();

	dol_fiche_head(  $head,  $type,  $page_name,   0,   "fraisdeport@fraisdeport" );	
	

	$l=new TListviewTBS('lPrice');
	
	
	$sql="SELECT rowid as Id, palier,fdp,zip,fk_shipment_mode,date_maj FROM ".MAIN_DB_PREFIX."frais_de_port 
			WHERE type='".$type."'";
	
	$PDOdb=new TPDOdb;
	
	$form = new TFormCore('auto', 'form1','get');
	echo $form->hidden('type', $type);
	
	
	echo $l->render($PDOdb, $sql, array(
		'link'=>array(
			'Id'=>'<a href="'.dol_buildpath('/fraisdeport/admin/fdp.php?action=edit&id=@val@&type='.$type,1).'">@val@</a>'
		)
		,'type'=>array(
			'fdp'=>'money'
			,'palier'=>'number'
			
			
			,'date_maj'=>'date'
		)
		,'title'=>array(
			'palier'=>$langs->trans('Palier')
			,'zip'=>$langs->trans('Zip')
			,'fk_shipment_mode'=>$langs->trans('ShipmentMode')
			,'fdp'=>$langs->trans('FraisDePort')
			,'date_maj'=>$langs->trans('Update')
		)
		,'eval'=>array(
			'fk_shipment_mode'=>'showShipmentMode(@val@)'
		)
		,'search'=>array(
			'palier'=>true
			,'zip'=>true
			,'fdp'=>true
		)
	),array(
		':type'=>$type
	));
	
	$form->end();
	
	echo '<div class="tabsAction">';
	echo $form->bt($langs->trans('New'), 'bt_new', ' onclick="document.location.href=\'?type='.$type.'&action=new\' "' );
	echo '</div>';
	
	dol_fiche_end();
	llxFooter();
		
}

function showShipmentMode($id) {
global $db, $langs;
	
	$sql = "SELECT rowid, code, libelle as label";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_shipment_mode";
    $sql.= " WHERE rowid=".$id;

	$res = $db->query($sql);
	if($obj = $db->fetch_object($res)) {
		return ($langs->trans("SendingMethod".strtoupper($obj->code)) != "SendingMethod".strtoupper($obj->code)) ? $langs->trans("SendingMethod".strtoupper($obj->code)) : $obj->label;
	}
	
	return '';
		
}
