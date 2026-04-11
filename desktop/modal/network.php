<?php
// vim: tabstop=4 autoindent

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
?>

<style>
	#div_devoloNetwork table {
		margin: auto;
		width: auto;
	}

	#div_devoloNetwork thead th:first-child {
		width: 300px;
	}

	#div_devoloNetwork thead th:nth-child(2) {
		width: 40px;
	}

	#div_devoloNetwork thead th:not(:first-child):not(:nth-child(2)) {
		width: 60px;
		text-align: center;
	}

	#div_devoloNetwork .loopback {
		background-color : grey !important;
		border-radius: 7px;
	}

	#div_devoloNetwork [data-src_mac] {
		text-align: center;
	}

	#div_devoloNetwork td span.pull-right {
		padding-top : 14px;
	}

	#div_devoloNetwork .legend{
		margin: auto;
		width: 200px;
	}

	#div_devoloNetwork .eqLogicId{
		font-style : italic;
		vertical-align: middle;
	}

</style>
<div id='div_devoloNetwork'>
	<h3 class="center">{{Débits CPL}}</h3>
	<ul id="tabs_network" class="nav nav-tabs" data-tabs="tabs"></ul>
	<div id='network-tab-content' class='tab-content'></div>
	<div class="legend">{{Lignes: source}}<br>{{Colonnes: destination}}</div>
	<div id="dateMesure" class="pull-right"></div>
</div>
<script>

"use strict"
function fillRatesTables() {
	domUtils.ajax({
		type: 'POST',
		async: false,
		global: false,
		url: 'plugins/devolo_cpl/core/ajax/devolo_cpl.ajax.php',
		data: {
			action: 'getCplRates',
		},
		dataType: 'json',
		success: function(data) {
			if (data.state != 'ok') {
				jeedomUtils.showAlert({message: data.result, level: 'danger'})
				return
			}
			let rates = json_decode(data.result)
			if (rates.length > 0) {
				document.getElementById('div_devoloNetwork').querySelector('#dateMesure').textContent = rates[0].time
			}
			for (let rate of rates) {
				let cel = document.getElementById('div_devoloNetwork').querySelector('[data-src_mac="' + rate.mac_address_from + '"][data-dst_mac="' + rate.mac_address_to + '"]')
				if (is_object(cel)) {
					cel.textContent = rate.tx_rate
				}
			}
		}
	})
}

function createNetworkTabs() {
	domUtils.ajax({
		type: 'POST',
		async: false,
		global: false,
		url: 'plugins/devolo_cpl/core/ajax/devolo_cpl.ajax.php',
		data: {
			action: 'getCplNetworks',
		},
		dataType: 'json',
		success: function(data) {
			if (data.state != 'ok') {
				jeedomUtils.showAlert({message: data.result, level: 'danger'})
				return
			}
			let networks = json_decode(data.result)
			let active = true
			let hidden = false
			if (Object.keys(networks).length == 1) {
				hidden = true
			}
			for ( let network of Object.keys(networks).sort()) {
				let li = '<a href="#tab_network_' + network + '" data-toggle="tab">'
				li += network
				li += '</a>'
				let newTabLabel = document.createElement('li')
				newTabLabel.innerHTML = li
				if (active) {
					newTabLabel.addClass("active")
				}
				if (hidden) {
					newTabLabel.addClass("hidden")
				}
				document.getElementById('div_devoloNetwork').querySelector('#tabs_network').appendChild(newTabLabel)

				let tab = document.createElement('div')
				tab.id = 'tab_network_' + network
				tab.addClass('tab-pane')
				if (active) {
					tab.addClass('active')
					active = false
				}
				document.getElementById('div_devoloNetwork').querySelector('#network-tab-content').appendChild(tab)

				let table = '<table>'
				table += '<thead>'
				table += '<tr>'
				table += '<th>{{Nom}}</th>'
				table += '<th class="eqLogicId">{{id}}</th>'
				for (let equipement of Object.keys(networks[network])) {
					table += '<th class="eqLogicId">' + networks[network][equipement].id + '</th>'
				}
				table += '</tr>'
				table += '</thead>'
				table += '<tbody>'
				for (let src_equipement of Object.keys(networks[network])) {
					let mac_src = networks[network][src_equipement].macAddress
					table += '<tr>'
					table += '<td>'
					table += '<img src="' + networks[network][src_equipement].img + '" height="40"> '
					table += src_equipement
					if (networks[network][src_equipement].cpl_speed) {
						table += '<span class="pull-right">(' + networks[network][src_equipement].cpl_speed + ')</span>'
					}
					table += '</td>'
					table += '<th class="eqLogicId">' + networks[network][src_equipement].id + '</th>'
					for (let dst_equipement of Object.keys(networks[network])) {
						let mac_dst = networks[network][dst_equipement].macAddress
						let loopback = ''
						if (mac_src == mac_dst) {
							loopback = 'class="loopback"'
						}
						table += '<td ' + loopback + ' data-src_mac="' + mac_src + '" data-dst_mac="' + mac_dst + '"></td>'
					}
					table += '</tr>'
				}
				table += '</tbody>'
				table += '</table>'
				let newTable = document.createElement('table')
				newTable.innerHTML = table
				newTable.addClass('table')
				newTable.addClass('table-condensed')
				newTable.addClass('devolo_rates_table')
				newTable.id = 'network_' + network
				document.getElementById('div_devoloNetwork').querySelector('#tab_network_' + network).appendChild(newTable)
			}
			fillRatesTables()
		}
	})
}

createNetworkTabs()

</script>
