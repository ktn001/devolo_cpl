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
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class devolo_cpl extends eqLogic {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */

    /*
    * Fonction exécutée automatiquement toutes les minutes par Jeedom
    public static function cron() {}
    */

    /*
    * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
    public static function cron5() {}
    */

    /*
    * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
    public static function cron10() {}
    */

    /*
    * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
    public static function cron15() {}
    */

    /*
    * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
    public static function cron30() {}
    */

    /*
    * Fonction exécutée automatiquement toutes les heures par Jeedom
    public static function cronHourly() {}
    */

    /*
    * Fonction exécutée automatiquement tous les jours par Jeedom
    public static function cronDaily() {}
    */

   // public static function dependancy_install() {
   //     log::remove(__CLASS__ . '_update');
   //     return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependency', 'log' => log::getPathToLog(__CLASS__ . '_update'));
   // }

   // public static function dependancy_info() {
   //     $return = array();
   //     $return['log'] = log::getPathToLog(__CLASS__ . '_update');
   //     $return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependency';
   //     if (file_exists(jeedom::getTmpFolder(__CLASS__) . '/dependency')) {
   //         $return['state'] = 'in_progress';
   //     } else {
   //         if (exec(system::getCmdSudo() . 'pip3 list | grep -Ewc "devolo-plc-api"') < 1) {
   //             $return['state'] = 'nok';
   //         } else {
   //             $return['state'] = 'ok';
   //         }
   //     }
   //     return $return;
   // }

    public static function getModelInfos($model = Null) {
	$infos =  json_decode(file_get_contents(__DIR__ . "/../config/models.json"),true);
	$country = config::byKey('country','devolo_cpl','ch');
	$imgDir = __DIR__ . '/../../desktop/img/';
	foreach ($infos as $m => $i ) {
	    if (!array_key_exists('image',$i)){
		continue;
	    }
	    $img = $i['image'];
	    if (file_exists($imgDir . $img)){
		continue;
	    }
	    $img = $country . '-' . $img;
	    if (file_exists($imgDir . $img)){
		$infos[$m]['image'] = $img;
	    }
	}
	log::add("devolo_cpl","debug",print_r($infos,true));
	if ($model == Null) {
	    return $infos;
	}
	if (array_key_exists($model,$infos)){
	    return $infos[$model];
	}
	return Null;
    }

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
	} else {
	    log::add("devolo_cpl","debug",sprintf(__("Créaction de '%s'",__FILE__),$equipement['name']));
	    $devolo = new devolo_cpl();
	    $devolo->setName($equipement['name']);
	    $devolo->setEqType_name(__CLASS__);
	    $devolo->setLogicalId($equipement['serial']);
	    $devolo->setConfiguration("sync_model",$equipement['model']);
	    if (self::getModelInfos($equipement['model']) == Null) {
		$devolo->setConfiguration("model","autre");
	    } else {
		$devolo->setConfiguration("model",$equipement['model']);
	    }
	    $devolo->save();
	}
    }

    public static function syncDevolo() {
	$path = realpath(dirname(__FILE__) . '/../../resources/bin');
	$cmd = $path . '/devolo_cpl.py';
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

    /*     * *********************Méthodes d'instance************************* */

    // Fonction exécutée automatiquement avant la création de l'équipement
    public function preInsert() {
    }

    // Fonction exécutée automatiquement après la création de l'équipement
    public function postInsert() {
    }

    // Fonction exécutée automatiquement avant la mise à jour de l'équipement
    public function preUpdate() {
    }

    // Fonction exécutée automatiquement après la mise à jour de l'équipement
    public function postUpdate() {
    }

    // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
    public function preSave() {
    }

    // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
    public function postSave() {
    }

    // Fonction exécutée automatiquement avant la suppression de l'équipement
    public function preRemove() {
    }

    // Fonction exécutée automatiquement après la suppression de l'équipement
    public function postRemove() {
    }

    /*
    * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
    * Exemple avec le champ "Mot de passe" (password)
    */
    public function decrypt() {
      $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
    }
    public function encrypt() {
      $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
    }

    public function getImage() {
	$model = $this->getConfiguration('model');
	if ($model != "") {
	    $infos = $this->getModelInfos($model);
	    if (is_array($infos) and array_key_exists('image',$infos)) {
		return '/plugins/devolo_cpl/desktop/img/' . $infos['image'];
	    }
	}
	return parent::getImage();
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

    // Exécution d'une commande
    public function execute($_options = array()) {
    }

    /*     * **********************Getteur Setteur*************************** */

}
