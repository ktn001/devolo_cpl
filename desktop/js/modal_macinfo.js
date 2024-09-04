$("#table_macinfo tbody").on("input", ".macinfoAttr", function () {
  macinfoChanged = true;
});

function macinfo_canClose() {
  if (!macinfoChanged) {
    return true;
  }
  return confirm(
    "{{Attention vous quittez une page ayant des données modifiées non sauvegardées. Voulez-vous continuer ?}}",
  );
}

$("#table_macinfo tbody").on(
  "click",
  ".macinfoAction[data-action=remove]",
  function () {
    $(this).closest("tr").remove();
    macinfoChanged = true;
  },
);

$(".macinfoAction[data-action=close]").on("click", function () {
  $(this).closest("[id^=md_modal]").dialog("close");
});

$(".macinfoAction[data-action=cancel]").on("click", function () {
  macinfoChanged = false;
  $(this).closest("[id^=md_modal]").dialog("close");
});

$(".macinfoAction[data-action=save]").on("click", function () {
  if (!macinfoChanged) {
    $.fn.showAlert({
      message: "{{Pas de modification à sauvegarder}}",
      level: "success",
    });
    return;
  }
  macInfos = $("#table_macinfo .macinfo").getValues(".macinfoAttr");
  $.ajax({
    type: "POST",
    url: "/plugins/devolo_cpl/core/ajax/devolo_macinfo.ajax.php",
    data: {
      action: "save",
      macinfos: json_encode(macInfos),
    },
    dataType: "json",
    error: function (request, status, error) {
      handleAjaxError(request, status, error);
    },
    success: function (data) {
      if (data.state != "ok") {
        $.fn.showAlert({ message: data.result, level: "danger" });
        return;
      }
      $.fn.showAlert({
        message: "{{Sauvegarde effectuée avec succès}}",
        level: "success",
      });
      $("#table_macinfo tbody tr").remove();
      for (macinfo of json_decode(data.result)) {
        addMacinfoToTable(macinfo);
      }
      macinfoChanged = false;
    },
  });
});

function loadAll() {
  $.ajax({
    type: "POST",
    url: "/plugins/devolo_cpl/core/ajax/devolo_macinfo.ajax.php",
    data: {
      action: "getAll",
    },
    dataType: "json",
    error: function (request, status, error) {
      handleAjaxError(request, status, error);
    },
    success: function (data) {
      if (data.state != "ok") {
        $.fn.showAlert({ message: data.result, level: "danger" });
        return;
      }
      $("#table_macinfo tbody tr").remove();
      for (macinfo of json_decode(data.result)) {
        addMacinfoToTable(macinfo);
      }
    },
  });
}

function addMacinfoToTable(_macinfo) {
  tr = '<tr class="macinfo" data-macinfo_id="' + init(_macinfo.id) + '">';
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
  $("#table_macinfo tbody").append(tr);
  tr = $("#table_macinfo tbody tr").last();
  tr.setValues(_macinfo, ".macinfoAttr");
}

loadAll();
macinfoChanged = false;
