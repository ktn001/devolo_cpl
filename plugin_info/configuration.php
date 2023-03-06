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
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
include_file('core', 'devolo_cpl', 'class', 'devolo_cpl');
$defaultPort = config::getDefaultConfiguration('devolo_cpl')['devolo_cpl']['daemon::port'];
?>

<form class="form-horizontal">
  <fieldset>
    <div class="form-group col-md-6 col-sm-12">
      <legend><i class="fas fa-wrench"></i> {{Plugin}}</legend>
      <div class="row">
        <label class="col-sm-5 control-label">{{Pays}}
          <sup><i class="fas fa-question-circle" title="{{Permet d'afficher les images des équipements avec le bon type de prise}}"></i></sup>
        </label>
        <select class="configKey col-sm-4 form-control" data-l1key="country">
          <option value="be" selected>{{Belgique}}</option>
          <option value="fr" selected>{{France}}</option>
          <option value="ch" selected>{{Suisse}}</option>
        </select>
      </div>
      <div class="row">
        <label class="col-sm-5 control-label">{{Nom des équipements sans l'objet}}
          <sup><i class="fas fa-question-circle" title="{{Afficher 'nom_appareil' au lieu de '[objet][nom_appareil] dans les graphiques et tableaux'}}"></i></sup>
        </label>
	<input class="configKey" type=checkbox data-l1key="noObject"></input>
      </div>
      <legend><i class="fas fa-database"></i> {{Base de données}}</legend>
      <div class="row">
        <label class="col-sm-5 control-label">{{Rétention}}
          <sup><i class="fas fa-question-circle" title="{{Durée de rétention de l'historique des débits CPL}}"></i></sup>
        </label>
        <select class="configKey col-sm-4 form-control" data-l1key="data-retention">
          <option value="1 DAY" selected>1 {{jour}}</option>
          <option value="3 DAY" selected>3 {{jours}}</option>
          <option value="7 DAY" selected>1 {{semaine}}</option>
          <option value="14 DAY" selected>2 {{semaines}}</option>
          <option value="1 MONTH" selected>1 {{mois}}</option>
          <option value="2 MONTH" selected>2 {{mois}}</option>
        </select>
      </div>
    </div>
    <div class="form-group col-md-6 col-sm-12">
      <legend><i class="fas fa-university"></i> {{Démon}} <sub>({{nécessite un redémarrage du démon}})</sub></legend>
      <div class="row">
        <label class="col-sm-5 control-label">{{Port}}
          <sup><i class="fas fa-question-circle" title="{{Redémarrer le démon en cas de modification}}"></i></sup>
        </label>
        <input class="configKey col-sm-2 form-control" data-l1key="daemon::port" placeholder="<?= $defaultPort ?>"/>
      </div>
      <div class="row">
        <label class="col-sm-5 control-label">{{Version devolo_plc_api}}
          <sup><i class="fas fa-question-circle" title="{{Sauf indication contraire, veuillez utiliser la dernière version}}"></i></sup>
        </label>
        <select class="configKey col-sm-2 form-control" data-l1key="devolo_plc_api::version">
        <?php
          $dir = opendir(__DIR__ . "/../3rdparty/");
          $versions = [];
          while (false !== ($entry = readdir($dir))){
            if (preg_match('/^devolo_plc_api-([\d\.]+)$/', $entry, $match)){
              $versions[] = $match[1];
	    }
	  }
          closedir($dir);
	  sort($versions);
	  foreach ($versions as $version) {
            echo ('<option value="' . $version . '">' . $version . '</option>'); 
          }
        ?>
        </select>
      </div>
    </div>
  </fieldset>
</form>
