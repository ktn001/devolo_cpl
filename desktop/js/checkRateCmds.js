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
					if (count(result) == 0) {
						let div = document.createElement('div').addClass('rateCmdsOk')
						div.innerText='{{Toutes les commandes de débits sont OK}}'
						resultContent.append(div)
					} else {
						console.log(result)
						for (let eqName of Object.keys(result)) {
							let eqDiv = document.createElement('div').addClass('equipement')
							eqDiv.innerText = eqName + ":"
							resultContent.append(eqDiv)

							if ('upToRemove' in result[eqName]) {
								let entries = result[eqName]['upToRemove']
								let txt = '{{Les commandes suivantes devraient être supprimées}}'
								if (count(entries) == 1) {
									txt = '{{La commande suivante devrait être supprimée}}'
								}
								txt += '<sup><i class="fas fa-question-circle" title="L\'option <b>Flux montant</b> n\'est pas activée dans la config du plugin"></i></sup>'
								txt += '<ul></ul>'
								let upToRemoveTitle = document.createElement('div').addClass('errorTypeTitle')
								upToRemoveTitle.innerHTML = txt
								resultContent.append(upToRemoveTitle)
								let upToRemoveDiv = document.createElement('div').addClass('upToRemove')
								upToRemoveDiv.innerText = 'upToRemove'
								resultContent.append(upToRemoveDiv)
							}

							if ('downToRemove' in result[eqName]) {
								let downToRemoveDiv = document.createElement('div').addClass('downToRemove')
								downToRemoveDiv.innerText = 'downToRemove'
								resultContent.append(downToRemoveDiv)
							}

							if ('noTarget' in result[eqName]) {
								let noTargetDiv = document.createElement('div').addClass('noTarget')
								noTargetDiv.innerText = 'noTarget'
								resultContent.append(noTargetDiv)
							}

							if ('wrongType' in result[eqName]) {
								let wrongTypeDiv = document.createElement('div').addClass('wrongType')
								wrongTypeDiv.innerText = 'wrongType'
								resultContent.append(wrongTypeDiv)
							}

							if ('wrongNetwork' in result[eqName]) {
								let wrongNetworkDiv = document.createElement('div').addClass('wrongNetwork')
								wrongNetworkDiv.innerText = 'wrongNetwork'
								resultContent.append(wrongNetworkDiv)
							}
						}
						console.log(resultContent)
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
