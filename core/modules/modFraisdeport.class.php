<?php
/* <one line to give the program's name and a brief idea of what it does.>
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
 * 	\defgroup	mymodule	MyModule module
 * 	\brief		MyModule module descriptor.
 * 	\file		core/modules/modMyModule.class.php
 * 	\ingroup	mymodule
 * 	\brief		Description and activation file for module MyModule
 */
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

/**
 * Description and activation class for module MyModule
 */
class modFraisdeport extends DolibarrModules
{

    /**
     * 	Constructor. Define names, constants, directories, boxes, permissions
     *
     * 	@param	DoliDB		$db	Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;

        // Id for module (must be unique).
        // Use a free id here
        // (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 104150; // 104000 to 104999 for ATM CONSULTING
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'fraisdeport';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
        // It is used to group modules in module setup page
        $this->family = "ATM";
        // Module label (no space allowed)
        // used if translation string 'ModuleXXXName' not found
        // (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description
        // used if translation string 'ModuleXXXDesc' not found
        // (where XXX is value of numeric property 'numero' of module)
        $this->description = "Frais de port calculés en fonction du prix de la commande";
        // Possible values for version are: 'development', 'experimental' or version
        $this->version = '1.1';
        // Key used in llx_const table to save module status enabled/disabled
        // (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        // Where to store the module in setup page
        // (0=common,1=interface,2=others,3=very specific)
        $this->special = 0;
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png
        // use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png
        // use this->picto='pictovalue@module'
        $this->picto = 'fraisdeport@fraisdeport'; // mypicto@mymodule
        // Defined all module parts (triggers, login, substitutions, menus, css, etc...)
        // for default path (eg: /mymodule/core/xxxxx) (0=disable, 1=enable)
        // for specific path of parts (eg: /mymodule/core/modules/barcode)
        // for specific css file (eg: /mymodule/css/mymodule.css.php)
        $this->module_parts = array(
            // Set this to 1 if module has its own trigger directory
            'triggers' => 1,
            // Set this to 1 if module has its own login method directory
            //'login' => 0,
            // Set this to 1 if module has its own substitution function file
            //'substitutions' => 0,
            // Set this to 1 if module has its own menus handler directory
            //'menus' => 0,
            // Set this to 1 if module has its own barcode directory
            //'barcode' => 0,
            // Set this to 1 if module has its own models directory
            //'models' => 0,
            // Set this to relative path of css if module has its own css file
            //'css' => '/mymodule/css/mycss.css.php',
            // Set here all hooks context managed by module
            'hooks' => array('ordercard','propalcard','billcard', 'invoicecard')
            // Set here all workflow context managed by module
            //'workflow' => array('order' => array('WORKFLOW_ORDER_AUTOCREATE_INVOICE'))
        );

        // Data directories to create when module is enabled.
        // Example: this->dirs = array("/mymodule/temp");
        $this->dirs = array();

        // Config pages. Put here list of php pages
        // stored into mymodule/admin directory, used to setup module.
        $this->config_page_url = array("admin_fraisdeport.php@fraisdeport");

        // Dependencies
        // List of modules id that must be enabled if this module is enabled
        $this->depends = array();
        // List of modules id to disable if this one is disabled
        $this->requiredby = array();
        // Minimum version of PHP required by module
        $this->phpmin = array(5, 3);
        // Minimum version of Dolibarr required by module
        $this->need_dolibarr_version = array(3, 2);
        $this->langfiles = array("fraisdeport@fraisdeport"); // langfiles@mymodule
        // Constants
        // List of particular constants to add when module is enabled
        // (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example:
        $this->const = array(
           
        );

        // Array to add new pages in new tabs
        // Example:
        $this->tabs = array(
            //	// To add a new tab identified by code tabname1
            //	'objecttype:+tabname1:Title1:langfile@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__',
            //	// To add another new tab identified by code tabname2
            //	'objecttype:+tabname2:Title2:langfile@mymodule:$user->rights->othermodule->read:/mymodule/mynewtab2.php?id=__ID__',
            //	// To remove an existing tab identified by code tabname
            //	'objecttype:-tabname'
        );
        // where objecttype can be
        // 'thirdparty'			to add a tab in third party view
        // 'intervention'		to add a tab in intervention view
        // 'order_supplier'		to add a tab in supplier order view
        // 'invoice_supplier'	to add a tab in supplier invoice view
        // 'invoice'			to add a tab in customer invoice view
        // 'order'				to add a tab in customer order view
        // 'product'			to add a tab in product view
        // 'stock'				to add a tab in stock view
        // 'propal'				to add a tab in propal view
        // 'member'				to add a tab in fundation member view
        // 'contract'			to add a tab in contract view
        // 'user'				to add a tab in user view
        // 'group'				to add a tab in group view
        // 'contact'			to add a tab in contact view
        // 'categories_x'		to add a tab in category view
        // (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
        // Dictionnaries
        if (! isset($conf->mymodule->enabled)) {
            $conf->mymodule=new stdClass();
            $conf->mymodule->enabled = 0;
        }
        $this->dictionnaries = array();
      
        // Boxes
        // Add here list of php file(s) stored in core/boxes that contains class to show a box.
        $this->boxes = array(); // Boxes list
        $r = 0;
        // Example:

        $this->boxes[$r][1] = "MyBox@fraisdeport";
        $r ++;
        /*
          $this->boxes[$r][1] = "myboxb.php";
          $r++;
         */

