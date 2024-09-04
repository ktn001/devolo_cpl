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

/* Permet la réorganisation des commandes dans l'équipement */
$("#table_cmd").sortable({
  axis: "y",
  cursor: "move",
  items: ".cmd",
  placeholder: "ui-state-highlight",
  tolerance: "intersect",
  forcePlaceholderSize: true,
});

/* Bouton de synchronisation des équipements */
$("#bt_syncDevolo").on("click", function () {
  $.ajax({
    type: "POST",
    url: "/plugins/devolo_cpl/core/ajax/devolo_cpl.ajax.php",
    data: {
      action: "syncDevolo",
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
      jeedomUtils.loadPage(window.location.href);
    },
  });
});

/* Sauvegarde avec validation préliminaire */
$(".eqLogicAction[data-action=save_devolo")
  .off("click")
  .on("click", function () {
    var manageable = $(
      ".eqLogicAttr[data-l1key=configuration][data-l2key=model]",
    )
      .find("option:selected")
      .attr("manageable");
    if (manageable != 1) {
      var cmdToRemove = [];
      $(".cmd.manageable-only").each(function () {
        var logicalId = $(this).find(".cmdAttr[data-l1key=logicalId]").value();
        cmdToRemove.push(logicalId);
      });
      if (cmdToRemove.length > 0) {
        var message =
          "{{Le changement de modèle implique la suppression des commandes suivantes:}}";
        message += "<ul>";
        for (let i = 0; i < cmdToRemove.length; i++) {
          message += "<li>" + cmdToRemove[i] + "</li>";
        }
        message += "<ul>";
        bootbox.confirm(message, function (result) {
          if (!result) {
            return;
          }
          $(".cmd.manageable-only").remove();
          $(".eqLogicAction[data-action=save").trigger("click");
        });
      } else {
        $(".eqLogicAction[data-action=save").trigger("click");
      }
    } else {
      $(".eqLogicAction[data-action=save").trigger("click");
    }
  });

/* Affichage du réseau */
$("#bt_devoloNetwork")
  .off("click")
  .on("click", function () {
    $("#md_modal")
      .dialog({ title: "{{Réseaux CPL}}" })
      .load("index.php?v=d&plugin=devolo_cpl&modal=network")
      .dialog("open");
  });

/* Affichage des adresses mac */
$("#bt_devoloMacInfo")
  .off("click")
  .on("click", function () {
    $("#md_modal2")
      .dialog({
        title: "{{Mac adresses}}",
        classes: {
          "ui-dialog": "no-close",
        },
        beforeClose: function (event, ui) {
          return macinfo_canClose();
        },
      })
      .load("index.php?v=d&plugin=devolo_cpl&modal=macinfo")
      .dialog("open");
  });

/* Mise à jour de l'image lors du changement de modèle */
$(".eqLogicAttr[data-l1key=configuration][data-l2key=model]").on(
  "change",
  function () {
    var img = $(this).find("option:selected").attr("img");
    var val = $(this).find("option:selected").attr("value");
    var manageable = $(this).find("option:selected").attr("manageable");
    if (manageable == 1) {
      $(".manageable-only").removeClass("hidden");
    } else {
      $(".manageable-only").addClass("hidden");
    }
    $("#img_equipement").attr("src", img);
    $("#code_equipement").html(val);
  },
);

/* Fonction permettant l'affichage des commandes dans l'équipement */
function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = { configuration: {} };
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {};
  }
  manageable = "";
  if (isset(_cmd.logicalId)) {
    if (isset(cmdsDef[_cmd.logicalId])) {
      if (isset(cmdsDef[_cmd.logicalId]["manageableOnly"])) {
        if (cmdsDef[_cmd.logicalId]["manageableOnly"] == 1) {
          manageable = " manageable-only";
        }
      }
    }
  }
  var tr =
    '<tr class="cmd' + manageable + '" data-cmd_id="' + init(_cmd.id) + '">';
  tr += '<td class="hidden-xs">';
  tr += '<span class="cmdAttr" data-l1key="id"></span>';
  tr += "</td>";
  tr += "<td>";
  tr += '<div class="input-group">';
  tr +=
    '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">';
  tr +=
    '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>';
  tr +=
    '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>';
  tr += "</div>";
  tr +=
    '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande info liée}}">';
  tr += '<option value="">{{Aucune}}</option>';
  tr += "</select>";
  tr += "</td>";
  tr += "<td>";
  tr += '<span class="cmdAttr" data-l1key="logicalId"></span>';
  tr += "</td>";
  tr += "<td>";
  tr +=
    '<span class="type" type="' +
    init(_cmd.type) +
    '">' +
    jeedom.cmd.availableType() +
    "</span>";
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
  tr += "</td>";
  tr += "<td>";
  tr +=
    '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> ';
  tr +=
    '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> ';
  tr +=
    '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> ';
  tr += '<div style="margin-top:7px;">';
  tr +=
    '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
  tr +=
    '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
  tr +=
    '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
  tr += "</div>";
  tr += "</td>";
  tr += "<td>";
  tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
  tr += "</td>";
  tr += "<td>";
  if (is_numeric(_cmd.id)) {
    tr +=
      '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
    tr +=
      '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>';
  }
  tr +=
    '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i></td>';
  tr += "</tr>";
  $("#table_cmd tbody").append(tr);
  var tr = $("#table_cmd tbody tr").last();
  jeedom.eqLogic.buildSelectCmd({
    id: $(".eqLogicAttr[data-l1key=id]").value(),
    filter: { type: "info" },
    error: function (error) {
      $("#div_alert").showAlert({ message: error.message, level: "danger" });
    },
    success: function (result) {
      tr.find(".cmdAttr[data-l1key=value]").append(result);
      tr.setValues(_cmd, ".cmdAttr");
      jeedom.cmd.changeType(tr, init(_cmd.subType));
      $(".eqLogicAttr[data-l1key=configuration][data-l2key=model]").trigger(
        "change",
      );
    },
  });
}
