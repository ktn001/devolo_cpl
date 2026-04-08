"use strict"

if (typeof devolo_cplPanel.rates === 'undefined') {
	devolo_cplPanel.rates = {

		rateId: 0,

		init: function() {
			document.getElementById('devolo_cpl_rates').addEventListener('click', function(event) {
				let _target=null

				if (_target = event.target.closest('#bt_addRatesCard')) {
					devolo_cplPanel.rates.addRatesCard();
					return
				}

				if (_target = event.target.closest("div.card .button.ok")) {
					devolo_cplPanel.rates.loadGraph(_target.closest('.card'))
					return
				}
			})

			document.getElementById('devolo_cpl_rates').addEventListener('change', function(event) {
				let _target=null
				if (_target = event.target.closest('div.card select.from')) {
					devolo_cplPanel.rates.sourceChanged(_target.closest('.card'))
					return
				}
			})
		},

		nextId: function() {
			devolo_cplPanel.rates.rateId++
			return devolo_cplPanel.rates.rateId
		},

		addRatesCard: function() {
			let card = "<div>"
			card += '<div class="card-header">';
			card += '<div class="col-sm-3">';
			card += '<i class="fas fa-exchange-alt"></i>';
			card += "<h3>{{Débits}}</h3>";
			card += "</div>";
			card += '<div class="col-sm-5">';
			card += '<span class="label">{{de}}:</span>';
			card += '<select class="from">';
			for (let name in eqList) {
				card +=
					'<option value="' +
					eqList[name]["mac"] +
					'" data-network="' +
					eqList[name]["network"] +
					'">';
				card += name;
				card += "</option>";
			}
			card += "</select>";
			card += '<span class="label">{{vers}}:</span>';
			card += '<select class="to">';
			for (let name in eqList) {
				card +=
					'<option value="' +
					eqList[name]["mac"] +
					'" data-network="' +
					eqList[name]["network"] +
					'">';
				card += name;
				card += "</option>";
			}
			card += "</select>";
			card += '<span class="button ok cursor">{{OK}}</span>';
			card += "</div>";
			card += '<i class="fas fa-times-circle bt-close_card"></i>';
			card += "</div>";
			card +=
				'<div id="ratesGraph_' + devolo_cplPanel.rates.nextId() + '" class="card-content">';
			card += "</div>";
			card += "</div>"
			let newCard = document.createElement("div")
			newCard.innerHTML = card
			newCard.addClass("card")
			document.getElementById('devolo_cpl_rates').appendChild(newCard)
			devolo_cplPanel.rates.sourceChanged(newCard)
		},

		sourceChanged: function(card) {
			let selectFrom = card.querySelector("select.from")
			let macFrom = selectFrom.value
			let network = selectFrom.options[selectFrom.selectedIndex].dataset.network
			let selectTo = card.querySelector("select.to")
			let macTo = selectTo.value
			for (let i = 0; i < selectTo.options.length; i++){
				let option = selectTo.options[i]
				if (network != option.dataset.network) {
					option.unseen()
					option.disabled = true
					continue
				}
				if (macFrom == option.value) {
					option.unseen()
					option.disabled = true
				} else {
					option.seen()
					option.disabled = false
				}
			}
			if (selectTo.options[selectTo.selectedIndex].disabled) {
				for (let i = 0; i < selectTo.options.length; i++){
					if (selectTo.options[i].disabled) {
						continue
					}
					selectTo.selectedIndex = i
					break
				}
			}
		},

		loadGraph: function(card) {
			let graphId = card.querySelector('.card-content').id

			let selectFrom = card.querySelector('select.from')
			let macFrom = selectFrom.value
			let eqFrom = selectFrom.options[selectFrom.selectedIndex].text

			let selectTo = card.querySelector('select.to')
			let macTo = selectTo.value
			let eqTo = selectTo.options[selectTo.selectedIndex].text

			domUtils.ajax({
				type: "POST",
				async: false,
				global: false,
				url: devolo_cplPanel.ajaxUrl,
				data: {
					action: "ratesHistorique",
					macFrom: macFrom,
					macTo: macTo,
				},
				dataTyp: "json",
				success: function(data) {
					if (data.state != "ok") {
						jeedomUtils.showAlert({
							message: data.result,
							level: "danger",
						})
						return
					}
					let tx_rates = []
					let rx_rates = []
					for (let d of data.result) {
						tx_rates.push([parseInt(d["time"]) * 1000, parseInt(d["tx_rate"])])
						rx_rates.push([parseInt(d["time"]) * 1000, parseInt(d["rx_rate"])])
					}
					let chart_config = {
						...devolo_cplPanel.chart_defaults,
						...{
							chart: {
								zoomType: "x",
								spacingBottom: 5,
								spacingTop: 5,
								spacingRight: 5,
								spacingLeft: 5,
								style: {
									fontFamily: "Roboto",
								},
							},
							series: [
								{
									name: "{{Emission}}",
									data: tx_rates,
								},
								{
									name: "{{Réception}}",
									data: rx_rates,
								},
							],
							title: {
								text: eqFrom + " -> " + eqTo,
							},
						},
					};
					let chart = new Highcharts.StockChart(graphId, chart_config)
				},
			})
		},

	}
}
devolo_cplPanel.rates.init()
devolo_cplPanel.rates.addRatesCard();
document.getElementById('devolo_cpl_rates').querySelector('div.card .button.ok').click()

// vim: tabstop=2 autoindent
