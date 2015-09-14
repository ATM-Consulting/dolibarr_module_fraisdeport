<?php


class TFraisDePort extends TObjetStd {
    function __construct() {
        global $langs;
         
        parent::set_table(MAIN_DB_PREFIX.'frais_de_port');
        parent::add_champs('palier,fdp',array('type'=>'float', 'index'=>true));
        parent::add_champs('zip,type',array('index'=>true));
        parent::add_champs('fk_shipment_mode',array('type'=>'int', 'index'=>true));
        
        parent::_init_vars();
        parent::start();    
        
         
    }
	
	static function getAll(&$PDOdb, $type='AMOUNT', $asArray=false) {
		
		$TFdp = array();
		$Tab = $PDOdb->ExecuteAsArray("SELECT rowid FROM ".MAIN_DB_PREFIX."frais_de_port WHERE type='".$type."' ORDER BY fk_shipment_mode,zip, palier ");
		foreach($Tab as &$row) {
			
			$o=new TFraisDePort;
			$o->load($PDOdb, $row->rowid );
			
			$TFdp[] = ($asArray) ? (Array)$o : $o;			
			
		}
		
		return $TFdp;
	}
    
}
    