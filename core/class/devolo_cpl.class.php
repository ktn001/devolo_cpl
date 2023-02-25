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
require_once __DIR__  . '/../php/devolo_cpl.inc.php';

class devolo_cpl extends eqLogic {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */

    /*
    * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
    */
    public static function cron5() {
	$equipements = eqLogic::byType(__CLASS__,True);
	foreach($equipements as $equipement) {
	    if (! $equipement->isManageable()){
		continue;
	    }
	    log::add("devolo_cpl","debug","cron: " . $equipement->getName());
	    $equipement->getEqState();
	}
	devolo_cpl::getRates();
    }

    public static function cronDaily() {
	self::purgeDB();
    }

    /*
     * Changement de version pour devolo_plc_api
     */
    public static function preConfig_devolo_plc_api_version($version){
        $etcPath = __DIR__ . '/../../resources/etc';
	if (! file_exists($etcPath)){
	    mkdir ($etcPath);
	    chmod ($etcPath, 0775);
	}
	$versionFile = $etcPath . '/devolo_plc_api.version';
	file_put_contents($versionFile, $version);
	return $version;
    }

    /*
     * Etat du daemon
     */
    public static function deamon_info() {
	return self::daemon_info();
    }
    public static function daemon_info() {
        $return = array();
        $return['log'] = __CLASS__;
        $return['state'] = 'nok';
        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/daemon.pid';
        if (file_exists($pid_file)) {
            if (@posix_getsid(trim(file_get_contents($pid_file)))) {
                $return['state'] = 'ok';
            } else {
                shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
            }
        }
        $return['launchable'] = 'ok';
        return $return;
    }

