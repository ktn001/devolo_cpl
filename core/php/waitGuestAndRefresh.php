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

require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once __DIR__ . '/devolo_cpl.inc.php';

$options = getopt('i:');
if (! ($options and array_key_exists("i", $options))) {
	log::add("devolo_cpl","error", __FILE__ . ": " .  __("option -i manquante",__FILE__));
	exit (1);
}

$cmd = devolo_cplCmd::byId($options['i']);
if (! is_object($cmd)){
	log::add("devolo_cpl","error", __FILE__ . ": " .  sprintf(__("CMD id %s introuvable!",__FILE__),$options['i']));
	exit (2);
}
if ($cmd->getEqType_name() != 'devolo_cpl') {
	log::add("devolo_cpl","error", __FILE__ . ": " . sprintf(__("La commande %s n'est pas une commande d'un équipement devolo_cpl",__FILE__),$options['i']));
	exit (2);
}
if ($cmd->getLogicalId() != 'guest') {
	log::add("devolo_cpl","error", __FILE__ . ": " . sprintf(__("Le logicalId de la commande %s n'est pas guest",__FILE__),$options['i']));
	exit (2);
}

log::add("devolo_cpl","debug", __FILE__ . ": " . sprintf(__("Attente de la cmd %s",__FILE__),$options['i']));

for ($i=0; $i < 20; $i++){
	if ($cmd->execCmd() == 1) {
		break;
	}
	usleep(500000);
	log::add("devolo_cpl","debug",$i);
}

exit(0);
