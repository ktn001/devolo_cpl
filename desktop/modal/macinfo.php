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

if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}

require_once __DIR__ . '/../../core/php/devolo_cpl.inc.php';
include_file('desktop', 'modal_macinfo', 'css', 'devolo_cpl');
?>

<div class='input-group pull-right' style='display:inline-flex;'>
	<span class='input-group-btn'>
		<a class='btn btn-sm btn-warning roundedLeft macinfoAction' data-action='cancel'>
			<i class='fas fa-times-circle'></i>
			{{Annuler}}
		</a>
		<a class='btn btn-sm btn-success macinfoAction' data-action='save'>
			<i class='fas fa-check-circle'></i>
			{{Sauvegarder}}
		</a>
		<a class='btn btn-sm roundedRight macinfoAction' data-action='close'>
			<i class='fas fa-window-close'></i>
			{{fermer}}
		</a>
	</span>
</div>
<div id='div_devoloMacInfo'>
	<h3 class="center">{{Adresses mac des composants découverts}}</h3>
</div>
<table id="table_macinfo" class="table table-condensed">
	<thead>    
		<tr>
			<th class='hidden-xs'>{{Id}}</th>
			<th>{{Mac}}</th>
			<th>{{Fabriquant}}</th>
			<th>{{Nom}}</th>
			<th><span class="pull-right">{{Action}}</span></th>
		</tr>
	</thead>    
	<tbody>
	</tbody>
</table>

<?php include_file('desktop', 'modal_macinfo', 'js', 'devolo_cpl'); ?>
