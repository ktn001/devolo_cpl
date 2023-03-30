/*
 * Compteur pour les identifients uniques
 */
wifi_id = 0
function get_next_wifi_id () {
	wifi_id++
	return wifi_id
}

/*
 * Création d'une carte graphique
 */
function add_wifi_card() {
	card  = '<div class=card>'
	card +=   '<div class="card-header">'
	card +=     '<div class="col-sm-3">'
	card +=       '<i class="fas fa-wifi"></i>'
	card +=       '<h3>{{connections WiFi}}</h3>'
	card +=     '</div>'
	card +=     '<div class="col-sm-5">'
	card +=       '<span class="label">{{Equipement}}:</span>'
	card +=       '<select class="AP">'
	for (name in eqList) {
		if (eqList[name]['wifi'] == 1) {
			card += '<option value="' + eqList[name]['serial'] + '">'
			card += name
			card += '</option>'
		}
	}
	card +=       '</select>'
	card +=       '<span class="button ok cursor">{{OK}}</span>'
	card +=     '</div>'
	card +=     '<i class="fas fa-times-circle bt-close_card"</i>'
	card +=   '</div>'
	card +=   '<div id="WifiGraph_' + get_next_wifi_id() + '" class="card-content">'
	card +=   '</div>'
	card += '</div>'
	$('#devolo_cpl_wifi').append(card)
}

/*
 * Ajourd'une carte graphique
 */
$('#bt_add-wifi-graph').on('click', function() {
	add_wifi_card()
})

/*
 * Chargement des données sur click du bouron "OK"
 */
$('#devolo_cpl_wifi').on('click','div.card .button.ok', function() {
	graphId = $(this).closest('.card').find('.card-content').attr('id')
	serial = $(this).closest('.card').find('select.AP').value()

	$.ajax({
		type: 'POST',
		url: '/plugins/devolo_cpl/core/ajax/devolo_cpl.ajax.php',
		data: {
			action: 'wifiHistorique',
			serial : serial
		},
		dataType: 'json',
		error: function(request, status, error) {
			handleAjaxError(request, status, error)
		},
		success: function(data){
			if (data.state != 'ok'){
				$.fn.showAlert({message: data.result, level: "danger"})
				return
			}
			categories = []
			networks = []
			for (entry of data.result){
				if (! categories.includes(entry.label)) {
					categories.push(entry.label)
				}
				if (! networks.includes(entry.network)) {
					networks.push(entry.network)
				}
			}
			categories.sort()
			networks.sort()
			categoryNr = {}
			for (i=0; i < categories.length; i++) {
				categoryNr[categories[i]] = i
			}
			datas= []
			for (network of networks) {
				console.log(network)
				datas[network] = []
			}
			console.log(datas)
			for (entry of data.result){
				data = {}
				data.x = parseInt(entry.connect_time)
				data.x2 = parseInt(entry.disconnect_time)
				data.y = categoryNr[entry.label]
				datas[entry.network].push(data)
			}
			chart_config = {...chart_defaults, ...{
				chart: {
					displayErrors: true,
					type: 'xrange',
					zoomType: 'x',
					spacingBottom: 5,
					spacingTop: 5,
					spacingRight: 5,
					spacingLeft: 5,
					style: {
						fontFamily: 'Roboto',
					},
				},
				title: {
					text: 'Highcharts X-range'
				},
				accessibility: {
					point: {
						descriptionFormatter: function(point) {
							var ix = point.index + 1,
								category = point.yCategory,
								from = new Date(point.x),
								to = new Date(point.x2);
							return ix + '. ' + category + ', ' + from.toDateString() +
								' to ' + to.toDateString() + '.';
						}
					}
				},
				xAxis: {
					type: 'datetime'
				},
				yAxis: {
					title: {
						text: '',
					},
					categories: categories,
					reversed: true,
				},
				series: []
			}};
			console.log(chart_config)
			for (network of networks) {
				chart_config.series.push({
					name : network,
					borderColor: 'gray',
					pointWidth: 5,
					data : datas[network],
					dataLabels: {
						enabled: true,
					},
					showInLegend: true,
				})
			}
			console.log(chart_config)
			chart = new Highcharts.chart(graphId,chart_config)

		}
	})
})
add_wifi_card()
