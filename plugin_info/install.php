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

function devolo_cpl_upgrade() {
	$pluginLevel = config::byKey('pluginLevel','devolo_cpl',0);
	log::add("devolo_cpl","info","pluginLevel: " . $pluginLevel);
	if ($pluginLevel < 1) {
		foreach (devolo_cpl::byType('devolo_cpl') as $eqLogic){
			$eqLogic->createCmds(1);
			$eqLogic->save();
		}
		config::save('pluginLevel',1,'devolo_cpl');
		$pluginLevel = 1;
		log::add("devolo_cpl","info","pluginLevel: " . $pluginLevel);
	}
	if ($pluginLevel < 2) {
		foreach (devolo_cpl::byType('devolo_cpl') as $eqLogic){
			$eqLogic->createCmds(2);
			$eqLogic->save();
		}
		config::save('pluginLevel',2,'devolo_cpl');
		$pluginLevel = 2;
		log::add("devolo_cpl","info","pluginLevel: " . $pluginLevel);
	}
}

// Fonction exécutée automatiquement après l'installation du plugin
function devolo_cpl_install() {
	log::add("devolo_cpl","warning","devolo_cpl_install");
	devolo_cpl_checkMac();
	devolo_cpl_upgrade();
}

// Fonction exécutée automatiquement après la mise à jour du plugin
function devolo_cpl_update() {
	devolo_cpl_checkMac();
	devolo_cpl_upgrade();
}

// Fonction exécutée automatiquement après la suppression du plugin
function devolo_cpl_remove() {
}
