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
      <label class="col-sm-2 control-label">{{Pays}}
        <sup><i class="fas fa-question-circle" title="{{Permet d'afficher les images des équipements avec le bon type de prise}}"></i></sup>
      </label>
      <select class="configKey col-sm-4 form-control" data-l1key="country">
	<option value="fr" selected>{{Belgique}}</option>
	<option value="fr" selected>{{France}}</option>
	<option value="ch" selected>{{Suisse}}</option>
      </select>
    </div>
  </fieldset>
</form>
