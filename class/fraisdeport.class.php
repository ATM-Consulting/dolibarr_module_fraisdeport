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
	static function getFDP(&$PDOdb, $type, $total) {
		
		$TFraisDePort = TFraisDePort::getAll($PDOdb, $type, true);
		
		$fdp_used = 0;
        if(is_array($TFraisDePort) && count($TFraisDePort) > 0) {
        	
            foreach ($TFraisDePort as &$fdp) {
            	
                if($type === 'WEIGHT' && $total >= $fdp['palier'] && ($fdp['fdp']>$fdp_used || empty($fdp_used) ) ) {
                    if (empty($fdp['zip']) 
                        || (!empty( $fdp['zip'] ) && strpos( $object->client->zip, $fdp['zip']) === 0 ) ){
                            $fdp_used = $fdp['fdp'];        
                    } 
                }
				else if($type==='AMOUNT') {
					if($total < $fdp['palier'] && ($fdp['fdp']<$fdp_used || empty($fdp_used) ) ) {
							$fdp_used = $fdp['fdp'];
					}
				}
				
            }
        }
		
		return $fdp_used;
	}
	static function alreadyAdded(&$object) {
		global $conf;
		
		$fdpAlreadyInDoc = false;
		$fk_product = $conf->global->FRAIS_DE_PORT_ID_SERVICE_TO_USE;
		
		
		foreach($object->lines as $line) {
			if(!empty($line->fk_product) && $line->fk_product == $fk_product) {
				$fdpAlreadyInDoc = true;
                break;
			}
		}
		
		return $fdpAlreadyInDoc;
	}
    
	static function getTotalWeight(&$object) {
		global $db;
		 dol_include_once('/product/class/product.class.php','Product');
		
		$total_weight = 0;
        foreach($object->lines as &$line) {
            if($line->fk_product_type ==0 && $line->fk_product>0 ) {
                $p=new Product($db);
                $p->fetch($line->fk_product);
                
                if($p->id>0) {
                    $weight_kg = $p->weight * $line->qty * pow(10, $p->weight_units);
                    $total_weight+=$weight_kg;
                }
            }
        }
        
		return $total_weight;
		
	}
}
    