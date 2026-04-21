"use strict"

if (typeof devolo_cplFrontEnd.mod_checkRateCmds === 'undefined') {
	devolo_cplFrontEnd.mod_checkRateCmds = {

		init: function(){
			let btnCancel = document.getElementById(devolo_cplFrontEnd.mId_checkRateCmds).querySelector('.button[data-type=cancel]')
			if (is_object(btnCancel)) {
				btnCancel.remove()
			}
			domUtils.ajax({
				url: devolo_cplFrontEnd.ajaxUrl,
				data: {
					action: 'checkRateCmds',
				},
				success: function(data){
					if (data.state != "ok") {
						jeedomUtils.showAlert({
							message: data.result,
							level: "danger",
						})
						return
					}
					let result = data.result
					let resultContent = document.getElementById(devolo_cplFrontEnd.mId_checkRateCmds).querySelector('#resultContent')
					resultContent.innerText = ''

					if (Object.keys(result).length == 0) {
						let div = document.createElement('div').addClass('rateCmdsOk')
						div.innerText='{{Toutes les commandes de débits sont OK}}'
						resultContent.append(div)
					} else {
						console.log(result)
						for (let eqName of Object.keys(result)) {
							let eqDiv = document.createElement('div').addClass('equipement')
							eqDiv.innerText = eqName + ":"
							resultContent.append(eqDiv)
							if (result[eqName]['info']) {
								let lignes = []
								let keyText = {
									network : '{{réseau cpl}}',
									id : 'ID',
								}
								for ( let infoKey in result[eqName]['info']){
									lignes.push( keyText[infoKey] + ': <b>"' + result[eqName]['info'][infoKey] + '"</br>')
								}

								let infoDiv = document.createElement('div')
								infoDiv.innerHTML = lignes.join('<br>')
								eqDiv.append(infoDiv)
							}

							// UPTOREMOVE
							if ('upToRemove' in result[eqName]) {
								let entries = result[eqName]['upToRemove']
								let txt = '{{Les commandes de débit montant suivantes devraient être supprimées}}'
								if (Object.keys(entries).length == 1) {
									txt = '{{La commande de débit montant suivante devrait être supprimée}}'
								}
								txt += '<sup><i class="fas fa-question-circle" title="L\'option <b>Flux montant</b> n\'est pas activée dans la config du plugin"></i></sup>'
								let upToRemoveTitle = document.createElement('div').addClass('errorTypeTitle')
								upToRemoveTitle.innerHTML = txt
								resultContent.append(upToRemoveTitle)

								let cmdsList = document.createElement('ul')
								for (let entry of entries) {
									let ligne = '<li>'
									ligne += '<b>' + entry.cmdName + '</b> '
									ligne += '(Id: <b>' + entry.cmdId + '</b>), '
									ligne += '{{équipement cible}}: <b>' + entry.targetName + '</b> '
									ligne += '(Id: <b>' + entry.targetId + '</b>)'
									ligne += '</li>'
									let li = document.createElement('li')
									li.innerHTML = ligne
									cmdsList.append(li)
								}
								resultContent.append(cmdsList)
							}

							// DOWNTOREMOVE
							if ('downToRemove' in result[eqName]) {
								let entries = result[eqName]['downToRemove']
								let txt = '{{Les commandes de débit descendant suivantes devraient être supprimées}}'
								if (Object.keys(entries).length == 1) {
									txt = '{{La commande de débit descendant suivante devrait être supprimée}}'
								}
								txt += '<sup><i class="fas fa-question-circle" title="L\'option <b>Flux montant</b> n\'est pas activée dans la config du plugin"></i></sup>'
								let downToRemoveTitle = document.createElement('div').addClass('errorTypeTitle')
								downToRemoveTitle.innerHTML = txt
								resultContent.append(downToRemoveTitle)

								let cmdsList = document.createElement('ul')
								for (let entry of entries) {
									let ligne = '<li>'
									ligne += '<b>' + entry.cmdName + '</b> '
									ligne += '(Id: <b>' + entry.cmdId + '</b>), '
									ligne += '{{équipement cible}}: <b>' + entry.targetName + '</b> '
									ligne += '(Id: <b>' + entry.targetId + '</b>)'
									ligne += '</li>'
									let li = document.createElement('li')
									li.innerHTML = ligne
									cmdsList.append(li)
								}
								resultContent.append(cmdsList)
							}

							// NOTARGET
							if ('noTarget' in result[eqName]) {
								let entries = result[eqName]['noTarget']
								let txt = '{{Les commandes suivantes n\'ont pas d\'équipement cible défini}}'
								if (Object.keys(entries).length == 1) {
									txt = '{{La commande suivante n\'a pas d\'équipement cible défini}}'
								}
								txt += '<sup><i class="fas fa-question-circle" title="ou l\'équipement cible est introuvable"></i></sup>'
								let noTargetTitle = document.createElement('div').addClass('errorTypeTitle')
								noTargetTitle.innerHTML = txt
								resultContent.append(noTargetTitle)

								let cmdsList = document.createElement('ul')
								for (let entry of entries) {
									let ligne = '<li>'
									ligne += '<b>' + entry.cmdName + '</b> '
									ligne += '(Id: <b>' + entry.cmdId + '</b>),'
									ligne += '</li>'
									let li = document.createElement('li')
									li.innerHTML = ligne
									cmdsList.append(li)
								}
								resultContent.append(cmdsList)
							}

							// WRONGTYPE
							if ('wrongType' in result[eqName]) {
								let entries = result[eqName]['wrongType']
								let txt = "{{L'équipement cible des commandes suivantes n'est pas un devolo_cpl}}"
								if (Object.keys(entries).length == 1) {
									txt = "{{L'équipement cible de la commandes suivante n'est pas un devolo_cpl}}"
								}
								let wrongTypeTitle = document.createElement('div').addClass('errorTypeTitle')
								wrongTypeTitle.innerHTML = txt
								resultContent.append(wrongTypeTitle)

								let cmdsList = document.createElement('ul')
								for (let entry of entries) {
									let ligne = '<li>'
									ligne += '<b>' + entry.cmdName + '</b> '
									ligne += '(Id: <b>' + entry.cmdId + '</b>), '
									ligne += 'Cible: <b>' + entry.targetName + '</b> (Id: <b>' + entry.targetId + '</b>, Type: <b>' + entry.targetType + '</b>)'
									ligne += '</li>'
									let li = document.createElement('li')
									li.innerHTML = ligne
									cmdsList.append(li)
								}
								resultContent.append(cmdsList)
							}

							// LOOP
							if ('loop' in result[eqName]) {
								let entries = result[eqName]['loop']
								let txt = "{{L'équipement cible des commandes suivantes est l'équipement de la commande}}"
								if (Object.keys(entries).length == 1) {
									txt = "{{L'équipement cible de la commande suivante est l'équipement de la commande}}"
								}
								let loopTitle = document.createElement('div').addClass('errorTypeTitle')
								loopTitle.innerHTML = txt
								resultContent.append(loopTitle)

								let cmdsList = document.createElement('ul')
								for (let entry of entries) {
									let ligne = '<li>'
									ligne += '<b>' + entry.cmdName + '</b> '
									ligne += '(Id: <b>' + entry.cmdId + '</b>), '
									ligne += 'Cible: <b>' + entry.targetName + '</b> (Id: <b>' + entry.targetId + '</b>)'
									ligne += '</li>'
									let li = document.createElement('li')
									li.innerHTML = ligne
									cmdsList.append(li)
								}
								resultContent.append(cmdsList)
							}

							// WRONGNETWORK
							if ('wrongNetwork' in result[eqName]) {
								let entries = result[eqName]['wrongNetwork']
								let txt = "{{L'équipement cible des commandes suivantes est dans un autre réseau devolo_cpl}}"
								if (Object.keys(entries).length == 1) {
									txt = "{{L'équipement cible de la commandes suivante est dans un autre réseau devolo_cpl}}"
								}
								let wrongNetworkTitle = document.createElement('div').addClass('errorTypeTitle')
								wrongNetworkTitle.innerHTML = txt
								resultContent.append(wrongNetworkTitle)

								let cmdsList = document.createElement('ul')
								for (let entry of entries) {
									let ligne = '<li>'
									ligne += '<b>' + entry.cmdName + '</b> '
									ligne += '(Id: <b>' + entry.cmdId + '</b>), '
									ligne += 'Cible: <b>' + entry.targetName + '</b> (Id: <b>' + entry.targetId + '</b>, réseau cpl: <b>"' + entry.targetNetwork + '"</b>)'
									ligne += '</li>'
									let li = document.createElement('li')
									li.innerHTML = ligne
									cmdsList.append(li)
								}
								resultContent.append(cmdsList)
							}

							//MISSINGIP
							if ('missingUp' in result[eqName]) {
								let entries = result[eqName]['missingUp']
								let txt = "{{Les commandes pour les débits montants vers les équipements suivants sont introuvables}}"
								if (Object.keys(entries).length == 1) {
									txt = "{{La commande pour le débit montant vers l'équipement suivant est introuvable}}"
								}
								let missingUpTitle = document.createElement('div').addClass('errorTypeTitle')
								missingUpTitle.innerHTML = txt
								resultContent.append(missingUpTitle)

								let eqsList = document.createElement('ul')
								for (let id in entries) {
									let ligne = '<li>'
									ligne += '<b>' + entries[id]['eqName'] + '</b>'
									ligne += '</li>'
									let li = document.createElement('li')
									li.innerHTML = ligne
									eqsList.append(li)
								}
								resultContent.append(eqsList)
							}

							//MISSINGDOWN
							if ('missingDown' in result[eqName]) {
								let entries = result[eqName]['missingDown']
								let txt = "{{Les commandes pour les débits descendants depuis les équipements suivants sont introuvables}}"
								if (Object.keys(entries).length == 1) {
									txt = "{{La commande pour le débit descendant depuis l'équipement suivant est introuvable}}"
								}
								let missingDownTitle = document.createElement('div').addClass('errorTypeTitle')
								missingDownTitle.innerHTML = txt
								resultContent.append(missingDownTitle)

								let eqsList = document.createElement('ul')
								for (let id in entries) {
									let ligne = '<li>'
									ligne += '<b>' + entries[id]['eqName'] + '</b>'
									ligne += '</li>'
									let li = document.createElement('li')
									li.innerHTML = ligne
									eqsList.append(li)
								}
								resultContent.append(eqsList)
							}
						}
						jeedomUtils.initTooltips(resultContent)
					}
				}
			})
		},

		close: function(){
			document.getElementById(devolo_cplFrontEnd.mId_checkRateCmds)._jeeDialog.close()
		}
	}
}
devolo_cplFrontEnd.mod_checkRateCmds.init()

// vim: tabstop=2 autoindent
