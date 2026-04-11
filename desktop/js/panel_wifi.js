"use strict"

if (typeof devolo_cplPanel.wifi === 'undefined') {
	devolo_cplPanel.wifi = {

		wifiId: 0,

		nextId: function() {
			devolo_cplPanel.wifi.wifiId++
			return devolo_cplPanel.wifi.wifiId
		},

		init: function() {
			document.getElementById('devolo_cpl_wifi').addEventListener('click', function(event) {
				let _target = null

				if (_target = event.target.closest('#bt_addWifiCard')) {
					devolo_cplPanel.wifi.addWifiCard()
					return
				}

				if (_target = event.target.closest('div.card .button.ok')) {
					devolo_cplPanel.wifi.loadGraph(_target.closest('.card'))
					return
				}
			})

			document.getElementById('devolo_cpl_wifi').addEventListener('change', function(event) {
				let _target = null

				if (_target = event.target.closest('select.devolo_wifi_view')) {
					devolo_cplPanel.wifi.viewChanged(_target.closest('.card'))
					return
				}
			})
		},

		addWifiCard: function() {
			let card = "<div>";
			card += '<div class="card-header">';
			card += '<div class="col-sm-3">';
			card += '<i class="fas fa-wifi"></i>';
			card += "<h3>{{connections WiFi}}</h3>";
			card += "</div>";
			card += '<div class="col-sm-5">';
			card += '<span class="label">{{Equipement}}:</span>';
			card += '<select class="devolo_wifi_view">';
			card += '<option value="ap">{{AP}}</option>';
			card += '<option value="client">{{client}}</option>';
			card += "</select>";
			card += '<select class="graphview" data-view="ap">';
			for (let name in eqList) {
				if (eqList[name]["wifi"] == 1) {
					card += '<option value="' + eqList[name]["serial"] + '">';
					card += name;
					card += "</option>";
				}
			}
			card += "</select>";
			card += '<select class="graphview" data-view="client">';
			for (let id in macinfo) {
				card +=
					'<option value="' +
					macinfo[id]["mac"] +
					'">' +
					macinfo[id]["displayname"] +
					"</option>";
			}
			card += "</select>";
			card += '<span class="button ok cursor">{{OK}}</span>';
			card += "</div>";
			card += '<i class="fas fa-times-circle bt-close_card"</i>';
			card += "</div>";
			card +=
				'<div id="WifiGraph_' + devolo_cplPanel.wifi.nextId() + '" class="card-content">';
			card += "</div>";
			card += "</div>";
			let newCard = document.createElement('div')
			newCard.innerHTML = card
			newCard.addClass('card')
			document.getElementById('devolo_cpl_wifi').appendChild(newCard)
			devolo_cplPanel.wifi.viewChanged(newCard)
		},

		viewChanged: function(card) {
			let selectedView = card.querySelector('select.devolo_wifi_view').value
			card.querySelectorAll('select.graphview[data-view]').unseen()
			card.querySelectorAll('select.graphview[data-view=' + selectedView + ']').seen()
		},

		loadGraph: function(card){
			let graphId = card.querySelector(".card-content").id
			let selectedView = card.querySelector('select.devolo_wifi_view').value

			let selectKey = card.querySelector('select[data-view=' + selectedView + ']')
			let title = selectKey.options[selectKey.selectedIndex].text
			let key = selectKey.value

			let actionAjax = ''
			if (selectedView == "ap") {
				actionAjax = "wifiHistorique_ap";
			} else {
				actionAjax = "wifiHistorique_client";
			}

			domUtils.ajax({
				type: "POST",
				async: false,
				global: false,
				url: devolo_cplPanel.ajaxUrl,
				data: {
					action: actionAjax,
					key: key
				},
				dataType: 'json',
				success: function(data) {
					if (data.state != "ok") {
						jeedomUtils.showAlert({
							message: data.result,
							level: "danger",
						})
						return
					}
					let categories = [];
					let networks = [];
					for (let entry of data.result) {
						if (!categories.includes(entry.category)) {
							categories.push(entry.category);
						}
						if (!networks.includes(entry.network)) {
							networks.push(entry.network);
						}
					}
					categories.sort();
					networks.sort();
					let categoryNr = {};
					for (let i = 0; i < categories.length; i++) {
						categoryNr[categories[i]] = i;
					}
					if (selectedView == "client") {
						for (let i = 0; i < categories.length; i++) {
							for (let eq in eqList) {
								if (eqList[eq].serial == categories[i]) {
									categories[i] = eq;
								}
							}
						}
					}
					let datas = [];
					for (let network of networks) {
						datas[network] = [];
					}
					for (let entry of data.result) {
						data = {};
						data.x = parseInt(entry.connect_time);
						data.x2 = parseInt(entry.disconnect_time);
						data.y = categoryNr[entry.category];
						datas[entry.network].push(data);
					}
					let chart_config = {
						...devolo_cplPanel.chart_defaults,
						...{
							chart: {
								displayErrors: true,
								type: "xrange",
								zoomType: "x",
								spacingBottom: 5,
								spacingTop: 5,
								spacingRight: 5,
								spacingLeft: 5,
								style: {
									fontFamily: "Roboto",
								},
							},
							title: {
								text: title,
							},
							accessibility: {
								point: {
									descriptionFormatter: function (point) {
										var ix = point.index + 1,
											category = point.yCategory,
											from = new Date(point.x),
											to = new Date(point.x2);
										return (
											ix +
											". " +
											category +
											", " +
											from.toDateString() +
											" to " +
											to.toDateString() +
											"."
										);
									},
								},
							},
							xAxis: {
								type: "datetime",
							},
							yAxis: {
								title: {
									text: "",
								},
								categories: categories,
								reversed: true,
							},
							series: [],
						},
					};
					for (let network of networks) {
						chart_config.series.push({
							name: network,
							borderColor: "gray",
							pointWidth: 10,
							data: datas[network],
							dataLabels: {
								enabled: true,
							},
							showInLegend: true,
						});
					}
					let chart = new Highcharts.chart(graphId, chart_config);
				},
			})
		},

	}
}
devolo_cplPanel.wifi.init()
devolo_cplPanel.wifi.addWifiCard();
document.getElementById('devolo_cpl_wifi').querySelector('div.card .button.ok').click();

// vim: tabstop=2 autoindent
