<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

try {
    require_once dirname(__FILE__) . '/../php/devolo_cpl.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

  /* Fonction permettant l'envoi de l'entête 'Content-Type: application/json'
    En V3 : indiquer l'argument 'true' pour contrôler le token d'accès Jeedom
    En V4 : autoriser l'exécution d'une méthode 'action' en GET en indiquant le(s) nom(s) de(s) action(s) dans un tableau en argument
  */
    ajax::init();
    $action = init('action');
    log::add("devolo_cpl","debug","  Ajax devolo_cpl: action: " . $action);

    if ($action == 'syncDevolo'){
	unautorizedInDemo();
	if (!isConnect('admin')) {
	    throw new Exception(__('401 - Accès non autorisé',__FILE__));
	}

	try {
	    devolo_cpl::syncDevolo();
	    ajax::success();
	} catch (Exception $e){
	    ajax::error(displayException($e), $e->getCode());
	}
    }

    if ($action == 'getCplNetworks') {
	try {
	    $cplNetworks = devolo_cpl::getCplNetworksToModal();
	    ajax::success(json_encode($cplNetworks));
	} catch (Exception $e){
	    ajax::error(displayException($e), $e->getCode());
	}
    }

    if ($action == 'getCplRates') {
	try {
	    $cplRates = devolo_cpl::getCplRatesToModal();
	    ajax::success(json_encode($cplRates));
	} catch (Exception $e){
	    ajax::error(displayException($e), $e->getCode());
	}
    }

    if ($action == 'ratesHistorique') {
	try {
	    $macFrom = init('macFrom');
	    $macTo = init('macTo');
	    if ($macFrom == '' || $macTo == '') {
		throw new Exception(__("l'adresse mac source ou destination est indéfinie.",__FILE__));
	    }
	    $rates = devolo_cpl::getRatesHistorique($macFrom, $macTo);
	    ajax::success($rates);
	} catch (Exception $e){
	    ajax::error(displayException($e), $e->getCode());
	}
    }

    if ($action == 'wifiHistorique_ap') {
	try {
	    $serial = init('key');
	    if ($serial == '') {
		throw new Exception(__("Le numéro de série est indéfinie.",__FILE__));
	    }
	    $histo = devolo_connection::getWifiHistorique_ap($serial);
	    ajax::success($histo);
	} catch (Exception $e){
	    ajax::error(displayException($e), $e->getCode());
	}
    }

    if ($action == 'wifiHistorique_client') {
	try {
	    $mac = init('key');
	    if ($mac == '') {
		throw new Exception(__("L'adresse mac du client est indéfinie.",__FILE__));
	    }
	    $histo = devolo_connection::getWifiHistorique_client($mac);
	    ajax::success($histo);
	} catch (Exception $e){
	    ajax::error(displayException($e), $e->getCode());
	}
    }

    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
    /*     * *********Catch exeption*************** */
}
catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
