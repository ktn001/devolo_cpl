<?php

function process_infoState($result, $eqLogic) {
    if (isset($result['leds'])) {
	$eqLogic->checkAndUpdateCmd('leds', $result['leds']);
    }
    if (isset($result['locate'])) {
	$eqLogic->checkAndUpdateCmd('locate', $result['locate']);
    }
    if (isset($result['firmwareAvailable'])) {
	$eqLogic->checkAndUpdateCmd('update_available', $result['firmwareAvailable']);
    }
    if (isset($result['wifi_guest'])) {
	$eqLogic->checkAndUpdateCmd('guest', $result['wifi_guest']['enabled']);
	$remaindCmd = $eqLogic->getCmd('info','guest_remaining');
	if (is_object($remaindCmd)){
	    $oldValue=$remaindCmd->execCmd();
	    $eqLogic->checkAndUpdateCmd('guest_remaining', $result['wifi_guest']['remaining']);
	    $newValue=$remaindCmd->execCmd();
	    if ($newValue > $oldValue) {
		$remaindCmd->setConfiguration('maxValue',$newValue);
		$remaindCmd->setConfiguration('minValue',0);
		$remaindCmd->save();
	    }
	}
    }
}

function process_firmwares($result, $eqLogic) {
	foreach ($result['firmwares'] as $firmware) {
		$eqLogic = devolo_cpl::byMacAddress ($firmware['mac']);
		$eqLogic->checkAndUpdateCmd('firmware',$firmware['version']);
	}
}

function process_getRates($result, $eqLogic) {
    $time = date('Y-m-d H:i:s');
    foreach ($result['rates'] as $rate){
	$rate['mac_address_from'] = rtrim(chunk_split($rate['mac_address_from'],2,":"),":");
	$rate['mac_address_to'] = rtrim(chunk_split($rate['mac_address_to'],2,":"),":");
	$sql = 'INSERT INTO devolo_cpl_rates ';
	$sql .= 'SET `time`="' . $time . '", ';
	$sql .= '`mac_address_from`="' . $rate['mac_address_from'] . '", ';
	$sql .= '`mac_address_to`="' . $rate['mac_address_to'] . '", ';
	$sql .= '`tx_rate`="' . $rate['tx_rate'] . '", ';
	$sql .= '`rx_rate`="' . $rate['rx_rate'] . '"';
	DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
    }
}

function process_message($result, $eqLogic) {
    if ($result['code'] == 'devNotAnswer') {
	$texte = sprintf(__("L'équipement %s (%s) ne répond pas",__FILE__),$result['serial'],$result['ip']);
	log::add("devolo_cpl","error",$texte);
    } elseif ($result['code'] == 'devPasswordError') {
	$texte = sprintf(__("L'équipement %s (%s): erreur de password",__FILE__),$result['serial'],$result['ip']);
	log::add("devolo_cpl","error",$texte);
    } elseif ($result['code'] == 'httpxStatusError') {
	log::add("devolo_cpl","error",$result['message']);
    } else {
	log::add("devolo_cpl","error",$result['code']);
    }
}

function process_wifiConnectedDevices($result, $eqLogic) {
	devolo_connection::set_connected_devices($result['serial'],$result['connections']);
}

try {
    require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

    if (!jeedom::apiAccess(init('apikey'), 'devolo_cpl')) {
	echo __('Vous n\'etes pas autorisé à effectuer cette action', __FILE__);
	die();
    }
    if (init('test') != '') {
	echo 'OK';
	die();
    }
    $result = json_decode(file_get_contents("php://input"), true);
    log::add("devolo_cpl","info",__("Message reçu par jeedevolo_cpl.php: ",__FILE__) .  print_r($result,true));
    if (!is_array($result)) {
	die();
    }
    if (! isset($result['action'])) {
	throw new Exception(__('Message inconnu reçu du démon',__FILE__));
    }
    if (isset ($result['serial'])) {
	$eqLogic = devolo_cpl::byLogicalId($result['serial'], 'devolo_cpl');
	if (! is_object($eqLogic)){
	    throw new Exception(sprintf(__('eqLogic pour le n° de serie %s introuvable',__FILE__),$result['serial']));
	}
    } else {
	$eqLogic = NULL;
    }

    $function = 'process_' . $result['action'];
    if (function_exists($function)){
	$function($result, $eqLogic);
    }

} catch (Exception $e) {
    log::add('devolo_cpl', 'error', displayException($e));
}


