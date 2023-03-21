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

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';

function isRandomMac ($_mac) {
	return in_array(substr($_mac,1,1),['2','4','A','a','E','e']);
}

class devolo_mac_info {
	/*     * *************************Attributs****************************** */

	private $id;
	private $mac;
	private $vendor;
	private $name;
	private $_changed = false;

	/*     * ********************Méthodes statiques************************** */

	public static function isRandom () {
		return isRandomMac ($this->mac);
	}

	public static function byMac ($_mac, $create=false) {
		if ($_mac == '') {
			return;
		}
		$value = array(
			'mac' => $_mac,
		);
		$sql  = 'SELECT ' . DB::buildField(__CLASS__);
		$sql .= '  FROM devolo_mac_info';
		$sql .= ' WHERE mac=:mac';
		$mac = DB::Prepare($sql, $value, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
		if (is_object($mac)) {
			return $mac;
		}
		if ($create) {
			$mac = new devolo_mac_info();
			$mac->setMac($_mac);
			return $mac;
		}
		return;
	}

	public static function actualize () {
		$macs = devolo_connection::macList();
		foreach ($macs as $mac) {
			$mac_info = devolo_mac_info::byMac($mac);
			if (is_object($mac_info)){
				continue;
			}
			log::add("devolo_cpl","info",sprintf(__("Recherche du vendeur pour %s",__FILE__),$mac));
			$url = "https://api.macvendors.com/" . urlencode($mac);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$response = curl_exec($ch);
			$mac_info = new devolo_mac_info();
			$mac_info->setMac($mac);
			$mac_info->setVendor($response);
			$mac_info->save();
			sleep(1);
		}

	}

	/*     * *******************Méthodes d'instance************************** */

	public function getTableName() {
		return 'devolo_mac_info';
	}

	public function save() {
		if ($this->getId() == '') {
			$stored = self::byMac($this->getMac());
			if (is_object($stored)) {
				$this->setId($stored->getId());
			}
		}
		DB::save($this);
	}

	/*     * **********************Getteur Setteur*************************** */

	public function getId() {
		return $this->id;
	}

	public function setId ($_id) {
		$this->id = $_id;
	}

	public function getMac() {
		return $this->mac;
	}

	public function setMac ($_mac) {
		if ($_mac != $this->mac) {
			$this->mac = $_mac;
			$this->_changed = true;
		}
	}

	public function getVendor() {
		return $this->vendor;
	}

	public function setVendor ($_vendor) {
		if ($_vendor != $this->vendor) {
			$this->vendor = $_vendor;
			$this->_changed = true;
		}
	}

}
