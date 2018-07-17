<?php
require("../config.php");

$get = GETPOST('get');
$put = GETPOST('put');

$weight = GETPOST('poids');
$country = GETPOST('pays');
$dpt = GETPOST('dpt');
$obj_id = GETPOST('obj_id');

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
    global $db, $langs, $obj_id;
    
    $result = array();
    if (empty($weight)) 
    {
        print json_encode(array('status'=>500, 'msg' => "la commande est vide"));
        exit;
    }
    elseif (empty($country)) 
    {
        print json_encode(array('status'=>500, 'msg' => "le pays de destination n'est pas renseigné"));
        exit;
    }
    elseif ($country == 1 && empty($dpt)) 
    {
        print json_encode(array('status'=>500, 'msg' => "le département de destination n'est pas renseigné"));
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
            $sql = "SELECT t.tarif, s.libelle as label, s.rowid";
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
                if(!empty($obj->tarif)) {
                    $result[$obj->rowid]['label'] = $obj->label;
                    $result[$obj->rowid]['prix'] = $obj->tarif;
                }
            }
            $t[] = $tid;
            //if($tid == '23') break;
        }
    }
    
    $ret = '<p>Poids pris en compte pour le calcul : ' . $weight .' kg</p>';
    if(count($result))
    {
        $ret.='<table width="100%" class="liste">';
        $ret .= '<tr class="liste_titre"><td>Transporteur</td><td>Cout de l\'envoi</td><td align="center">Appliquer</td></tr>';
        foreach ($result as $id => $price)
        {
            $p = ($price['prix'] < 1) ? $price['prix'] * $weight : $price['prix'];
            $ret .= '<tr class="oddeven"><td>'.$price['label'].'</td><td>'.$p.'</td>';
            $ret .= '<td align="center"><a href="#" class="applyPrice butAction" data-method="'.$id.'" data-pv="'.$price['prix'].'">'.$langs->trans('Apply').'</a></td>';
            $ret .= '</tr>';
        }
        $ret.='</table>';
        
        $ret.= '<script>';
        $ret.= '$(document).ready(function(){
                    $(".applyPrice").on("click", function(e){
                        e.preventDefault();
                        transp = $(this).attr("data-method");
                        price = $(this).attr("data-pv");
                        $.ajax({
                            url : "?action=setshippingmethod&id='.$obj_id.'&shipping_method_id="+transp
                        }).done(function(){
                            $("#tva_tx").hide();
                            $("#price_ht").hide();
                            $("#units").hide();
                            $("#np_markRate").hide();
                            $("#np_markRate").next().hide();
                            $("#prod_entry_mode_predef").prop("checked", true);
                            $("#search_idprod").val("TRANSPORT");
                            $("#idprod").val("839");
                            $("#fournprice_predef").val("inputprice");
                            $("#buying_price").show().val(price);
                            $("[title=Close]").click()
                        });
                    });
                });';
        $ret.= '</script>';
        
        
    }
    
    if (empty($ret)) print json_encode(array('status'=>500, 'msg' => "Aucun tarif trouvé pour les paramètres de cete commande", 'liste' => $ret));
    else print json_encode(array('status'=>200, 'msg' => "récupération réussi", 'liste' => $ret));
}