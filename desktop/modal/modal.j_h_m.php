<STYLE>
	.j_h_m-containter {
		max-width: 400px;
		margin-left: auto;
		margin-right:auto;
	}


	.j_h_m-containter input {
		width: 70px !important;
		font-size 12px;
		padding:2px;
	}

	.j_h_m-containter .ui-button-icon.ui-icon {
		margin-top: -8px;
	}

	.j_h_m-field {
		display : inline-block;
	}
</STYLE>
<div class='j_h_m-containter'>
	<div class="col-sm-4 j_h_m-field">
		<div>Jours</div>
		<input id="sliderInputDays" type="number" min="0" max="14" value=0 step="1" style="width:50%">
	</div>
	<div class="col-sm-4 j_h_m-field">
		<div>Heures</div>
		<input id="sliderInputHours" type="number" min="0" max="23" value=0 step="1">
	</div>
	<div class="col-sm-4 j_h_m-field">
		<div>minutes</div>
		<input id="sliderInputMinutes" type="number" min="0" max="59" value=0 step="1">
	</div>
<div>
<SCRIPT>
	$('#sliderInputDays').spinner({
		icons : {down: "ui-icon-triangle-1-s", up: "ui-icon-triangle-1-n"}
	})
	$('#sliderInputHours').spinner({
		icons : {down: "ui-icon-triangle-1-s", up: "ui-icon-triangle-1-n"}
	})
	$('#sliderInputMinutes').spinner({
		icons : {down: "ui-icon-triangle-1-s", up: "ui-icon-triangle-1-n"}
	})
	function j_h_m_init(value) {
		minutes = value % 60
		hours = Math.trunc (value / 60)
		days = Math.trunc (hours / 24)
		hours = hours % 24
		$('#sliderInputDays').value(days)
		$('#sliderInputHours').value(hours)
		$('#sliderInputMinutes').value(minutes)
	}
</SCRIPT>