        // Permissions
        $this->rights = array(); // Permission array used by this module
        $r = 0;

        // Add here list of permission defined by
        // an id, a label, a boolean and two constant strings.
        // Example:
        //// Permission id (must not be already used)
        //$this->rights[$r][0] = 2000;
        //// Permission label
        //$this->rights[$r][1] = 'Permision label';
        //// Permission by default for new user (0/1)
        //$this->rights[$r][3] = 1;
        //// In php code, permission will be checked by test
        //// if ($user->rights->permkey->level1->level2)
        //$this->rights[$r][4] = 'level1';
        //// In php code, permission will be checked by test
        //// if ($user->rights->permkey->level1->level2)
        //$this->rights[$r][5] = 'level2';
        //$r++;
        // Main menu entries
        $this->menus = array(); // List of menus to add
        $r = 0;

        // Add here entries to declare new menus
        //
        // Example to declare a new Top Menu entry and its Left menu entry:
        //$this->menu[$r]=array(
        //	// Put 0 if this is a top menu
        //	'fk_menu'=>0,
        //	// This is a Top menu entry
        //	'type'=>'top',
        //	'titre'=>'MyModule top menu',
        //	'mainmenu'=>'mymodule',
        //	'leftmenu'=>'mymodule',
        //	'url'=>'/mymodule/pagetop.php',
        //	// Lang file to use (without .lang) by module.
        //	// File must be in langs/code_CODE/ directory.
        //	'langs'=>'mylangfile',
        //	'position'=>100,
        //	// Define condition to show or hide menu entry.
        //	// Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
        //	'enabled'=>'$conf->mymodule->enabled',
        //	// Use 'perms'=>'$user->rights->mymodule->level1->level2'
        //	// if you want your menu with a permission rules
        //	'perms'=>'1',
        //	'target'=>'',
        //	// 0=Menu for internal users, 1=external users, 2=both
        //	'user'=>2
        //);
        //$r++;
        //$this->menu[$r]=array(
        //	// Use r=value where r is index key used for the parent menu entry
        //	// (higher parent must be a top menu entry)
        //	'fk_menu'=>'r=0',
        //	// This is a Left menu entry
        //	'type'=>'left',
        //	'titre'=>'MyModule left menu',
        //	'mainmenu'=>'mymodule',
        //	'leftmenu'=>'mymodule',
        //	'url'=>'/mymodule/pagelevel1.php',
        //	// Lang file to use (without .lang) by module.
        //	// File must be in langs/code_CODE/ directory.
        //	'langs'=>'mylangfile',
        //	'position'=>100,
        //	// Define condition to show or hide menu entry.
        //	// Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
        //	'enabled'=>'$conf->mymodule->enabled',
        //	// Use 'perms'=>'$user->rights->mymodule->level1->level2'
        //	// if you want your menu with a permission rules
        //	'perms'=>'1',
        //	'target'=>'',
        //	// 0=Menu for internal users, 1=external users, 2=both
        //	'user'=>2
        //);
        //$r++;
        //
        // Example to declare a Left Menu entry into an existing Top menu entry:
        //$this->menu[$r]=array(
        //	// Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy'
        //	'fk_menu'=>'fk_mainmenu=mainmenucode',
        //	// This is a Left menu entry
        //	'type'=>'left',
        //	'titre'=>'MyModule left menu',
        //	'mainmenu'=>'mainmenucode',
        //	'leftmenu'=>'mymodule',
        //	'url'=>'/mymodule/pagelevel2.php',
        //	// Lang file to use (without .lang) by module.
        //	// File must be in langs/code_CODE/ directory.
        //	'langs'=>'mylangfile',
        //	'position'=>100,
        //	// Define condition to show or hide menu entry.
        //	// Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
        //	// Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
        //	'enabled'=>'$conf->mymodule->enabled',
        //	// Use 'perms'=>'$user->rights->mymodule->level1->level2'
        //	// if you want your menu with a permission rules
        //	'perms'=>'1',
        //	'target'=>'',
        //	// 0=Menu for internal users, 1=external users, 2=both
        //	'user'=>2
        //);
        //$r++;
        // Exports
        $r = 1;

    }

    /**
     * Function called when module is enabled.
     * The init function add constants, boxes, permissions and menus
     * (defined in constructor) into Dolibarr database.
     * It also creates data directories
     *
     * 	@param		string	$options	Options when enabling module ('', 'noboxes')
     * 	@return		int					1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $db,$conf;
        	
        $sql = array();

        $result = $this->loadTables();
		
		// Création extrafield pour choix si frais de port doit apparaitre sur doc.
		dol_include_once('/core/class/extrafields.class.php');
		//function addExtraField($attrname, $label, $type, $pos, $size, $elementtype, $unique=0, $required=0,$default_value='', $param=0)
		$ext = new ExtraFields($db);
		$res = $ext->addExtraField("use_frais_de_port", 'Automatisation des frais de port', 'select', 0, "", 'propal', 0, 0, '', array("options" =>array("Oui" => "Oui", "Non" => "Non")));
		$res = $ext->addExtraField("use_frais_de_port", 'Automatisation des frais de port', 'select', 0, "", 'commande', 0, 0, '', array("options" =>array("Oui" => "Oui", "Non" => "Non")));
		
		define('INC_FROM_DOLIBARR', true);
		
		dol_include_once('/fraisdeport/config.php');
		dol_include_once('/fraisdeport/class/fraisdeport.class.php');
		
		$PDOdb=new TPDOdb;
		$o=new TFraisDePort;
		$o->init_db_by_vars($PDOdb);
		
		if(!empty($conf->global->FRAIS_DE_PORT_WEIGHT_ARRAY)) {
			
			$TFraisDePort = unserialize($conf->global->FRAIS_DE_PORT_WEIGHT_ARRAY);
			
			foreach($TFraisDePort as $fdp) {
				
				$o=new TFraisDePort;
				$o->palier = $fdp['weight'];
				$o->fdp = $fdp['fdp'];
				$o->zip = $fdp['zip'];
				$o->type='WEIGHT';
				$o->save($PDOdb);
				
			}
			
			
			dolibarr_del_const($db, 'FRAIS_DE_PORT_WEIGHT_ARRAY');
		}
		
		if(!empty($conf->global->FRAIS_DE_PORT_ARRAY)) {
			$TFraisDePort = unserialize($conf->global->FRAIS_DE_PORT_ARRAY);
			foreach($TFraisDePort as $palier=>$fdp) {
				$o=new TFraisDePort;
				$o->palier = $palier;
				$o->fdp = $fdp;
				$o->type='AMOUNT';
				$o->save($PDOdb);
				
			}
			
			dolibarr_del_const($db, 'FRAIS_DE_PORT_ARRAY');
		}
		
        return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled.
     * Remove from database constants, boxes and permissions from Dolibarr database.
     * Data directories are not deleted
     *
     * 	@param		string	$options	Options when enabling module ('', 'noboxes')
     * 	@return		int					1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();

        return $this->_remove($sql, $options);
    }

    /**
     * Create tables, keys and data required by module
     * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
     * and create data commands must be stored in directory /mymodule/sql/
     * This function is called by this->init
     *
     * 	@return		int		<=0 if KO, >0 if OK
     */
    private function loadTables()
    {
        return $this->_load_tables('/fraisdeport/sql/');
    }
}