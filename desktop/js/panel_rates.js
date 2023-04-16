/*
 * Compteur pour les identifients uniques
 */
rates_id = 0
function get_next_rates_id () {
	rates_id++
	return rates_id
}

/*
 * Création d'une carte graphique
 */
function add_rates_card() {
	let card  = '<div class=card>'
	card +=   '<div class="card-header">'
	card +=     '<div class="col-sm-3">'
	card +=       '<i class="fas fa-exchange-alt"></i>'
	card +=       '<h3>{{Débits}}</h3>'
	card +=     '</div>'
	card +=     '<div class="col-sm-5">'
	card +=       '<span class="label">{{de}}:</span>'
	card +=         '<select class="from">' 
	for (name in eqList) {
		card += '<option value="' + eqList[name]['mac'] + '" data-network="' + eqList[name]['network']  +'">'
		card += name
		card += '</option>'
	}
	card +=         '</select>' 
	card +=       '<span class="label">{{vers}}:</span>'
	card +=         '<select class="to">' 
	for (name in eqList) {
		card += '<option value="' + eqList[name]['mac'] + '" data-network="' + eqList[name]['network']  +'">'
		card += name
		card += '</option>'
	}
	card +=         '</select>' 
	card +=         '<span class="button ok cursor">{{OK}}</span>'
	card +=       '</div>'
	card +=       '<i class="fas fa-times-circle bt-close_card"></i>'
	card +=     '</div>'
	card +=     '<div id="ratesGraph_' + get_next_rates_id() + '" class="card-content">'
	card +=   '</div>'
	card += '</div>'
	$('#devolo_cpl_rates').append(card)
	$('#devolo_cpl_rates div.card').last().find('select.from').trigger('change')
}

/*
 * Ajout d'une carte graphique
 */
$('#bt_add-rates-graph').on('click', function() {
	add_rates_card()
})

/*
 * Changement de source
 */
$('#devolo_cpl_rates').on('change','div.card select.from', function() {
	let mac = $(this).value()
	let network = $(this).find(':selected').data('network')
	let selectTo = $(this).closest('div.card').find('select.to')
	let macTo = selectTo.value()
	let changeNeeded = 0
	$(selectTo).find('option').each(function(){
		if (($(this).data('network') != network) || ($(this).value() == mac)) {
			$(this).hide()
			$(this).prop('disabled', true)
			if (macTo == $(this).value()){
				changeNeeded = 1
			}
		} else {
			$(this).show()
			$(this).prop('disabled', false)
		}
	})
	if (changeNeeded == 1) {
		$(selectTo).find('option:enabled').first().prop('selected', true)
	}
})

/*
 * Chargement des données sur click du bouton "OK"
 */
$('#devolo_cpl_rates').on('click','div.card .button.ok', function() {
	let graphId = $(this).closest('.card').find('.card-content').attr('id')
	let macFrom = $(this).closest('.card').find('select.from').value()
	let eqFrom =  $(this).closest('.card').find('select.from option:selected').text()
	let macTo = $(this).closest('.card').find('select.to').value()
	let eqTo =  $(this).closest('.card').find('select.to option:selected').text()

	$.ajax({
		type: 'POST',
		url: '/plugins/devolo_cpl/core/ajax/devolo_cpl.ajax.php',
		data: {
			action: 'ratesHistorique',
			macFrom: macFrom,
			macTo: macTo
		},
		dataType: 'json',
		error: function(request, status, error) {
			handleAjaxError(request, status, error)
		},
		success: function(data){
			if (data.state != 'ok'){
				$.fn.showAlert({message:data.result, level: "danger"})
				return
			}
			let tx_rates = []
			let rx_rates = []
			for (d of data.result) {
				tx_rates.push([parseInt(d['time'])*1000,parseInt(d['tx_rate'])])
				rx_rates.push([parseInt(d['time'])*1000,parseInt(d['rx_rate'])])
			}
			chart_config = {...chart_defaults, ...{
				chart: {
					zoomType: 'x',
					spacingBottom: 5,
					spacingTop: 5,
					spacingRight: 5,
					spacingLeft: 5,
					style: {
						fontFamily: 'Roboto',
					},
				},
				series:[{
					name: "{{Emission}}",
					data: tx_rates,
				},{
					name: "{{Réception}}",
					data: rx_rates,
				}],
				title:{
					text: eqFrom + " -> " + eqTo,
				},
			}}
			let chart = new Highcharts.StockChart(graphId,chart_config)
		}
	})
})

/*
 * Création d'une première carte graphique après chargement
 */
add_rates_card()
$('#devolo_cpl_rates').find('div.card .button.ok').trigger('click')
