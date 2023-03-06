<?php
sendVarToJS('eqList', devolo_cpl::getEqListToGraph());
?>

<div class="col-lg-12" id="devolo_cpl_rates">
  <a id="bt_add-graph" class="btn btn-default btn-sm pull-right panel-top">
   <i class="fas fa-plus-circle"></i>
   {{Ajouter un graphique}}
  </a>
</div>

<?php
include_file('desktop', 'panel_rates', 'js', 'devolo_cpl')
?>
