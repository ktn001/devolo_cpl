
/*
 * Création d'une carte graphique
 */
function add_rates_card() {
	card  = '<div class=card>'
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
	card +=       '<span class="button ok cursor">{{OK}}</span>'
	card +=     '</div>'
	card +=     '<i class="fas fa-times-circle bt-close_card"></i>'
	card +=   '</div>'
	card +=   '<div class="card-content">'
	card +=     'content'
	card +=   '</div>'
	card += '</div>'
	$('#devolo_cpl_rates').append(card)
	card = $('#devolo_cpl_rates div.card').last()
	card.find('select.from').trigger('change')
}

/*
 * Ajout d'une carte graphique
 */
$('#bt_add-graph').on('click', function() {
	add_rates_card()
})

/*
 * Changement de source
 */
$('#devolo_cpl_rates').on('change','div.card select.from', function() {
	mac = $(this).value()
	network = $(this).find(':selected').data('network')
	selectTo = $(this).closest('div.card').find('select.to')
	macTo = selectTo.value()
	changeNeeded = 0
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
 * Création d'une première carte graphique après chargement
 */
add_rates_card()
