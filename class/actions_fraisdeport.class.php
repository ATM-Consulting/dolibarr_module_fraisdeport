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
			
            
            

		}
		
		return 0;
	}
   function formObjectOptions($parameters, &$object, &$action, $hookmanager) {  
        global $langs,$db;
     
        print '<script type="text/javascript">
                $(document).ready(function() { ';
     
        foreach($object->lines as &$line) {
            
            if($line->fk_product_type ==0 && $line->fk_product>0 ) {
                
                dol_include_once('/product/class/product.class.php','Product');
                
                $p=new Product($db);
                $p->fetch($line->fk_product);
                
                if($p->id>0) {
                    
                    $weight_kg = $p->weight * pow(10, $p->weight_units);
                    $id_line = !empty($line->id) ? $line->id : $line->rowid;
                    
                    if(!empty($weight_kg)) {
                        print '$("tr#row-'.$id_line.' td:first").append(" - '.$langs->trans('Weight').' : '.$weight_kg.'Kg");';    
                    }
                    
                   
                    
                }
                
                
            }
            
        }
        
        print '});
        </script>';
        
        return 0;
    }
}