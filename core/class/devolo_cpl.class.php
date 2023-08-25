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
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
     */
    public static function cron() {
	$equipements = eqLogic::byType(__CLASS__,True);
	foreach($equipements as $equipement) {
	    if (! $equipement->isManageable()){
		continue;
	    }
	    $equipement->getWifiConnectedDevices();
	    $equipement->getEqState();
	}
	devolo_connection::setIps();
    }

    /*
     * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
     */
    public static function cron5() {
	devolo_cpl::getRates();
    }

    /*
     * Fonction exécutée automatiquement chaque jour par Jeedom
     */
    public static function cronDaily() {
	self::purgeDB();
    }

    public static function postConfig_active($value) {
	log::add("toto","info","##################### " . $value);
	if ($value == 1) {
	    devolo_cpl::setListeners();
	}
    }

    /*
     * Changement de version pour devolo_plc_api
     *
     * Appelée par le core sur modification de la config "devolo_plc_api::version"
     */
    public static function preConfig_devolo_plc_api_version($version){
	log::add("toto","info","##################### " . $version);
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
     * Mise à jour des listener
     */
    public static function setListeners() {
	log::add("devolo_cpl","info","setListeners");
	foreach (eqLogic::byType('devolo_cpl') as $eqLogic) {
	    $eqLogic->setListener();
	}
    }

    /*
     * Fonction appelée par le listener en cas de changement de valuer pour la cmd 'online'
     */
    public static function alertNoOnline($_option) {
	log::add ("devolo_cpl","info","alertNoOnline called: " . print_r($_option,true));
	if ($_option['value'] == 1) {
	    return;
	}
	$eqLogic = devolo_cpl::byId($_option['id']);
	$eqLogic->execAlertOffline();
    }

    /*
     * Purge de données historiques
     */
    public static function purgeDB() {
	log::add("devolo_cpl","info","purge DB");
	$retention = config::byKey('data-retention','devolo_cpl');

	$sql  = "DELETE FROM devolo_cpl_rates";
	$sql .= " WHERE time < DATE_SUB(now(), INTERVAL " . $retention . ")";
	log::add("devolo_cpl","debug",$sql);
	$response = DB::Prepare($sql,array(), DB::FETCH_TYPE_ALL);
	log::add("devolo_cpl","debug",$response);

	$sql  = "DELETE FROM devolo_connection";
	$sql .= " WHERE disconnect_time < DATE_SUB(now(), INTERVAL " . $retention . ")";
	log::add("devolo_cpl","debug",$sql);
	$response = DB::Prepare($sql,array(), DB::FETCH_TYPE_ALL);
	log::add("devolo_cpl","debug",$response);

	$sql  = "DELETE FROM devolo_macinfo";
	$sql .= " wHERE name is NULL";
	$sql .= "   AND mac not in (";
	$sql .= "       SELECT mac FROM devolo_connection)";
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
		    ],
		    'wifi' => [
			'template' => 'tmplicon',
			'replace' => [
			    '#_icon_on_#' => '<i class="icon_green icon fas fa-wifi"></i>',
			    '#_icon_off_#' => '<i class="icon_blue icon fas fa-times"></i>'
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
	if (strpos($_macAddress,":") === false) {
		$_macAddress = rtrim(chunk_split($_macAddress,2,":"),":");
	}
	$eqLogics = devolo_cpl::byTypeAndSearchConfiguration(__CLASS__,['mac' => $_macAddress]);
	if (count($eqLogics) > 1) {
	    log::add("devolo_cpl","warning",sprintf(__("Il y a plusieurs équipements avec l'adresse mac %s :",__FILE__),$_macAddress));
	    foreach ($eqLogics as $eqLogic) {
		log::add("devolo_cpl","warning"," - " . $eqLogic->getId() . "  " . $eqLogic->getName());
	    }
	}
	return $eqLogics[0];
    }

    /*
     * Cherche les equipements ayant une feature
     */
    public static function byFeature($feature, $onlyEnable=false){
	$result = [];
	foreach (self::byType(__CLASS__, $onlyEnable) as $eqLogic){
	    if ($eqLogic->haveFeature($feature)){
		$result[] = $eqLogic;
	    }
	}
	return $result;
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
	    log::add("devolo_cpl","debug",sprintf(__("Création de '%s'",__FILE__),$equipement['name']));
	    $devolo = new devolo_cpl();
	    $devolo->setName($equipement['name']);
	    $devolo->setEqType_name(__CLASS__);
	    $devolo->setLogicalId($equipement['serial']);
	    $devolo->setConfiguration('mac',$equipement['mac']);
	    $devolo->setConfiguration("ip",$equipement['ip']);
	    $devolo->setConfiguration("sync_model",$equipement['model']);
	    if (devolo_model::byCode($equipement['model'] == Null)) {
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
	$cmd = "python3 " . $path . '/devolo_synchronize.py';
	$loglevel = log::convertLogLevel(log::getLogLevel(__CLASS__));
	if ($loglevel == 'debug' and ! config::bykey('debuglimite','devolo_cpl')) {
	    $loglevel = "fulldebug";
	}
	$cmd .= ' --loglevel ' . $loglevel;
	$cmd .= ' 2>>' . log::getPathToLog('devolo_synchronize');
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

    public static function getEqListToGraph() {
	$eqLogics = devolo_cpl::byType('devolo_cpl',true);
	$eqList = [];
	foreach ($eqLogics as $eqLogic) {
	    if (config::byKey('noObject','devolo_cpl') == 1){
		$humanName = $eqLogic->getName();
	    } else {
		$humanName = $eqLogic->getHumanName();
	    }
	    $eqList[$humanName] = [];
	    $eqList[$humanName]['network'] = $eqLogic->getConfiguration('network','cpl');
	    $eqList[$humanName]['mac'] = $eqLogic->getConfiguration('mac');
	    $eqList[$humanName]['serial'] = $eqLogic->getLogicalId();
	    if (in_array('wifi', $eqLogic->getFeatures())) {
		$eqList[$humanName]['wifi'] = 1;
	    } else {
		$eqList[$humanName]['wifi'] = 0;
	    }
	}
	return $eqList;
    }

    public static function getCplNetworksToModal() {
	$eqLogics = devolo_cpl::byType('devolo_cpl',true);
	$networks = [];
	foreach ($eqLogics as $eqLogic) {
	    $network = $eqLogic->getConfiguration('network','cpl');
	    if (!isset ($networks[$network])){
		$networks[$network] = [];
	    }
	    if (config::byKey('noObject','devolo_cpl') == 1){
		$humanName = $eqLogic->getName();
	    } else {
		$humanName = $eqLogic->getHumanName();
	    }
	    $networks[$network][$humanName] = [
		'id' => $eqLogic->getId(),
		'macAddress' => $eqLogic->getConfiguration('mac'),
		'img' => $eqLogic->getImage(),
	    ];
	    $model = $eqLogic->getModel();
	    $networks[$network][$humanName]['cpl_speed'] = $model->getCplSpeed();
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

    public static function getRatesHistorique($macFrom, $macTo) {
	$values = [
	    'macFrom' => $macFrom,
	    'macTo' => $macTo,
	];
	$sql  = 'SELECT UNIX_TIMESTAMP(time) as time, tx_rate, rx_rate';
	$sql .= '  FROM devolo_cpl_rates'; 
	$sql .= ' WHERE mac_address_from=:macFrom and mac_address_to=:macTo';
	$sql .= ' ORDER BY time';
	return DB::Prepare($sql, $values, DB::FETCH_TYPE_ALL);
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

    /*
     * Complète un message avec les infos standards avant de l'envoyer au daemon
     */
    public function PrepareToDaemon($params) {
	$params['serial'] = $this->getLogicalId();
	$params['ip'] = $this->getConfiguration('ip');
	$params['password'] = $this->getConfiguration('password');
	devolo_cpl::sendToDaemon($params);
    }

    /*
     * Demande au daemon de remonter l'état de l'équipement
     */
    public function getEqState () {
	$this::PrepareToDaemon(['action' => 'getState']);
    }

    /*
     * Demande au daemon de remonter la liste des appareils connectés au WiFi
     */
    public function getWifiConnectedDevices() {
	if (in_array('wifi', $this->getFeatures())) {
	    $this::PrepareToDaemon(['action' => 'getWifiConnectedDevices']);
	}
    }

    /*
     * Function pour trier les commandes
     */
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

    /*
     * Function pour la création des CMD
     */
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
	    if (array_key_exists('feature',$config)){
		if (! $this->haveFeature($config['feature'])){
		    continue;
		}
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

    /*
     * Fonctions pour la gestion du listener
     */
    private function getListener() {
	return listener::byClassAndFunction(__CLASS__, 'alertNoOnline', array('id' => $this->getId()));
    }

    private function removeListener() {
        $listener = $this->getListener();
        if (is_object($listener)) {
            $listener->remove();
        }
    }

    private function setListener() {
	if ($this->getIsEnable() == 0) {
            $this->removeListener();
            return;
        }

	if ($this->getConfiguration('alert_offline') == 0) {
            $this->removeListener();
            return;
        }

	$cmd = $this->getCmd('info','online');
	if (!is_object($cmd)){
            $this->removeListener();
            return;
	}

	$listener = $this->getListener();
        if (!is_object($listener)) {
            $listener = new listener();
            $listener->setClass(__CLASS__);
            $listener->setFunction('alertNoOnline');
            $listener->setOption(array('id' => $this->getId()));
        }
	$listener->emptyEvent();
	$listener->addEvent($cmd->getId());
	$listener->save();
    }

    /*
     * function appelée par 'alertNoOnline'
     */
    private function execAlertOffline() {
	log::add("devolo_cpl","info","11111111111111111111111111111");
	if ($this->getConfiguration('alert_offline') != 1) {
	    return;
	}
	log::add("devolo_cpl","info","222222222222222222222222222222");
	$cmd = $this->getCmd('info','online');
	if (!is_object($cmd)){
            return;
	}
	log::add("devolo_cpl","info","33333333333333333333333333333");
	if ($cmd->execCmd() == 1){
            return;
	}
	log::add("devolo_cpl","info","444444444444444444444444444444");
	log::add("devolo_cpl","error",sprintf(__("%s est inatteignable",__FILE__),$this->getHumanName()));
    }

    /*
     * Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
     */
    public function preSave() {
	if ($this->getConfiguration('model') == ""){
	    $this->setConfiguration('model','autre');
	}
    }

    /*
     * Fonction exécutée automatiquement avant la création de l'équipement
     */
    public function preInsert() {
	$this->_wasManageable = 0;
    }

    /*
     * Fonction exécutée automatiquement avant la mise à jour de l'équipement
     */
    public function preUpdate() {
	if ($this->getConfiguration('mac') != ''){
	    $mac = trim(strtoupper($this->getConfiguration('mac')));
	    if (! preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/',$mac)){
		if (preg_match('/^[0-9A-F]{12}$/',$mac)){
		    $mac = rtrim(chunk_split($mac,2,":"),":");
		}
	    }
	    $this->setConfiguration('mac',$mac);
	}
	$actualEqLogic = devolo_cpl::byId($this->getId());
	if ($actualEqLogic->isManageable()) {
	    $this->_wasManageable = 1;
	} else {
	    $this->_wasManageable = 0;
	}
    }

    /*
     * Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
     */
    public function postSave() {
	if ($this->isManageable() and ($this->_wasManageable == 0)) {
	    $this->createCmds();
	}
    }

    /* 
     * Fonction executée automatiquement après l'update de l'équipement
     */
    public function postUpdate() {
	$this->setListener();
    }

    public function getModel() {
	return devolo_model::byCode($this->getConfiguration('model'));
    }

    public function haveFeature($feature){
	return in_array($feature, $this->getModel()->getFeatures());
    }

    /*
     * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
     *
     * Ces méthodes sont appelées automatiquement par la class DB.
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

    public function getFeatures() {
	return $this->getModel()->getFeatures();
    }

    public function isManageable() {
	return devolo_model::byCode($this->getConfiguration('model'))->isManageable();
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
	switch ($this->getLogicalId()) {
	    case 'refresh':
		$this->getEqLogic()->getEqState();
		break;
	    case  'leds_on':
		$this->sendActionToDaemon('leds', 1);
		break;
	    case 'leds_off':
		$this->sendActionToDaemon('leds', 0);
		break;
	    case 'locate_on':
		$this->sendActionToDaemon('locate', 1);
		break;
	    case 'locate_off':
		$this->sendActionToDaemon('locate', 0);
		break;
	    case 'guest_duration':
		log::add("devolo_cpl","info",print_r($_options,true));
		break;
	    case 'guest_on':
		$durationCmd = $this->getEqLogic()->getCmd('action','guest_duration');
		if (is_object($durationCmd)) {
		    $duration = $durationCmd->getLastValue();
		} else {
		    $duration = 0;
		}
		$this->sendActionToDaemon('guest_on', $duration);
		$guestCmd = $this->getEqLogic()->getCmd('info','guest');
		if (is_object($guestCmd)){
			$cmd = __DIR__ . "/../php/waitGuestAndRefresh.php -i " . $guestCmd->getId();
			log::add("devolo_cpl","debug",sprintf(__("Lancement de %s",__FILE),$cmd ));
			system::php($cmd . ' >> ' . log::getPathToLog('devolo_cpl_script') . ' 2>&1 &');
		}
		/*
		foreach (devolo_cpl::byFeature('wifi', true) as $eqLogic){
		    if ($eqLogic->getId() != $this->getEqLogic_id()){
		    	$eqLogic->getEqLogic()->getEqState();
		    }
		}
		*/
		break;
	    case 'guest_off':
		$this->sendActionToDaemon('guest_off');
		break;
	}
    }

    /*     * **********************Getteur Setteur*************************** */

}
