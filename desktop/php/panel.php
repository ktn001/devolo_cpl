<?php
if (!isConnect()) {
	throw new Excption('{{401 - Accès non autorisé}}');
}

include_file('desktop', 'panel', 'css', 'devolo_cpl');
?>

<div class="subnavbar">
	<div class="subnavbar-inner">
		<div class="container">
			<ul class="mainnav">
				<li class="cursor" data-panel="devolo_cpl_rates">
					<i class="fas fa-chart-line"></i>
					<span>{{Débits CPL}}</span>
				</li>
			</ul>	
		</div>
	</div>
</div>
<div class="row row-overflow" id="div_devolo_cpl">
	<?php
	include_file('desktop','panel_rates', 'php', 'devolo_cpl');
	?>
</div>

<?php
include_file('desktop', 'panel', 'js', 'devolo_cpl')
?>
