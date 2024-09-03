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
		message::add("devolo_cpl",__("Veuillez renseigner l'adresse mac dans la configuration des équipements",__FILE__));
	}
}

function devolo_cpl_goto_13() {
	foreach (devolo_cpl::byType('devolo_cpl') as $eqLogic){
		$eqLogic->setConfiguration("alert_offline",1);
		$eqLogic->save();
	}
}

function devolo_cpl_goto_12() {
	config::save('devolo_plc_api::version','1.3.2',devolo_cpl);
}

function devolo_cpl_goto_11() {
	foreach (devolo_cpl::byType('devolo_cpl') as $eqLogic){
		$cmd = $eqLogic->getCmd('info','guest_remaining');
		if (! is_object($cmd)){
			continue;
		}
		$changed = false;
		if ($cmd->getTemplate('dashboard') == 'default') {
			$cmd->setTemplate('dashboard','devolo_cpl::j_h_m');
			$changed = true;
		}
		if ($cmd->getTemplate('mobile') == 'default') {
			$cmd->setTemplate('mobile','devolo_cpl::j_h_m');
			$changed = true;
		}
		if ($changed) {
			$cmd->save();
		}

		$cmd = $eqLogic->getCmd('action','guest_duration');
		if (! is_object($cmd)){
			continue;
		}
		$changed = false;
		if ($cmd->getTemplate('dashboard') == 'default') {
			$cmd->setTemplate('dashboard','devolo_cpl::j_h_m');
			$changed = true;
		}
		if ($cmd->getTemplate('mobile') == 'default') {
			$cmd->setTemplate('mobile','devolo_cpl::j_h_m');
			$changed = true;
		}
		if ($changed) {
			$cmd->save();
		}
	}
}

function devolo_cpl_goto_10() {
	config::save('devolo_plc_api::version','1.3.1',devolo_cpl);
}

function devolo_cpl_goto_9() {
	config::save('devolo_plc_api::version','1.3.0',devolo_cpl);
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
		$statments = explode(";",$sql);
		foreach ($statments as $statment) {
			log::add("devolo_cpl","info","exeution de  " . $statment);
			DB::Prepare($statment, array(), DB::FETCH_TYPE_ALL);
		}
	}
	$function = 'devolo_cpl_goto_' . $level;
	if (function_exists($function)){
		log::add("devolo_cpl","debug","execution de " . $function . "()");
		$function();
	}
}

function devolo_cpl_upgrade() {

	$lastLevel = 13;

	$pluginLevel = config::byKey('pluginLevel','devolo_cpl',0);
	log::add("devolo_cpl","info","pluginLevel: " . $pluginLevel . " => " . $lastLevel);
	for ($level = 1; $level <= $lastLevel; $level++) {
		if ($pluginLevel < $level) {
			devolo_upgrade_to_level($level);
			config::save('pluginLevel',$level,'devolo_cpl');
			$pluginLevel = $level;
			log::add("devolo_cpl","info","pluginLevel: " . $pluginLevel);
		}
	}
}

// Fonction exécutée automatiquement après l'installation du plugin
function devolo_cpl_install() {
	log::add("devolo_cpl","info","Lancement de 'devolo_cpl_install()'");
	devolo_cpl_checkMac();
	devolo_cpl_upgrade();
	devolo_cpl::setListeners();
}

// Fonction exécutée automatiquement après la mise à jour du plugin
function devolo_cpl_update() {
	log::add("devolo_cpl","info","Lancement de 'devolo_cpl_update()'");
	devolo_cpl_checkMac();
	devolo_cpl_upgrade();
	devolo_cpl::setListeners();
}
