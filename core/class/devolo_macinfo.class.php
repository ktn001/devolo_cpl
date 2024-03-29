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

class devolo_macinfo {
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

	public static function all() {
		$sql  = 'SELECT ' . DB::buildField(__CLASS__);
		$sql .= '  FROM devolo_macinfo';
		return DB::Prepare($sql, [], DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
	}

	public static function byId ($_id) {
		if ($_id == '') {
			return;
		}
		$value = array(
			'id' => $_id,
		);
		$sql  = 'SELECT ' . DB::buildField(__CLASS__);
		$sql .= '  FROM devolo_macinfo';
		$sql .= ' WHERE id=:id';
		return DB::Prepare($sql, $value, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
	}

	public static function byMac ($_mac, $create=false) {
		if ($_mac == '') {
			return;
		}
		$value = array(
			'mac' => $_mac,
		);
		$sql  = 'SELECT ' . DB::buildField(__CLASS__);
		$sql .= '  FROM devolo_macinfo';
		$sql .= ' WHERE mac=:mac';
		$mac = DB::Prepare($sql, $value, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
		if (is_object($mac)) {
			return $mac;
		}
		if ($create) {
			$mac = new devolo_macinfo();
			$mac->setMac($_mac);
			return $mac;
		}
		return;
	}

	public static function actualize () {
		$macs = devolo_connection::macList();
		foreach ($macs as $mac) {
			$macinfo = devolo_macinfo::byMac($mac);
			if (is_object($macinfo)){
				continue;
			}
			log::add("devolo_cpl","info",sprintf(__("Recherche du vendeur pour %s",__FILE__),$mac));
			$url = "https://api.macvendors.com/" . urlencode($mac);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$response = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$macinfo = new devolo_macinfo();
			$macinfo->setMac($mac);
			if ($httpcode == "200") {
				$macinfo->setVendor($response);
			} else {
				$macinfo->setVendor('inconnu');
			}
			$macinfo->save();
			sleep(1);
		}

	}

	public static function getMacWifiToGraph() {
		$sql  = "SELECT mac,";
		$sql .= "       if (ISNULL(name) or name = '',mac,name) AS displayname";
		$sql .= "  FROM devolo_macinfo";
		$sql .= " ORDER BY displayname";
		return DB::Prepare($sql, [], DB::FETCH_TYPE_ALL);
	}

	/*     * *******************Méthodes d'instance************************** */

	public function getTableName() {
		return 'devolo_macinfo';
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

	public function remove() {
		DB::remove($this);
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

	public function getName() {
		return $this->name;
	}

	public function setName ($_name) {
		if ($_name != $this->name) {
			$this->name = $_name;
			$this->_changed = true;
		}
	}

}