    /*
     * Lancement de daemon
     */
    public static function deamon_start() {
	return self::daemon_start();
    }
    public static function daemon_start() {
        self::daemon_stop();
        $daemon_info = self::daemon_info();
        if ($daemon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }

        $path = realpath(dirname(__FILE__) . '/../../resources/bin/');
        $cmd = 'python3 ' . $path . '/devolo_cpld.py';
        $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
        $cmd .= ' --socketport ' . config::byKey('daemon::port', __CLASS__ );
        $cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/devolo_cpl/core/php/jeedevolo_cpl.php'; // chemin de la callback url à modifier (voir ci-dessous)
        $cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__); // l'apikey pour authentifier les échanges suivants
        $cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/daemon.pid';
        log::add(__CLASS__, 'info', 'Lancement démon');
        $result = exec($cmd . ' >> ' . log::getPathToLog('devolo_cpl_daemon') . ' 2>&1 &');
        $i = 0;
        while ($i < 20) {
            $daemon_info = self::daemon_info();
            if ($daemon_info['state'] == 'ok') {
                break;
            }
            sleep(1);
            $i++;
        }
        if ($i >= 30) {
            log::add(__CLASS__, 'error', __('Impossible de lancer le démon, vérifiez le log', __FILE__));
            return false;
        }
        message::removeAll(__CLASS__, 'unableStartDaemon');
        return true;
    }

    /*
     * Arrêt du daemon
     */
    public static function deamon_stop() {
    	return self::daemon_stop();
    }
    public static function daemon_stop() {
        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/daemon.pid'; // ne pas modifier
        if (file_exists($pid_file)) {
            $pid = intval(trim(file_get_contents($pid_file)));
            system::kill($pid);
        }
        sleep(2);
        system::kill('python.*devolo_cpld.py'); // nom du démon à modifier
        sleep(1);
    }

    /*
     * Purge de données historiques
     */
    public static function purgeDB() {
	$retention = config::byKey('data-retention','devolo_cpl');
	$sql = "DELETE FROM devolo_cpl_rates";
	$sql .= " WHERE time < DATE_SUB(now(), INTERVAL " . $retention . ")";
	log::add("devolo_cpl","debug",$sql);
	$response = DB::Prepare($sql,array(), DB::FETCH_TYPE_ALL);
	log::add("devolo_cpl","debug",$response);
    }

    /*
     * Widgets spécifiques
     */
    public static function templateWidget() {
	$return = [
	    'action' => [
		'other' => [
		    'locate' => [
			'template' => 'tmplicon',
			'replace' => [
			    '#_icon_on_#' => '<i class="icon_green icon fas fa-podcast"></i>',
			    '#_icon_off_#' => '<i class="icon_blue icon jeedom2-case"></i>'
			]
		    ]
		]
	    ]
	];
	return $return;
    }

    /*
     * Cherche un équipement avec une mac adresse donnée
     */
    public static function byMacAddress ($_macAddress) {
	$eqLogics = devolo_cpl::byTypeAndSearchConfiguration(__CLASS__,['mac' => $_macAddress]);
	if (count($eqLogics > 1)) {
	    log::add("devolo_cpl","warning",sprintf(__("Il y a plusieurs équipements avec l'adresse mac %s",__FILE__),$_macAddress));
	}
	return $eqLogics[0];
    }

    /*
     * Création ou mise à jour d'un équipment suite à une synchro
     */
    public static function createOrUpdate($equipement){
	if (!is_array($equipement)) {
	    throw new Exception(__('Information reçues incorrectes',__FILE__));
	}

	if (!array_key_exists('serial',$equipement)) {
	    throw new Exception (__("Le n° de serie est indéfini!",__FILE__));
	}
	if (!array_key_exists('name',$equipement) || $equipement['name'] == '') {
	    $equipement['name'] = $equipement['serial'];
	}
	$eqLogic = devolo_cpl::byLogicalId($equipement['serial'],__CLASS__);
	if (is_object($eqLogic)) {
	    log::add("devolo_cpl","debug",sprintf(__("Mise à jour de '%s'",__FILE__),$equipement['name']));
	    $modified = false;
	    if ($eqLogic->getName() != $equipement['name']){
		log::add("devolo_cpl","info",__("Le nom de l'équipement a été changé:",__FILE__) . " " . $eqLogic->getName() . " => " . $equipement['name']);
		$modified = true;
		$eqLogic->setName($equipement['name']);
	    }
	    if ($eqLogic->getConfiguration("sync_model") != $equipement['model']){
		log::add("devolo_cpl","info",sprintf(__("Le model de l'équipement %s été changé:",__FILE__),$eqLogic->getName()) . " " . $eqLogic->getConfiguration('model') . " => " . $equipement['model']);
		$modified = true;
		$eqLogic->setConfiguration('sync_model',$equipement['model']);
		$eqLogic->setConfiguration('model',$equipement['model']);
	    }
	    if (strtoupper($eqLogic->getConfiguration("mac")) != str_replace(":","",$equipement['mac'])){
		log::add("devolo_cpl","info",sprintf(__("L'adresse mac de l'équipement %s été changé:",__FILE__),$eqLogic->getName()) . " " . $eqLogic->getConfiguration('mac') . " => " . $equipement['mac']);
		$modified = true;
		$eqLogic->setConfiguration('mac',$equipement['mac']);
	    }
	    if ($eqLogic->getConfiguration("ip") != $equipement['ip']){
		log::add("devolo_cpl","info",sprintf(__("L'ip de l'équipement %s été changé:",__FILE__),$eqLogic->getName()) . " " . $eqLogic->getConfiguration('ip') . " => " . $equipement['ip']);
		$modified = true;
		$eqLogic->setConfiguration('ip',$equipement['ip']);
	    }
	    if ($modified) {
		$eqLogic->save();
	    }
	} else {
	    log::add("devolo_cpl","debug",sprintf(__("Créaction de '%s'",__FILE__),$equipement['name']));
	    $devolo = new devolo_cpl();
	    $devolo->setName($equipement['name']);
	    $devolo->setEqType_name(__CLASS__);
	    $devolo->setLogicalId($equipement['serial']);
	    $devolo->setConfiguration("sync_model",$equipement['model']);
	    $devolo->setConfiguration("ip",$equipement['ip']);
	    if (model::byCode($equipement['model'] == Null)) {
		$devolo->setConfiguration("model","autre");
	    } else {
		$devolo->setConfiguration("model",$equipement['model']);
	    }
	    $devolo->save();
	}
    }

    /*
     * Lancement d'une synchro
     */
    public static function syncDevolo() {
	$path = realpath(dirname(__FILE__) . '/../../resources/bin');
	$cmd = "python3 " . $path . '/devolo_cpl.py';
	$cmd .= ' --syncDevolo';
	$cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
	$cmd .= ' 2>>' . log::getPathToLog('devolo_cpl_out');
	$lines = [];
	$result = exec($cmd ,$lines, $exitStatus);
	if ($result === false) {
	    throw new Exception(__("Erreur lors du lancement de syncDevolo.py",__FILE__));
	}
	if ($exitStatus != 0) {
	    throw new Exception(__("Erreur lors de l'exécution de syncDevolo.py",__FILE__));
	}
	log::add("devolo_cpl","info", join(" ",$lines));
	$equipements = json_decode(join(" ",$lines),true);
	foreach ($equipements as $equipement) {
	    log::add("devolo_cpl","debug",print_r($equipement,true));
	    self::createOrUpdate($equipement);
	}
    }

    public static function getCplNetworksToModal() {
	$eqLogics = devolo_cpl::byType('devolo_cpl',true);
	$networks = [];
	foreach ($eqLogics as $eqLogic) {
	    $network = $eqLogic->getConfiguration('network','cpl');
	    if (!isset ($networks[$network])){
		$networks[$network] = [];
	    }
	    $humanName = $eqLogic->getHumanName(true);
	    $networks[$network][$humanName] = [
		'id' => $eqLogic->getId(),
		'macAddress' => $eqLogic->getConfiguration('mac'),
		'img' => $eqLogic->getImage(),
	    ];
	    $infos = $eqLogic->getModelInfos();
	    if (isset($infos['cpl_speed'])){
		$networks[$network][$humanName]['cpl_speed'] = $infos['cpl_speed'];
	    } else {
		$networks[$network][$humanName]['cpl_speed'] = '';
	    }
	}
	return $networks;
    }

    public static function getCplRatesToModal() {
        $sql = "select `time`, `mac_address_from`, `mac_address_to`, `tx_rate`, `rx_rate`";
	$sql .=  "from `devolo_cpl_rates`";
	$sql .= "where time = (select max(time) from `devolo_cpl_rates`)";
	return  DB::Prepare($sql,array(), DB::FETCH_TYPE_ALL);
    }

    public static function getRates() {
	$equipements = eqLogic::byType(__CLASS__,True);
	$ipList = [];
	foreach($equipements as $equipement) {
	    $ip = $equipement->getConfiguration('ip');
	    if ($ip){
		$ipList[] = $ip;
	    }
	}
	$params['action'] = 'getRates';
	$params['ip'] = join(':',$ipList);
	self::sendToDaemon($params);
    }

    public static function sendToDaemon($params) {
        $daemon_info = self::daemon_info();
        if ($daemon_info['state'] != 'ok') {
            throw new Exception("Le démon n'est pas démarré");
        }
        $params['apikey'] = jeedom::getApiKey(__CLASS__);
        $payLoad = json_encode($params);
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        socket_connect($socket, '127.0.0.1', config::byKey('socketport', __CLASS__, config::byKey('daemon::port',__CLASS__)));
        socket_write($socket, $payLoad, strlen($payLoad));
        socket_close($socket);
    }

    /*     * *********************Méthodes d'instance************************* */

    public function PrepareToDaemon($params) {
	$params['serial'] = $this->getLogicalId();
	$params['ip'] = $this->getConfiguration('ip');
	$params['password'] = $this->getConfiguration('password');
	devolo_cpl::sendToDaemon($params);
    }

    // Remontée de l'état de l'équipement
    public function getEqState () {
	$this::PrepareToDaemon(['action' => 'getState']);
    }

    // Function pour tirer les commandes
    public function sortCmds () {
	log::add("devolo_cpl","info",sprintf(__("Tri des commandes pour l'équipement %s (%s)",__FILE__),$this->getName(), $this->getLogicalId()));
	$cmdFile = __DIR__ . "/../config/cmds.json"; 
	$configs =  json_decode(file_get_contents($cmdFile),true);
	foreach ($configs as $logicalId => $config) {
	    $cmd = $this->getCmd(null, $logicalId);
	    if (! is_object($cmd)) {
		continue;
	    }
	    $cmd->setOrder($config['order']);
	    $cmd->save();
	}
    }

    // Function pour la création des CMD
    public function createCmds ($level=0) {
	log::add("devolo_cpl","info",sprintf(__("Création des commandes manquantes pour l'équipement %s (%s)",__FILE__),$this->getName(), $this->getLogicalId()));
	
	$cmdFile = __DIR__ . "/../config/cmds.json"; 
	$configs =  json_decode(file_get_contents($cmdFile),true);
	foreach ($configs as $logicalId => $config) {
	    if ($level != 0 and $level != $config['level']){
		continue;
	    }
	    if (! $this->isManageable() and $config['manageableOnly']){
		continue;
	    }
	    $cmd = $this->getCmd(null, $logicalId);
	    if (is_object($cmd)) {
		continue;
	    }
	    log::add("devolo_cpl","info",sprintf(__("  cmd: %s (première passe)",__FILE__),$logicalId));
	    $cmd = new devolo_cplCMD();
	    $cmd->setEqLogic_id($this->getId());
	    $cmd->setLogicalId($logicalId);
	    $cmd->setName(translate::exec($config['name'],$cmdFile));
	    $cmd->setType($config['type']);
	    $cmd->setSubType($config['subType']);
	    $cmd->setOrder($config['order']);
	    if (isset($config['visible'])){
		$cmd->setIsVisible($config['visible']);
	    }
	    if (isset($config['configuration'])){
		if (isset($config['configuration']['returnStateValue'])){
		    $cmd->setConfiguration('returnStateValue',$config['configuration']['returnStateValue']);
		}
		if (isset($config['configuration']['returnStateTime'])){
		    $cmd->setConfiguration('returnStateTime',$config['configuration']['returnStateTime']);
		}
	    }
	    if (isset($config['template'])){
		if (isset($config['template']['dashboard'])){
		    $cmd->setTemplate('dashboard',$config['template']['dashboard']);
		}
		if (isset($config['template']['mobile'])){
		    $cmd->setTemplate('mobile',$config['template']['mobile']);
		}
	    }
	    $cmd->save();
	}
	foreach ($configs as $logicalId => $config) {
	    log::add("devolo_cpl","info",sprintf(__("  cmd: %s (seconde passe)",__FILE__),$logicalId));
	    if (isset($config['value'])){
		$cmdLiee = $this->getCmd(null,$config['value']);
		if (! is_object($cmdLiee)){
		    log::add("devolo_cpl","errror",sprintf(__("La commande '%s' est introuvable",__FILE__),$config['value']));
		    continue;
		}
		$cmd = $this->getCmd(null,$logicalId);
		if (! is_object($cmd)){
		    log::add("devolo_cpl","errror",sprintf(__("La commande '%s' est introuvable",__FILE__),$logicalId));
		    continue;
		}
		$cmd->setValue($cmdLiee->getId());
		$cmd->save();
	    }
	}
    }

    // Fonction exécutée automatiquement avant la mise à jour de l'équipement
    public function preUpdate() {
	if ($this->getConfiguration('mac') != ''){
	    $mac = trim(strtoupper($this->getConfiguration('mac')));
	    if (! preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/',$mac)){
		if (preg_match('/^[0-9A-F]{12}$/',$mac)){
		    $mac = rtrim(chunk_split($mac,2,":"),":");
		    log::add("devolo_cpl","debug","==" . $mac . "==");
		}
	    }
	    $this->setConfiguration('mac',$mac);
	} 
    }

    // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
    public function preSave() {
	if ($this->getConfiguration('model') == ""){
	    $this->setConfiguration('model','autre');
	}
    }

    // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
    public function postSave() {
	$this->createCmds();
    }

    public function getModel () {
	return model::byCode($this->getConfiguration('model'));
    }

    /*
    * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
    */
    public function decrypt() {
      $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
    }
    public function encrypt() {
      $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
    }

    public function getImage() {
	return $this->getModel()->getImage();
    }

    public function isManageable() {
	return model::byCode($this->getConfiguration('model'))->isManageable();
    }

    /*     * **********************Getteur Setteur*************************** */

}

class devolo_cplCmd extends cmd {
    /*     * *************************Attributs****************************** */

    /*
    public static $_widgetPossibility = array();
    */

    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
    * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
    public function dontRemoveCmd() {
      return true;
    }
    */

    private function sendActionToDaemon ($action, $param = Null, $refresh=true) {
	$params = [
	    'action' => "execCmd",
	    'cmd' => $action,
	    'param' => $param,
	    'refresh' => $refresh
	];
	$this->getEqLogic()->PrepareToDaemon($params);
    }

    // Exécution d'une commande
    public function execute($_options = array()) {
	if ($this->getLogicalId() == 'refresh') {
	    $this->getEqLogic()->getEqState();
	}
	if ($this->getLogicalId() == 'leds_on') {
	    $this->sendActionToDaemon('leds', 1);
	}
	if ($this->getLogicalId() == 'leds_off') {
	    $this->sendActionToDaemon('leds', 0);
	}
	if ($this->getLogicalId() == 'locate_on') {
	    $this->sendActionToDaemon('locate', 1);
	}
	if ($this->getLogicalId() == 'locate_off') {
	    $this->sendActionToDaemon('locate', 0);
	}
    }

    /*     * **********************Getteur Setteur*************************** */

}
