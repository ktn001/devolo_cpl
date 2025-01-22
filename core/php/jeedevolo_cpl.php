<?php
// vi: tabstop=4 autoindent
try {
	require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

	function process_infoState($serial, $states) {
		$eqLogic = devolo_cpl::byLogicalId($serial, 'devolo_cpl');
		if (!is_object($eqLogic)) {
			throw new Exception (sprintf(__("Equipemment serial:%s introuvable",__FILE__),$serial));
		}
		$states = is_json($states,$states);
		if (isset($states['leds'])) {
			$eqLogic->checkAndUpdateCmd('leds', $states['leds']);
		}
		if (isset($states['locate'])) {
			$eqLogic->checkAndUpdateCmd('locate', $states['locate']);
		}
		if (isset($states['firmwareAvailable'])) {
			$eqLogic->checkAndUpdateCmd('update_available', $states['firmwareAvailable']);
		}
		if (isset($states['wifi_guest'])) {
			$eqLogic->checkAndUpdateCmd('guest', $states['wifi_guest']['enabled']);
			$remaindCmd = $eqLogic->getCmd('info','guest_remaining');
			if (is_object($remaindCmd)){
				$oldValue=$remaindCmd->execCmd();
				$maxValue=$remaindCmd->getConfiguration('maxValue');
				if ($maxValue < $states['wifi_guest']['remaining']) {
					$remaindCmd->setConfiguration('maxValue',$states['wifi_guest']['remaining']);
					$remaindCmd->save();
				}
				$eqLogic->checkAndUpdateCmd('guest_remaining', $states['wifi_guest']['remaining']);
				$newValue=$remaindCmd->execCmd();
				if ($newValue > $oldValue) {
					$remaindCmd->setConfiguration('maxValue',$newValue);
					$remaindCmd->setConfiguration('minValue',0);
					$remaindCmd->save();
				}
			}
		}
		$eqLogic->checkAndUpdateCmd('online', 1);
	}
	
	function process_rates($rates) {
		$time = date('Y-m-d H:i:s');
		$sql = "INSERT INTO devolo_cpl_rates ";
		$sql .= "SET `time`=:time, ";
		$sql .= "`mac_address_from`=:mac_src, ";
		$sql .= "`mac_address_to`=:mac_dst, ";
		$sql .= "`tx_rate`=:tx_rate, ";
		$sql .= "`rx_rate`=:rx_rate ";
		foreach ($rates as $src => $dstRates) {
			$mac_src = rtrim(chunk_split($src,2,":"),":");
			foreach ($dstRates as $dst => $data) {
				$data = is_json($data,$data);
				$mac_dst = rtrim(chunk_split($dst,2,":"),":");
				$values = array(
					'time'    => $time,
					'mac_src' => $mac_src,
					'mac_dst' => $mac_dst,
					'tx_rate' => $data['tx_rate'],
					'rx_rate' => $data['rx_rate']
				);
				DB::Prepare($sql, $values, DB::FETCH_TYPE_ROW);
			}
		}
	}
	
	function process_firmwares($result, $eqLogic) {
		foreach ($result['firmwares'] as $firmware) {
			$eqLogic = devolo_cpl::byMacAddress ($firmware['mac']);
			$eqLogic->checkAndUpdateCmd('firmware',$firmware['version']);
		}
		$eqLogic->checkAndUpdateCmd('online', 1);
	}
	
	function process_message($result, $eqLogic) {
		if ($result['code'] == 'devNotAnswer') {
			$eqLogic->checkAndUpdateCmd('online', 0);
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

	if (!jeedom::apiAccess(init('apikey'), 'devolo_cpl')) {
		echo __('Vous n\'etes pas autorisé à effectuer cette action', __FILE__);
		die();
	}
	if (init('test') != '') {
		echo 'OK';
		die();
	}

	$payload = json_decode(file_get_contents("php://input"),true);
	$payload = is_json($payload,$payload);
	if (!is_array($payload)) {
		die();
	}
	log::add("devolo_cpl","info","[jeedevolo_cpl] Message reçu du démon: " . print_r($payload,true));

	if (array_key_exists('infoState',$payload)) {
		foreach ($payload['infoState'] as $serial => $states) {
			process_infostate($serial, $states);
		}
	}
	if (array_key_exists('rates',$payload)) {
		process_rates($payload['rates']);
	}

	log::add("devolo_cpl","debug","payload: " . print_r($payload,true));
	if (! isset($payload['action'])) {
		return;
	// 	throw new Exception(__('Message inconnu reçu du démon',__FILE__));
	}
	if (isset ($payload['serial'])) {
		$eqLogic = devolo_cpl::byLogicalId($payload['serial'], 'devolo_cpl');
		if (! is_object($eqLogic)){
			throw new Exception(sprintf(__('eqLogic pour le n° de serie %s introuvable',__FILE__),$payload['serial']));
		}
	} else {
		$eqLogic = NULL;
	}

	$function = 'process_' . $payload['action'];
	if (function_exists($function)){
		$function($payload, $eqLogic);
	}


} catch (Exception $e) {
	log::add('devolo_cpl', 'error', displayException($e));
}


