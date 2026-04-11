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

"use strict";
if (typeof devolo_cplFrontEnd === "undefined") {
	var devolo_cplFrontEnd = {
		mId_showNetwork: "mod_showNetwork",
		mId_macAdresses: "mod_macAdresses",
		ajaxUrl: "plugins/devolo_cpl/core/ajax/devolo_cpl.ajax.php",

		init: function() {
			/*
			 * Initialisation après chargement de la page
			 */
			document
				.getElementById("div_pageContainer")
				.addEventListener("click", function (event) {
					let _target = null

					if (_target = event.target.closest("#bt_syncDevolo")) {
						devolo_cplFrontEnd.syncDevolo()
						return
					}

					if (_target = event.target.closest("#bt_devoloNetwork")) {
						devolo_cplFrontEnd.showNetwork()
						return
					}

					if (_target = event.target.closest("#bt_devoloMacInfo")) {
						devolo_cplFrontEnd.macAdresses()
						return
					}

					if (_target = event.target.closest(".macinfoAction[data-action=remove]")) {
						devolo_cplFrontEnd.mod_MacAdresses.removeMac(_target)
						return
					}

					if (_target = event.target.closest(".eqLogicAction[data-action=save_devolo")) {
						devolo_cplFrontEnd.checkAndSave()
						return
					}

					return
				})
			document
				.getElementById("div_pageContainer")
				.addEventListener("change", function (event) {
					let _target = null

					if (_target = event.target.closest(".eqLogicAttr[data-l1key=configuration][data-l2key=model]")) {
						devolo_cplFrontEnd.changeModelImage(_target)
					}

					return
				})
		},

		syncDevolo: function() {
			domUtils.showLoading()
			setTimeout(function () {
				domUtils.ajax({
					type: "POST",
					async: false,
					global: false,
					url: devolo_cplFrontEnd.ajaxUrl,
					data: {
						action: "syncDevolo",
					},
					dataType: "json",
					success: function(data) {
						if (data.state != "ok") {
							jeedomUtils.showAlert({
								message: data.result,
								level: "danger",
							})
							return
						}
						jeedomUtils.loadPage(document.URL)
					}
				})
			})
		},

		showNetwork: function() {
			jeeDialog.dialog({
				id: devolo_cplFrontEnd.mId_showNetwork,
				title: "{{Réseaux}}",
				backdrop: false,
				contentUrl: "index.php?v=d&plugin=devolo_cpl&modal=network",
			})
		},

		macAdresses: function() {
			jeeDialog.dialog({
				id: devolo_cplFrontEnd.mId_macAdresses,
				setTitle: false,
				backdrop: false,
				contentUrl: "index.php?v=d&plugin=devolo_cpl&modal=macinfo",
				callback: function() {
					devolo_cplFrontEnd.mod_macAdresses.setChanged('0')
				},
				buttons: {
					cancel: {
						callback: {
							click: function() {
								if ( devolo_cplFrontEnd.mod_macAdresses.changed()) {
									jeeDialog.confirm("{{Attention vous quittez une page ayant des données modifiées non sauvegardées. Voulez-vous continuer ?}}", function (result) {
										if (result) {
											document.getElementById(devolo_cplFrontEnd.mId_macAdresses)._jeeDialog.close()
										}
									})
								} else {
									document.getElementById(devolo_cplFrontEnd.mId_macAdresses)._jeeDialog.close()
									return
								}
							},
						},
					},
					confirm: {
						callback: {
							click: function() {
								devolo_cplFrontEnd.mod_macAdresses.save()
							}
						}
					}
				},
			})
		},

		checkAndSave: function() {
			let select = document.getElementById('selectModel')
			let eqManageable = select.options[select.selectedIndex].getAttribute('manageable')
			if (eqManageable != 1) {
				let cmdToRemove = "";
				let cmds = document.getElementById("table_cmd").querySelectorAll(".cmd.manageable-only")
				if (cmds.length) {
					for (let i = 0; i < cmds.length; i++) {
						let logicalId = cmds[i].querySelector(".cmdAttr[data-l1key=logicalId]").textContent
						cmdToRemove += "<li>" + logicalId + "</li>"
					}
					let message = "{{Le changement de modèle implique la suppression des commandes suivantes:}}"
					message += "<ul>" + cmdToRemove + "</ul>"
					jeeDialog.confirm(message, function(result) {
						if (result) {
							document.getElementById("table_cmd").querySelectorAll(".cmd.manageable-only").remove()
							document.querySelector(".eqLogicAction[data-action=save]").click()
						}
					})
				}
			} else {
				document.querySelector(".eqLogicAction[data-action=save]").click()
			}
		},

		changeModelImage: function(select) {
			if (select.selectedIndex < 0) {
				return
			}
			let model = select.value
			let img = select.options[select.selectedIndex].getAttribute('img')
			let manageable = select.options[select.selectedIndex].getAttribute('manageable')
			if (manageable == 1) {
				document.querySelectorAll('.manageable-only').seen()
			} else {
				document.querySelectorAll('.manageable-only').unseen()
			}
			document.getElementById('img_equipement').src = img
			document.getElementById('code_equipement').html(model)
		},

		addCmdToTable: function(_cmd) {
			if (!isset(_cmd)) {
				let _cmd = { configuration: {} };
			}
			if (!isset(_cmd.configuration)) {
				_cmd.configuration = {};
			}
			let manageable = "";
			if (isset(_cmd.logicalId)) {
				if (isset(cmdsDef[_cmd.logicalId])) {
					if (isset(cmdsDef[_cmd.logicalId]["manageableOnly"])) {
						if (cmdsDef[_cmd.logicalId]["manageableOnly"] == 1) {
							manageable = "manageable-only";
						}
					}
				}
			}
			let tr = '<tr>'
			// ID
			tr += '<td class="hidden-xs">'
			tr += '<span class="cmdAttr" data-l1key="id"></span>'
			tr += "</td>"

			// NOM
			tr += '<td>'
			tr += '<div class="input-group">'
			tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
			tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
			tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
			tr += '</div>'
			tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande info liée}}">'
			tr += '<option value="">{{Aucune}}</option>'
			tr += '</select>'
			tr += '</td>'

			// LOGICALID
			tr += "<td>"
			tr += '<span class="cmdAttr" data-l1key="logicalId"></span>'
			tr += "</td>"

			// TYPE
			tr += "<td>"
			tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + "</span>"
			tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
			tr += "</td>"

			// OPTIONS
			tr += "<td>"
			tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
			tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
			tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> '
			tr += '<div style="margin-top:7px;">'
			tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
			tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
			tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
			tr += "</div>"
			tr += "</td>"

			// ETAT
			tr += "<td>"
			tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>'
			tr += "</td>"

			// ACTION
			tr += "<td>"
			if (is_numeric(_cmd.id)) {
				tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
				tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>'
			}
			tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i>'
			tr += "</td>"
			tr += '</tr>'

			let newRow = document.createElement("tr")
			newRow.innerHTML = tr
			newRow.addClass("cmd")
			if (manageable) {
				newRow.addClass(manageable)
			}
			newRow.setAttribute("data-cmd_id", init(_cmd.id))
			document.getElementById("table_cmd").querySelector("tbody").appendChild(newRow)
			jeedom.eqLogic.buildSelectCmd({
				id: document.querySelector('.eqLogicAttr[data-l1key="id"]').jeeValue(),
				filter: {type: 'info'},
				error: function(error) {
					jeedomUtils.showAlert({ message: error.message, level: 'danger' })
				},
				success: function(result) {
					newRow.querySelector('.cmdAttr[data-l1key="value"]').insertAdjacentHTML('beforeend', result)
					newRow.setJeeValues(_cmd, '.cmdAttr')
					jeedom.cmd.changeType(newRow, init(_cmd.subType))
				}
			})
		},

	}
}
devolo_cplFrontEnd.init();
addCmdToTable = devolo_cplFrontEnd.addCmdToTable

// vim: tabstop=2 autoindent
