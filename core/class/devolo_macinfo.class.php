<?php
// vi: tabstop=4 autoindent
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

	/*     * ********************Méthodes statiques************************** */

	public static function all() {
		$sql  = 'SELECT ' . DB::buildField(__CLASS__);
		$sql .= '  FROM devolo_macinfo';
		return DB::Prepare($sql, [], DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
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
		$macInfo = DB::Prepare($sql, $value, DB::FETCH_TYPE_ROW, PDO::FETCH_CLASS, __CLASS__);
		if (is_object($macInfo)) {
			return $macInfo;
		}
		if ($create) {
			$macInfo = new devolo_macinfo();
			$macInfo->setMac($_mac);
			$macInfo->recupVendor();
			$macInfo->save();
			return self::byMac ($_mac);
		}
		return False;
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
		DB::save($this);
	}

	public function remove() {
		DB::remove($this);
	}

	public function isRandom () {
		return isRandomMac ($this->mac);
	}

	public function recupVendor() {
		log::add("devolo_cpl","debug",sprintf(__("Recherche du vendeur pour %s",__FILE__),$this->mac));
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
	}

	public function searchAddr ($ping = false) {
		log::add("devolo_cpl","debug",sprintf(__("Recherche de l'adresse pour %s",__FILE__),$this->mac));
		if (! exec ('/usr/sbin/arp -a', $output)) {
			log::add("devolo_cpl","warning",sprintf(__("Erreur lors de l'exécution de %s",__FILE__),"/usr/bin/arp"));
			return;
		}
		array_shift($output);
		foreach ($output as $line) {
			if (preg_match('/incomplete/', $line)) {
				continue;
			}
			$tokens = explode(" ",$line);
			if (strtoupper($tokens[3]) == $this->mac) {
				if ($tokens[0] == '?') {
					return trim($tokens[1],'()');
				} else {
					return $tokens[0];
				}
			}
		}
		if ($ping) {
		unset($output);
			exec('/usr/sbin/ip add', $output);
			$brdAddrs = array();
			foreach ($output as $line) {
				$token = preg_split('/\s+/',$line);
				if ($token[1] == 'inet' and $token[3] == 'brd') {
					$brdAddrs[$token[4]] = 1;
				}
			}
			foreach (array_keys($brdAddrs) as $brdAddr) {
				exec(sprintf("/usr/bin/ping -c 1 -b %s > /dev/null 2>&1 &", $brdAddr));
			}
		}
		return $this->getName();

	}

	/*     * **********************Getteur Setteur*************************** */

	public function getId() {
		return $this->id;
	}

	public function setId ($_id) {
		if ($_id != $this->id) {
			$this->id = $_id;
		}
		return $this;
	}

	public function getMac() {
		return $this->mac;
	}

	public function setMac ($_mac) {
		if ($_mac != $this->mac) {
			$this->mac = $_mac;
		}
		return $this;
	}

	public function getVendor() {
		return $this->vendor;
	}

	public function setVendor ($_vendor) {
		if ($_vendor != $this->vendor) {
			$this->vendor = $_vendor;
		}
		return $this;
	}

	public function getName() {
		return $this->name;
	}

	public function setName ($_name) {
		if ($_name != $this->name) {
			$this->name = $_name;
		}
		return $this;
	}

}
