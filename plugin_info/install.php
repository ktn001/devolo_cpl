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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function devolo_cpl_checkMac() {
	$ok = true;
	foreach (devolo_cpl::byType('devolo_cpl') as $eqLogic){
		if ($eqLogic->getIsEnable() or $eqLogic->getConfiguration('previousIsEnable')) {
			if ($eqLogic->getConfiguration('mac') == '') {
				$ok = false;
				break;
			}
		}
	}
	if (! $ok) {
		message::add("devolo_cpl",__("Veuillez renseigner l'adresse mac dans la configuration des √©quipements",__FILE__));
	}
}

function devolo_cpl_goto_7() {
	config::save('displayDesktopPanel','1',devolo_cpl);
}

function devolo_cpl_goto_6() {
	foreach (devolo_cpl::byType('devolo_cpl') as $eqLogic){
		$eqLogic->sortCmds();
	}
}

function devolo_cpl_goto_4() {
	config::save('data-retention','7 DAY',devolo_cpl);
}

function devolo_cpl_goto_3() {
	config::save('devolo_plc_api::version','1.2.0',devolo_cpl);
}

function devolo_upgrade_to_level($level) {
	foreach (devolo_cpl::byType('devolo_cpl') as $eqLogic){
		$eqLogic->createCmds($level);
		$eqLogic->save();
	}
	$sqlFile = __DIR__ . "/sql/upgrade_" . $level . ".sql";
	if (file_exists($sqlFile)){
		$sql = file_get_contents($sqlFile);
		DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
	}
	$function = 'devolo_cpl_goto_' . $level;
	if (function_exists($function)){
		log::add("devolo_cpl","debug","execution de " . $function . "()");
		$function();
	}
}

function devolo_cpl_upgrade() {
	$pluginLevel = config::byKey('pluginLevel','devolo_cpl',0);
	log::add("devolo_cpl","info","pluginLevel: " . $pluginLevel);
	for ($level = 1; $level <= 7; $level++) {
		if ($pluginLevel < $level) {
			devolo_upgrade_to_level($level);
			config::save('pluginLevel',$level,'devolo_cpl');
			$pluginLevel = $level;
			log::add("devolo_cpl","info","pluginLevel: " . $pluginLevel);
		}
	}
}

// Fonction ex√©cut√©e automatiquement apr√®s l'installation du plugin
function devolo_cpl_install() {
	log::add("devolo_cpl","info","Lancement de 'devolo_cpl_install()'");
	devolo_cpl_checkMac();
	devolo_cpl_upgrade();
	$plc_api_version = config::byKey('devolo_plc_api::version',devolo_cpl);
	config::save('devolo_plc_api::version',$plc_api_version,devolo_cpl);
}

// Fonction ex√©cut√©e automatiquement apr√®s la mise √† jour du plugin
function devolo_cpl_update() {
	log::add("devolo_cpl","info","Lancement de 'devolo_cpl_update()'");
	devolo_cpl_checkMac();
	devolo_cpl_upgrade();
	$plc_api_version = config::byKey('devolo_plc_api::version',devolo_cpl);
	config::save('devolo_plc_api::version',$plc_api_version,devolo_cpl);
}

// Fonction ex√©cut√©e automatiquement apr√®s la suppression du plugin
// function devolo_cpl_remove() {
// 	$sql = 'DROP TABLE IF EXISTS devolo_cpl_rates';
// 	log::add("devolo_cpl","debug",$sql);
// 	$response = DB::Prepare($sql,array(), DB::FETCH_TYPE_ALL);
// 	log::add("devolo_cpl","debug",$response);
// }
