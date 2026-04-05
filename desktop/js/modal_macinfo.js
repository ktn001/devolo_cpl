"use strict"

if (typeof devolo_cplFrontEnd.mod_macAdresses === 'undefined') {
	devolo_cplFrontEnd.mod_macAdresses = {
		ajaxUrl: "/plugins/devolo_cpl/core/ajax/devolo_macinfo.ajax.php",

		init: function() {
			document.getElementById(devolo_cplFrontEnd.mId_macAdresses).addEventListener("click", function(event) {
				let _target = null

				if ((_target = event.target.closest(".macinfoAction[data-action=remove]"))) {
					devolo_cplFrontEnd.mod_macAdresses.remove(_target)
					return
				}
			})
			document.getElementById(devolo_cplFrontEnd.mId_macAdresses).addEventListener("change", function(event) {
				devolo_cplFrontEnd.mod_macAdresses.setChanged('1')
			})
		},

		setChanged: function(value) {
			document.getElementById(devolo_cplFrontEnd.mId_macAdresses).dataset.changed = value
		},

		changed: function() {
			return document.getElementById(devolo_cplFrontEnd.mId_macAdresses).dataset.changed == '1'
		},

		remove: function(btn) {
			btn.closest('tr').remove()
			devolo_cplFrontEnd.mod_macAdresses.setChanged('1')
		},

		save: function(close=true) {
			if (! devolo_cplFrontEnd.mod_macAdresses.changed()) {
				if (close) {
					document.getElementById(devolo_cplFrontEnd.mId_macAdresses)._jeeDialog.close()
				}
				return
			}
			macInfos = document.getElementById("table_macinfo").querySelectorAll(".macinfo").getJeeValues(".macinfoAttr")
			domUtils.ajax({
				type: "POST",
				async: false,
				global: false,
				url: devolo_cplFrontEnd.mod_macAdresses.ajaxUrl,
				data: {
		 			action: "save",
 					macinfos: json_encode(macInfos),
 				},
 				dataType: "json",
				success: function (data) {
		 			if (data.state != "ok") {
						jeedomUtils.showAlert({
							message: data.result,
							level: "danger",
						})
						return
					}
					if (close) {
						document.getElementById(devolo_cplFrontEnd.mId_macAdresses)._jeeDialog.close()
					}
				},
			})
		},

		loadAll: function() {
			domUtils.ajax({
				type: "POST",
				async: false,
				global: false,
				url: devolo_cplFrontEnd.mod_macAdresses.ajaxUrl,
				data: {
					action: "getAll",
				},
				dataType: "json",
				success: function (data) {
		 			if (data.state != "ok") {
						jeedomUtils.showAlert({
							message: data.result,
							level: "danger",
						})
						return
					}
					document.getElementById("table_macinfo").tBodies[0].replaceChildren()
		 			for (let macinfo of json_decode(data.result)) {
		 				devolo_cplFrontEnd.mod_macAdresses.addMacinfoToTable(macinfo);
		 			}
		 			devolo_cplFrontEnd.mod_macAdresses.setChanged('0')
				},
			})
		},

		addMacinfoToTable: function(_macinfo) {
			let tr = '<tr>';
			tr += '<td class="hidden-xs">';
			tr += '<span class="macinfoAttr" data-l1key="id"></span>';
			tr += "</td>";
			tr += "<td>";
			tr += '<span class="macinfoAttr" data-l1key="mac"></span>';
			tr += "</td>";
			tr += "<td>";
			tr += '<span class="macinfoAttr" data-l1key="vendor"></span>';
			tr += "</td>";
			tr += "<td>";
			tr +=
				'<input class="macinfoAttr form-control input-sm" data-l1key="name" maxlength="30"></input>';
			tr += "</td>";
			tr += "<td>";
			tr +=
				'<i class="fas fa-minus-circle pull-right macinfoAction cursor" data-action="remove"></i>';
			tr += "</td>";
			tr += "</tr>";
			let newRow = document.createElement("tr")
			newRow.innerHTML = tr
			newRow.addClass("macinfo")
			newRow.dataset.macinfo_id = init(_macinfo.id)
			newRow.setJeeValues(_macinfo, ".macinfoAttr");
			document.getElementById("table_macinfo").querySelector("tbody").appendChild(newRow)
		},
	}
}
devolo_cplFrontEnd.mod_macAdresses.init()
devolo_cplFrontEnd.mod_macAdresses.loadAll();

// vim: tabstop=2 autoindent
