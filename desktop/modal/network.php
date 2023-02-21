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
?>

<style>
  #div_devoloNetwork table {
    margin: auto;
    width: auto;
  }

  #div_devoloNetwork thead th:first-child {
    width: 220px;
  }

  #div_devoloNetwork thead th:nth-child(2) {
    width: 30px;
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

</style>
<div id='div_devoloNetwork'>
    <h3 class="center">{{Débits CPL}}</h3>
    <ul id="tabs_network" class="nav nav-tabs" data-tabs="tabs"></ul>
    <div id='network-tab-content' class='tab-content'></div>
</div>
<script>

function fillRatesTables() {
    $.ajax({
        type: 'POST',
        url: 'plugins/devolo_cpl/core/ajax/devolo_cpl.ajax.php',
        data: {
            action: 'getCplRates',
        },
        dataType: 'json',
        global: false,
        error: function(request, status, error) {
            handleAjaxError(request, status, error)
        },
        success: function(data) {
            if (data.state != 'ok') {
                $.fn.showAlert({message: data.result, level: 'danger'})
                return
            }
            rates = json_decode(data.result)
	    for (rate of rates) {
		selector = '#div_devoloNetwork .devolo_rates_table td'
		selector += '[data-src_mac="' + rate.mac_address_from + '"]'
		selector += '[data-dst_mac="' + rate.mac_address_to + '"]'
		$(selector).html(rate.tx_rate)
	    }
	}
    })
}

function createNetworkTabs() {
    $.ajax({
        type: 'POST',
        url: 'plugins/devolo_cpl/core/ajax/devolo_cpl.ajax.php',
        data: {
            action: 'getCplNetworks',
        },
        dataType: 'json',
        global: false,
        error: function(request, status, error) {
            handleAjaxError(request, status, error)
        },
        success: function(data) {
            if (data.state != 'ok') {
                $.fn.showAlert({message: data.result, level: 'danger'})
                return
            }

            networks = json_decode(data.result)
            active = 'active'
	    if (Object.keys(networks).length == 1) {
		    hidden = ' hidden'
	    } else {
		    hidden = ''
	    }
            for ( network of Object.keys(networks).sort()) {
                    li = '<li class="' + active + hidden + '" >'
                    li += '<a href="#tab_network_' + network + '" data-toggle="tab">'
                    li += network
                    li += '</a>'
                    li += '</li>'
                    $('#div_devoloNetwork #tabs_network').append(li)

                    tab = '<div id="tab_network_' + network + '" class="tab-pane ' + active + '">'
                    tab += '</div>'
                    active = ''
                    $('#div_devoloNetwork #network-tab-content').append(tab)

                    table = '<table id="network_' + network + '" class="table table-condensed table bordered devolo_rates_table">'
                    table += '<thead>'
                    table += '<tr>'
                    table += '<th>{{Nom}}</th>'
                    table += '<th>{{id}}</th>'
                    for (equipement of Object.keys(networks[network])) {
                        table += '<th>' + networks[network][equipement].id + '</th>'
                    }
                    table += '</tr>'
                    table += '</thead>'
                    table += '<tbody>'
                    for (src_equipement of Object.keys(networks[network])) {
                        mac_src = networks[network][src_equipement].macAddress
                        table += '<tr>'
			table += '<td>'
			table += '<img src="' + networks[network][src_equipement].img + '" height="40"> '
			table += src_equipement
			if (networks[network][src_equipement].cpl_speed) {
			    table += '<span class="pull-right">(' + networks[network][src_equipement].cpl_speed + ')</span>'
			}
		        table += '</td>'
                        table += '<td>' + networks[network][src_equipement].id + '</td>'
                        for (dst_equipement of Object.keys(networks[network])) {
                            mac_dst = networks[network][dst_equipement].macAddress
                            if (mac_src == mac_dst) {
                                loopback = 'class="loopback"'
                            } else {
                                loopback = ''
                            }
                            table += '<td ' + loopback + ' data-src_mac="' + mac_src + '" data-dst_mac="' + mac_dst + '"></td>'
                        }
                        table += '</tr>'
                    }
                    table += '</tbody>'
                    table += '</table>'
                    $('#div_devoloNetwork #tab_network_' + network).append(table)
		    legend = "<div>Lignes: source<br>Colonnes: destination</div>"
                    $('#div_devoloNetwork #tab_network_' + network).append(legend)
            }
	    fillRatesTables()
        }
    })
}

createNetworkTabs()

</script>
