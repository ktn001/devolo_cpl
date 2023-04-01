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

class devolo_connection {
	/*     * *************************Attributs****************************** */

	private $id;
	private $serial;
	private $mac;
	private $ip;
	private $network;
	private $connect_time;
	private $disconnect_time;
	private $_changed = false;

	/*     * ********************Méthodes statiques************************** */

	public static function byId($_id) {
		if ($_id == '') {
			return;
		}
		$value = array(
			'id' => $_id,
		);
		$sql = 'SELECT ' . DB::buildField(__CLASS__) . '
			FROM devolo_connection
			WHERE id=:id';
		return DB::Prepare($sql, $value, DB::FETCH_TYPE_ROW, __CLASS__);
	}

	public static function byMac($mac, $connectedOnly = true) {
		$value = array(
			'mac' => $mac,
		);
		$sql  = 'SELECT ' . DB::buildField(__CLASS__);
		$sql .= ' FROM devolo_connection';
		$sql .= ' WHERE mac = :mac';
		if ($connectedOnly) {
			$sql .= ' AND ISNULL (disconnect_time)';
		}
		return DB::Prepare($sql, $value, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
	}

	public static function bySerial($serial, $connectedOnly = true) {
		$value = array(
			'serial' => $serial,
		);
		$sql  = 'SELECT ' . DB::buildField(__CLASS__);
		$sql .= '  FROM devolo_connection';
		$sql .= ' WHERE serial = :serial';
		if ($connectedOnly) {
			$sql .= ' AND ISNULL (disconnect_time)';
		}
		return DB::Prepare($sql, $value, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
	}

	public static function macList() {
		$sql = 'SELECT DISTINCT mac from devolo_connection';
		$result =  DB::Prepare($sql, [], DB::FETCH_TYPE_ALL);
		$macs = [];
		foreach ($result as $row) {
			array_push($macs, $row['mac']);
		}
		return $macs;
	}

	public static function byNoIp() {
		$sql  = 'SELECT ' . DB::buildField(__CLASS__);
		$sql .= '  FROM devolo_connection';
		$sql .= ' WHERE ISNULL (ip)';
		$sql .= '   AND ISNULL (disconnect_time)';
		return DB::Prepare($sql, [], DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
	}

	public static function getWifiHistorique_ap($serial) {
		$value = array(
			'serial' => $serial,
		);
		$sql  = "SELECT macinfo.mac, network,";
		$sql .= "       unix_timestamp(connect_time) * 1000 as connect_time,";
		$sql .= "       unix_timestamp(IFNULL (disconnect_time,now())) * 1000 as disconnect_time,";
		$sql .= "       if (ISNULL(name) or name = '',connection.mac,name) AS category";
		$sql .= "  FROM devolo_connection AS connection";
		$sql .= "  JOIN devolo_macinfo AS macinfo ON connection.mac = macinfo.mac";
		$sql .= " WHERE serial = :serial";
		$sql .= " ORDER BY connect_time";
		return DB::Prepare($sql, $value, DB::FETCH_TYPE_ALL);
	}

	public static function getWifiHistorique_client($mac) {
		$value = array(
			'mac' => $mac,
		);
		$sql  = "SELECT serial as category, mac, network,";
		$sql .= "       unix_timestamp(connect_time) * 1000 as connect_time,";
		$sql .= "       unix_timestamp(IFNULL (disconnect_time,now())) * 1000 as disconnect_time";
		$sql .= "  FROM devolo_connection";
		$sql .= " WHERE mac = :mac";
		$sql .= " ORDER BY connect_time";
		return DB::Prepare($sql, $value, DB::FETCH_TYPE_ALL);
	}

	public static function set_connected_devices ($serial, $connected_devices) {
		if (! exec ('/usr/sbin/arp', $output)) {
			log::add("devolo_cpl","warning","Erreur lors de l'exécution de /usr/bin/arp");
			return;
		}
		array_shift($output);
		$ip = [];
		foreach ($output as $line) {
			if (preg_match('/\(incomplete\)/', $line)){
				continue;
			}
			$tokens = preg_split('/\s+/',$line);
			$ip[strtoupper($tokens[2])] = $tokens[0];
		}
		$mac_connected = [];
		foreach ($connected_devices as $connected_device) {
			array_push($mac_connected, $connected_device['mac']);
			$connections = devolo_connection::byMac($connected_device['mac']);
			$found = false;
			foreach ($connections as $connection) {
				if (   ($connection->getSerial() == $serial)
				    && ($connection->getMac() == $connected_device['mac'])
				    && ($connection->getNetwork() == $connected_device['band'])
				) {
					$found = true;
				} else {
					$connection->setDisconnect_time(date('Y-m-d H:i:s'));
					$connection->save();
				}
			}
			if (! $found) {
				$connection = new devolo_connection();
				$connection->setSerial($serial);
				$connection->setMac($connected_device['mac']);
				$connection->setNetwork($connected_device['band']);
				$connection->setConnect_time(date('Y-m-d H:i:s'));
				if (array_key_exists($connected_device['mac'], $ip)) {
					$connection->setIp($ip[$connected_device['mac']]);
				}
				$connection->save();
			}
		}
		$connections = devolo_connection::bySerial($serial);
		foreach ($connections as $connection) {
			if (! in_array($connection->getMac(), $mac_connected)){
				$connection->setDisconnect_time(date('Y-m-d H:i:s'));
				$connection->save();
			}
		}
	}

	public static function setIps() {
		$connection = devolo_connection::byNoIp();
		if (! exec ('/usr/sbin/arp', $result)) {
			log::add("devolo_cpl","warning","Erreur lors de l'exécution de /usr/bin/arp");
			return;
		}
		log::add("devolo_cpl","info",print_r($result,true));
		foreach ($connections as $connection) {
			$mac = $connection->getMac();

		}
	}

	/*     * ********************Méthodes d'instance************************** */

	public function getTableName() {
		return 'devolo_connection';
	}

	public function save($_direct = false) {
		if (!$this->_changed) {
			return true;
		}
		$return = DB::save($this, $_direct);
		if ($return) {
			$this->_changed = false;
		}
		return $return;
	}

	public function postSave() {
		devolo_macinfo::actualize();
	}

	public function remove() {
		return DB::remove($this);
	}

	/*     * ***********************Getteur Setteur*************************** */

	public function getId() {
		return $this->id;
	}

	public function setId ($_id) {
		if ($_id != $this->id) {
			$this->id = $_id;
			$this->_changed = true;
		}
	}

	public function getSerial() {
		return $this->serial;
	}

	public function setSerial ($_serial) {
		if ($_serial != $this->serial) {
			$this->serial = $_serial;
			$this->_changed = true;
		}
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

	public function getIp() {
		return $this->ip;
	}

	public function setIp ($_ip) {
		if ($_ip != $this->ip) {
			$this->ip = $_ip;
			$this->_changed = true;
		}
	}

	public function getNetwork() {
		return $this->network;
	}

	public function setNetwork ($_network) {
		if ($_network != $this->network) {
			$this->network = $_network;
			$this->_changed = true;
		}
	}

	public function getConnect_time() {
		return $this->connect_time;
	}

	public function setConnect_time ($_connect_time) {
		if ($_connect_time != $this->connect_time) {
			$this->connect_time = $_connect_time;
			$this->_changed = true;
		}
	}

	public function getDisconnect_time() {
		return $this->disconnect_time;
	}

	public function setDisconnect_time ($_disconnect_time) {
		if ($_disconnect_time != $this->disconnect_time) {
			$this->disconnect_time = $_disconnect_time;
			$this->_changed = true;
		}
	}

}
