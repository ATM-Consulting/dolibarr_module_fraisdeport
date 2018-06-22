<?php
class ActionsFraisdeport
{ 
     /** Overloading the doActions function : replacing the parent's function with the one below 
      *  @param      parameters  meta datas of the hook (context, etc...) 
      *  @param      object             the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...) 
      *  @param      action             current action (if set). Generally create or edit or null 
      *  @return       void 
      */
      
    function doActions($parameters, &$object, &$action, $hookmanager) 
    {  
      	global $langs,$db;
		
		if (in_array('ordercard',explode(':',$parameters['context']))) 
        {
			
            //var_dump($object);
            

		}
		
		return 0;
	}
	
   function formObjectOptions($parameters, &$object, &$action, $hookmanager) {  
        global $conf, $langs,$db;
		
 		if((in_array('ordercard',explode(':',$parameters['context'])) || in_array('propalcard',explode(':',$parameters['context']))) && $conf->global->FRAIS_DE_PORT_USE_WEIGHT) {
 			    
	        print '<script type="text/javascript">
	                $(document).ready(function() { ';
	     
	        foreach($object->lines as &$line) {
	            
	            if($line->fk_product_type ==0 && $line->fk_product>0 ) {
	                
	                dol_include_once('/product/class/product.class.php','Product');
	                
	                $p=new Product($db);
	                $p->fetch($line->fk_product);
	                
	                if($p->id>0) {
	                    
	                    $weight_kg = $p->weight * $line->qty * pow(10, $p->weight_units);
	                    $id_line = !empty($line->id) ? $line->id : $line->rowid;
	                    
	                    if(!empty($weight_kg)) {
	                        print '$("tr#row-'.$id_line.' td:first").append(" - '.$langs->trans('Weight').' : '.$weight_kg.'Kg");';    
	                    }
	                    
	                   
	                    
	                }
	                
	                
	            }
	            
	        }
	        
	        print '});
	        </script>';
	    }
        
        return 0;
    }
    
    function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        
        
        if (in_array('ordercard',explode(':',$parameters['context'])))
        {
            print '<a href="#" class="butAction" id="transport">Calcul des frais de transport</a>';
            
            $weight = $this->getCmdWeight($object);
            $country = $object->thirdparty->country_id;
            $dpt = $object->thirdparty->state_code;
            
            //print $weight;
            ?>
            
            <script>
				$(document).ready(function(){
					console.log('Calcul transport ajouté');
					var btn = $('#transport');

 					if($('#transportPrices').length==0) {
						$('body').append('<div id="transportPrices" title="Liste des prix"></div>');
					}

					btn.click(function(e){
						e.preventDefault();
						// appel ajax récupération du tarif par transporteur
						$.ajax({ // on check s'il existe un prix plus bas ailleurs
                            url : "<?php echo dol_buildpath('/fraisdeport/script/interface.php',1) ?>"
                            ,data:{
                                put: 'checkprices'
                                ,poids:<?php echo $weight ?>
                                ,pays:<?php echo empty($country) ? 0 : $country ?>
                                ,dpt:<?php echo empty($dpt) ? 0 : $dpt ?>
                            }
                            ,method:"post"
                            ,dataType:'json'
                        }).done(function(data) {//récupération du résultat et présentation dans une popin
							console.log(data);
							if(data.status == 500) {
								$('#transportPrices').html('<div style="text-align:center">'+data.msg+'</div>');

 								$('#transportPrices').dialog({
 									modal:true,
 									width:'80%'
 								});
							} else if (data.status == 200) {
								console.log(data.liste);
								$('#transportPrices').html('<div>'+data.liste+'</div>');

 								$('#transportPrices').dialog({
 									modal:true,
 									width:'80%'
 								});
							}
                        });
						
					});
				});
            </script>
            
            <?php
        }
        
    }
    
    function getCmdWeight($object)
    {
        global $conf;
        
        $poidscmd = 0;
        
        if(!$conf->shippableorder->enabled)
        {
            define('INC_FROM_DOLIBARR',true);
            dol_include_once('/fraisdeport/config.php');
            dol_include_once('/fraisdeport/class/fraisdeport.class.php');
            
            $poidscmd = TFraisDePort::getTotalWeight($object);
        }
        else
        {
            //                 var_dump($object->lines, $object->shippableorder->TlinesShippable);
            
            foreach ($object->lines as $line)
            {
                if(!empty($object->shippableorder->TlinesShippable[$line->id]['qty_shippable'])) $poidscmd += $line->weight * $object->shippableorder->TlinesShippable[$line->id]['qty_shippable'];
            }
            
        }
        return $poidscmd;
    }
}