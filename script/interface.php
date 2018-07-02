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
    
    $sqlT = "SELECT DISTINCT transport as nom FROM ".MAIN_DB_PREFIX."c_grilles_transporteurs";
    $resql = $db->query($sqlT);
    $TTransport = array();
    if($resql)
    {
        while($obj = $db->fetch_object($resql)) $TTransport[] = $obj->nom;
    }
    
    if(count($TTransport))
    {
        foreach ($TTransport as $transport)
        {
            $sql = "SELECT tarif";
            $sql.= " FROM ".MAIN_DB_PREFIX."c_grilles_transporteurs";
            $sql.= " WHERE poids < ".$weight;
            $sql.= " AND transport = '".$transport."'";
            $sql.= " AND fk_pays = ".$country;
            if($country = 1) $sql.= " AND departement = ".$dpt;
            $sql.= " ORDER BY poids DESC LIMIT 1";
            
            $resql2 = $db->query($sql);
            if($resql2)
            {
                $obj = $db->fetch_object($resql2);
                if(!empty($obj->tarif)) $result[$transport] = $obj->tarif;
            }
        }
    }
    
    $ret = '';
    if(count($result))
    {
        $ret.='<table width="100%" class="liste">';
        $ret .= '<tr class="liste_titre"><td>Transporteur</td><td>Cout de l\'envoi</td></tr>';
        foreach ($result as $name => $price)
        {
            $p = ($price < 0) ? $price * $weight : $price;
            $ret .= '<tr class="oddeven"><td>'.$name.'</td><td>'.$p.'</td></tr>';
        }
        $ret.='</table>';
    }
    
    print json_encode(array('status'=>200, 'msg' => "récupération réussi", 'liste' => $ret));
}