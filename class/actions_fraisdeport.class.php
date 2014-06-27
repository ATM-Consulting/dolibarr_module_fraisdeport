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
			$object->fetch_optionals($object->id);
			/*echo "<pre>";
			print_r($object);
			echo "</pre>";*/

			if($action == "confirm_validate" && $object->array_options['options_use_frais_de_port'] === "Oui") {
				
				// On récupère les frais de port définis dans la configuration du module
				$TFraisDePort = unserialize(dolibarr_get_const($db, "FRAIS_DE_PORT_ARRAY"));
				
				// On les range du pallier le plus petit au plus grand
				ksort($TFraisDePort);
				
				// On parcoure les pallier du plus petit au plus grand pour chercher si le montant de la commande est inférieur à l'un des palliers
				if(is_array($TFraisDePort) && count($TFraisDePort) > 0) {
					
					foreach ($TFraisDePort as $pallier => $fdp) {
						
						if($object->total_ttc < $pallier)
							$fdp_used = $fdp;
						
					}
					
				}
				
				$fdp_used = empty($fdp_used) ? 0 : $fdp_used;
				$object->addline("Montant total des frais de port", $fdp_used, 1, 0, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $date_start, $date_end);
				
			}

		}
		
		return 0;
	}

}