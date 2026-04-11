"use strict"

if (typeof devolo_cplPanel === 'undefined') {
	var devolo_cplPanel = {

		ajaxUrl: "plugins/devolo_cpl/core/ajax/devolo_cpl.ajax.php",
		chart_defaults: {
			credits: {
				enabled: false,
			},
			exporting: {
				libURL: "3rdparty/highstock/lib/",
			},
			lang: {
				downloadCSV: "{{Téléchargement CSV}}",
				downloadJPEG: "{{Téléchargement JPEG}}",
				downloadPDF: "{{Téléchargement PDF}}",
				downloadPNG: "{{Téléchargement PNG}}",
				downloadSVG: "{{Téléchargement SVG}}",
				downloadXLS: "{{Téléchargement XLS}}",
				printChart: "{{Imprimer}}",
				viewFullscreen: "{{Plein écran}}",
			},
			legend: {
				enabled: true,
				align: "left",
			},
			rangeSelector: {
				buttonTheme: {
					width: "auto",
					padding: 4,
				},
				buttons: [
					{
						type: "all",
						count: 1,
						text: "{{Tous}}",
					},
					{
						type: "hour",
						count: 1,
						text: "{{Heure}}",
					},
					{
						type: "day",
						count: 1,
						text: "{{Jour}}",
					},
					{
						type: "week",
						count: 1,
						text: "{{Semaine}}",
					},
					{
						type: "month",
						count: 1,
						text: "{{Mois}}",
					},
				],
				selected: 2,
				inputEnabled: false,
				x: 0,
				y: 0,
			},
			xAxis: {
				type: "datetime",
			},
		},

		init: function() {
			document.getElementById('div_pageContainer').addEventListener("click", function(event) {
				let _target = null

				if (_target = event.target.closest(".mainnav li[data-panel]")) {
					devolo_cplPanel.hideAll()
						_target.addClass("active")
						let panelId = _target.dataset.panel
						document.getElementById(panelId).seen()
				}

				if (_target = event.target.closest(".bt-close_card")) {
					_target.closest('.card').remove()
				}

			})
			devolo_cplPanel.hideAll()
			document.getElementById('div_pageContainer').querySelector(".mainnav li[data-panel=devolo_cpl_rates]").click()
		},

		hideAll: function() {
			document.getElementById('div_pageContainer').querySelectorAll(".mainnav li").removeClass("active")
			document.getElementById('div_devolo_cpl').querySelectorAll(".graph_tab").unseen()
		},
	}
}
devolo_cplPanel.init()

// vim: tabstop=2 autoindent
