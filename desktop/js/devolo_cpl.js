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
		mId_checkRateCmds: "mod_checkRateCmds",

		ajaxUrl: "plugins/devolo_cpl/core/ajax/devolo_cpl.ajax.php",

		init: function() {
			/*
			 * Initialisation après chargement de la page
			 */
			document.getElementById("div_pageContainer").addEventListener("click", function (event) {
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
					if (_target = event.target.closest("#bt_checkRateCmds")) {
						devolo_cplFrontEnd.checkRateCmds()
						return
					}

					if (_target = event.target.closest("#bt_community")) {
						devolo_cplFrontEnd.community()
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

					if (_target = event.target.closest(".cmdAction[data-action=addRateDown]")) {
						devolo_cplFrontEnd.createRateCmd("down")
						return
					}

					if (_target = event.target.closest(".cmdAction[data-action=addRateUp]")) {
						devolo_cplFrontEnd.createRateCmd("up")
						return
					}

					return
				})
			document .getElementById("div_pageContainer") .addEventListener("change", function (event) {
					let _target = null

					if (_target = event.target.closest('.eqLogicAttr[data-l1key=configuration][data-l2key=model]')) {
						devolo_cplFrontEnd.changeModelImage(_target)
						return
					}

					if (_target = event.target.closest('.cmdAttr[data-l1key=configuration][data-l2key=target]')) {
						devolo_cplFrontEnd.checkRateTarget(_target)
						return
					}

					if (_target = event.target.closest('.eqLogicAttr[data-l1key=configuration][data-l2key=network]')) {
						devolo_cplFrontEnd.checkAllRateTarget()
						return
					}
				})
		},

		syncDevolo: function() {
			domUtils.showLoading()
			setTimeout(function () {
				domUtils.ajax({
					url: devolo_cplFrontEnd.ajaxUrl,
					data: {
						action: "syncDevolo",
					},
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

		checkRateCmds: function() {
			jeeDialog.dialog({
				id: devolo_cplFrontEnd.mId_checkRateCmds,
				title: '{{Titre checkRates}}',
				contentUrl: 'index.php?v=d&plugin=devolo_cpl&modal=checkRateCmds',
				buttons: {
					confirm: {
						callback: {
							click: function() {
								devolo_cplFrontEnd.mod_checkRateCmds.close()
							}
						}
					}
				},
			})
		},

		community: function() {
			jeedom.plugin.createCommunityPost({
				type: ('devolo_cpl'),
				error: function(error) {
					domUtils.hideLoading()
					jeedomUtils.showAlert({
						message: error.message,
						level: 'danger'
					})
				},
				success: function(data) {
					let element = document.createElement('a')
					element.setAttribute('href', data.url)
					element.setAttribute('target', '_blank')
					element.style.display = 'none'
					document.body.appendChild(element)
					element.click()
					document.body.removeChild(element)
				}
			})
			return
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

		createRateCmd: function(direction) {
			let cmd = {
				type: 'info',
				subType: 'numeric',
				isHistorized: '0',
			}
			if (direction == 'up') {
				cmd.logicalId = 'rate_upload'
			} else if (direction == 'down') {
				cmd.logicalId = 'rate_download'
			}
			cmd.configuration = {}
			cmd.configuration.target = -1
			cmd.unite = '{{Mbit/s}}'
			cmd.configuration.minValue = 0
			cmd.configuration.maxValue = 1000
			devolo_cplFrontEnd.addCmdToTable(cmd)
		},

		checkRateTarget: function(select) {
			let network = document.getElementById('div_pageContainer').querySelector('.eqLogicAttr[data-l1key=configuration][data-l2key=network]').jeeValue()
			let selectedIndex = select.selectedIndex
			let networkTarget = null
			if (selectedIndex >= 0) {
				networkTarget = select.options[selectedIndex].dataset.network
			}
			if (network != networkTarget) {
				select.addClass('warning')
				return false
			}
			let counter = 0
			let logicalId = select.closest('tr').querySelector('[data-l1key=logicalId]').jeeValue()
			let targetId = select.closest('tr').querySelector('[data-l1key=configuration][data-l2key=target]').jeeValue()
			document.getElementById('table_cmd').querySelectorAll('[data-l1key=configuration][data-l2key=target]').forEach(function(elem) {
				if (elem.jeeValue() == targetId) {
					if (elem.closest('tr').querySelector('[data-l1key=logicalId]').jeeValue() == logicalId) {
						counter++
					}
				}
			})
			if (counter > 1 ) {
				select.addClass('warning')
				return false
			}
			select.removeClass('warning')
			return true
		},

		checkAllRateTarget: function() {
			let ok = true
			document.getElementById('table_cmd').querySelectorAll('select.cmdAttr[data-l1key=configuration][data-l2key=target]').forEach(function(select){
				if (! devolo_cplFrontEnd.checkRateTarget(select)) {
					ok = false
				}
			})
			return ok
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
			tr += '<span class="cmdAttr hidden" data-l1key="value" style="display:none;"></span>'
			tr += '<input class="valueName form-control input-sm" style="display:none;" disabled></input>'
			tr += '</td>'

			// LOGICALID
			tr += "<td>"
			tr += '<input class="cmdAttr form-control input-sm" data-l1key="logicalId" disabled></input>'
			tr += "</td>"

			// TYPE
			tr += '<td>'
			tr += '<div>'
			tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" style="margin-bottom:1.2px" disabled></input>'
			tr += '</div>'
			tr += '<div>'
			tr += '<input class="cmdAttr form-control input-sm" data-l1key="subType" disabled></input>'
			tr += '</div>'
			tr += '</td>'

			// PARAMETRE
			tr += '<td>'
			if (_cmd.logicalId == 'rate_upload' || _cmd.logicalId == 'rate_download') {
				let label = '{{Flux depuis}}:'
				if (_cmd.logicalId == 'rate_upload'){
					label = '{{Flux vers}}:'
				}
				tr += '<div style="margin-top:8px;">'
				tr += '<span>' + label + '</span>'
				tr += '</div>'
				tr += '<div style="margin-top:8px;">'
				tr += '<select class="cmdAttr form-control input-sm" data-l1key=configuration data-l2key=target>'
				tr += '</select>'
				tr += '</div>'
			}
			tr += '</td>'

			// OPTIONS
			tr += '<td>'
			tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
			tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
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
			newRow.setJeeValues(_cmd, '.cmdAttr')
			if (_cmd.logicalId == 'rate_upload' || _cmd.logicalId == 'rate_download') {
				domUtils.ajax({
					url: devolo_cplFrontEnd.ajaxUrl,
					data: {
						action: "getPartners",
						id: document.querySelector('.eqLogicAttr[data-l1key="id"]').jeeValue(),
					},
					success: function(data) {
						if (data.state != "ok") {
							jeedomUtils.showAlert({
								message: data.result,
								level: "danger",
							})
							return
						}
						let result = data.result

						let usedTarget = []
						if (_cmd.configuration.target == -1) {
							document.getElementById('table_cmd').querySelectorAll('[data-l1key=configuration][data-l2key=target]').forEach(function(elem){
								if (elem.jeeValue() == '') {
									return
								}
								let logicalId = elem.closest('tr').querySelector('[data-l1key=logicalId]').jeeValue()
								if (logicalId != _cmd.logicalId) {
									return
								}
								usedTarget.push (elem.jeeValue())
							})
						}

						let options = ""
						let eqLogic_id = document.querySelector('.eqLogicAttr[data-l1key="id"]').jeeValue()
						for (let i in result) {
							if (result[i].id == eqLogic_id) {
								continue
							}
							options += '<option value="' + result[i].id + '" data-network="' + result[i].network + '">' + result[i].name + '</option>'
							if ((_cmd.configuration.target == -1) && (! usedTarget.includes(result[i].id))) {
								_cmd.configuration.target = result[i].id
							}
						}
						newRow.querySelector('select[data-l1key=configuration][data-l2key=target]').innerHTML = options
						newRow.setJeeValues(_cmd, '.cmdAttr')
					}
				})
			} else {
				newRow.setJeeValues(_cmd, '.cmdAttr')
			}
			document.getElementById("table_cmd").querySelector("tbody").appendChild(newRow)

			jeedom.cmd.changeType(newRow, init(_cmd.subType))
			if (_cmd.value) {
			 	jeedom.eqLogic.getCmd({
					id: document.querySelector('.eqLogicAttr[data-l1key="id"]').jeeValue(),
					success: function(cmds) {
						for (let i in cmds) {
			 				if (cmds[i].id == _cmd.value) {
								newRow.querySelector('.valueName').jeeValue(cmds[i].name)
								newRow.querySelector('.valueName').seen()
								break
							}
						}
					}
				})
			}
		},
	}
}
devolo_cplFrontEnd.init();
addCmdToTable = devolo_cplFrontEnd.addCmdToTable

// vim: tabstop=2 autoindent
