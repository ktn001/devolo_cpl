<?php

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
    log::add("devolo_cpl","debug",__("Message reçu par jeedevolo_cpl.php: ",__FILE__) .  print_r($result,true));
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
    }

    if ($result['action'] == 'getState') {
	if (isset($result['leds'])) {
	    $eqLogic->checkAndUpdateCmd('LEDS', $result['leds']);
	}
    }
} catch (Exception $e) {
    log::add('devolo_cpl', 'error', displayException($e));
}

