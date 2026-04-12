<div class="j_h_m-container row">
	<div class="col-sm-4 j_h_m-field">
		<div>Jours</div>
		<input id="sliderInputDays" class='ispin' type="number" min="0" max="14" step="1" style="width:50%">
	</div>
	<div class="col-sm-4 j_h_m-field">
		<div>Heures</div>
		<input id="sliderInputHours" class='ispin' type="number" min="0" max="23" step="1">
	</div>
	<div class="col-sm-4 j_h_m-field">
		<div>minutes</div>
		<input id="sliderInputMinutes" class='ispin' type="number" min="0" max="59" step="1">
	</div>
</div>
<div class="row btn-container">
	<a id="set-30m" class="btn btn-sm btn-primary settime" data-time="30">{{30 minutes}}</a>
	<a id="set-1h" class="btn btn-sm btn-primary settime" data-time="60">{{1 heure}}</a>
	<a id="set-2h" class="btn btn-sm btn-primary settime" data-time="120">{{2 heures}}</a>
	<a id="set-3h" class="btn btn-sm btn-primary settime" data-time="180">{{3 heures}}</a>
	<a id="set-6h" class="btn btn-sm btn-primary settime" data-time="360"> {{6 heures}}</a>
	<a id="set-12h" class="btn btn-sm btn-primary settime" data-time="720"> {{12 heures}}</a>
	<a id="set-1j" class="btn btn-sm btn-primary settime" data-time="1440"> {{1 jour}}</a>
	<a id="set-2j" class="btn btn-sm btn-primary settime" data-time="2880"> {{2 jours}}</a>
	<a id="set-7j" class="btn btn-sm btn-primary settime" data-time="10080"> {{7 jours}}</a>
	<a id="set-0" class="btn btn-sm btn-primary settime" data-time="0"> {{Zéro}}</a>
</div>
<script>
	if (typeof devolo_cplActionJHM_modal === 'undefined') {
		devolo_cplActionJHM_modal = {
			init: function() {
				document.getElementById('GuestTimeInput').addEventListener('click',function(event){
					let _target = null

					if (_target = event.target.closest('.settime')) {
						let time = _target.dataset.time
						devolo_cplActionJHM_modal.setValue(time)
					}
				})
			},

			setValue: function(value) {
				let jour = Math.trunc(value / (24*60))
				value = value % (24*60)
				let heure = Math.trunc(value / 60)
				let minute = value % 60
				document.getElementById('GuestTimeInput').querySelector('#sliderInputDays').value = jour
				document.getElementById('GuestTimeInput').querySelector('#sliderInputHours').value = heure
				document.getElementById('GuestTimeInput').querySelector('#sliderInputMinutes').value = minute
			},

			getValue: function() {
				let value = 0
				value += parseInt(document.getElementById('GuestTimeInput').querySelector('#sliderInputDays').value) * 24 * 60
				value += parseInt(document.getElementById('GuestTimeInput').querySelector('#sliderInputHours').value) * 60
				value += parseInt(document.getElementById('GuestTimeInput').querySelector('#sliderInputMinutes').value)
				return value
			}
		}
		devolo_cplActionJHM_modal.init()
	}

</script>
<!--
vim: tabstop=2 autoindent
-->

