<?php
require("../config.php");

$get = GETPOST('get');
$put = GETPOST('put');

$weight = GETPOST('poids');
$country = GETPOST('pays');
$dpt = GETPOST('dpt');

if (empty($weight))
{
    print json_encode(array('status'=>500, 'msg' => "Problème de poids de la commande<br>vérifiez que les poids sont bien renseignés sur chaque produit de la commande"));
    exit;
}
elseif (empty($country))
{
    print json_encode(array('status'=>500, 'msg' => "le pays de destination n'est pas renseigner"));
    exit;
}

switch($put){
        
    case 'checkprices': // récupère les prix de transport par transporteur
        checkprice($weight, $country, $dpt);
        break;
        
    Default:
        break;
        
}

function checkprice($weight, $country, $dpt)
{
    global $db;
    
    $result = array();
    if (empty($weight)) 
    {
        print json_encode(array('status'=>500, 'msg' => "la commande est vide"));
        exit;
    }
    elseif (empty($country)) 
    {
        print json_encode(array('status'=>500, 'msg' => "le pays de destination n'est pas renseigner"));
        exit;
    }
    elseif ($country == 1 && empty($dpt)) 
    {
        print json_encode(array('status'=>500, 'msg' => "le département de destination n'est pas renseigner"));
        exit;
    }
    
    //$sqlT = "SELECT DISTINCT transport as nom FROM ".MAIN_DB_PREFIX."c_grilles_transporteurs";
    $sqlT = "SELECT rowid, active FROM ".MAIN_DB_PREFIX."c_shipment_mode WHERE active = 1";
    $resql = $db->query($sqlT);
    $TTransport = array();
    if($resql)
    {
        while($obj = $db->fetch_object($resql)) $TTransport[] = $obj->rowid;
    }
    
    if(count($TTransport))
    {
        foreach ($TTransport as $tid)
        {
            $sql = "SELECT t.tarif, s.libelle as label";
            $sql.= " FROM ".MAIN_DB_PREFIX."c_tarifs_transporteurs as t";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paliers_transporteurs as p ON t.fk_palier = p.rowid";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_shipment_mode as s ON s.rowid = p.fk_trans";
            $sql.= " WHERE p.poids < ".$weight;
            $sql.= " AND p.fk_trans = '".$tid."'";
            $sql.= " AND t.fk_pays = ".$country;
            if($country = 1) $sql.= " AND t.departement = ".$dpt;
            $sql.= " ORDER BY poids DESC LIMIT 1";
            $resql2 = $db->query($sql);
            if($resql2)
            {
                $obj = $db->fetch_object($resql2);
                if(!empty($obj->tarif)) $result[$obj->label] = $obj->tarif;
            }
            $t[] = $tid;
            //if($tid == '23') break;
        }
    }
    
    $ret = '';
    if(count($result))
    {
        $ret.='<table width="100%" class="liste">';
        $ret .= '<tr class="liste_titre"><td>Transporteur</td><td>Cout de l\'envoi</td></tr>';
        foreach ($result as $name => $price)
        {
            $p = ($price < 1) ? $price * $weight : $price;
            $ret .= '<tr class="oddeven"><td>'.$name.'</td><td>'.$p.'</td></tr>';
        }
        $ret.='</table>';
    }
    
    print json_encode(array('status'=>200, 'msg' => "récupération réussi", 'liste' => $ret));
}