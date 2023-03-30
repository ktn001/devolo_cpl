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

	ajax::init();
	$action = init('action');
	log::add("devolo_cpl","debug","  Ajax devolo_macinfo: action: " . $action);

	if ($action == 'getAll') {
		try{
			$return = [];
			foreach (devolo_macinfo::all() as $macinfoObj) {
				$return[] = utils::o2a($macinfoObj);
			}
			ajax::success(json_encode($return));
		} catch (Exception $e){
			ajax::error(displayException($e). $e->getCode());
		}
	}

	if ($action == 'save') {
		unautorizedInDemo();
		if (!isConnect('admin')) {
			throw new Exceptoin(__('401 - Accès non autorisé',__FILE__));
		}

		try {
			$macinfosDB = [];
			foreach (devolo_macinfo::all() as $macinfoDB) {
				$macinfosDB[$macinfoDB->getId()] = $macinfoDB;
			}

			$macinfos = json_decode(init('macinfos'), true);
			foreach ($macinfos as $macinfo) {
				log::add("devolo_cpl","debug","SAVE: " . print_r($macinfo,true));
    
				if (array_key_exists($macinfo['id'],$macinfosDB)){
					utils::a2o($macinfosDB[$macinfo['id']], $macinfo);
					$macinfosDB[$macinfo['id']]->save();
					unset ($macinfosDB[$macinfo['id']]);
				} else {
					$macinfoObj = new devolo_macinfo();
					utils::a2o($macinfosObj, $macinfo);
					$macinfoObj->save();
				}
			}
			foreach ($macinfosDB as $macinfoObj) {
				$macinfoObj->remove();
			}
			$return = [];
			foreach (devolo_macinfo::all() as $macinfoObj) {
				$return[] = utils::o2a($macinfoObj);
			}
			ajax::success(json_encode($return));
		} catch (Exception $e){
			ajax::error(displayException($e). $e->getCode());
		}
	}

	throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
	/*     * *********Catch exeption*************** */
}
catch (Exception $e) {
	ajax::error(displayException($e), $e->getCode());
}
