/*
 * Compteur pour les identifients uniques
 */
wifi_id = 0;
function get_next_wifi_id() {
  wifi_id++;
  return wifi_id;
}

/*
 * Création d'une carte graphique
 */
function add_wifi_card() {
  card = "<div class=card>";
  card += '<div class="card-header">';
  card += '<div class="col-sm-3">';
  card += '<i class="fas fa-wifi"></i>';
  card += "<h3>{{connections WiFi}}</h3>";
  card += "</div>";
  card += '<div class="col-sm-5">';
  card += '<span class="label">{{Equipement}}:</span>';
  card += '<select class="devolo_wifi_view">';
  card += '<option value="ap">{{AP}}</option>';
  card += '<option value="client">{{client}}</option>';
  card += "</select>";
  card += '<select class="graphview" data-view="ap">';
  for (name in eqList) {
    if (eqList[name]["wifi"] == 1) {
      card += '<option value="' + eqList[name]["serial"] + '">';
      card += name;
      card += "</option>";
    }
  }
  card += "</select>";
  card += '<select class="graphview" data-view="client">';
  for (id in macinfo) {
    card +=
      '<option value="' +
      macinfo[id]["mac"] +
      '">' +
      macinfo[id]["displayname"] +
      "</option>";
  }
  card += "</select>";
  card += '<span class="button ok cursor">{{OK}}</span>';
  card += "</div>";
  card += '<i class="fas fa-times-circle bt-close_card"</i>';
  card += "</div>";
  card +=
    '<div id="WifiGraph_' + get_next_wifi_id() + '" class="card-content">';
  card += "</div>";
  card += "</div>";
  $("#devolo_cpl_wifi").append(card);
  $("#devolo_cpl_wifi div.card")
    .last()
    .find("select.devolo_wifi_view")
    .trigger("change");
}

/*
 * Ajourd'une carte graphique
 */
$("#bt_add-wifi-graph").on("click", function () {
  add_wifi_card();
});

/*
 * changement de vue
 */
$("#devolo_cpl_wifi").on(
  "change",
  "div.card select.devolo_wifi_view",
  function () {
    selected_view = $(this).val();
    $(this)
      .closest(".card-header")
      .find("select.graphview[data-view!=" + selected_view + "]")
      .addClass("hidden");
    $(this)
      .closest(".card-header")
      .find("select.graphview[data-view=" + selected_view + "]")
      .removeClass("hidden");
  },
);

/*
 * Chargement des données sur click du bouton "OK"
 */
$("#devolo_cpl_wifi").on("click", "div.card .button.ok", function () {
  let selected_view = $(this)
    .closest(".card-header")
    .find("select.devolo_wifi_view")
    .val();
  let graphId = $(this).closest(".card").find(".card-content").attr("id");
  let title = $(this)
    .closest(".card")
    .find("select.graphview[data-view=" + selected_view + "] option:selected")
    .text();
  let key = $(this)
    .closest(".card")
    .find("select.graphview[data-view=" + selected_view + "] option:selected")
    .val();
  if (selected_view == "ap") {
    actionAjax = "wifiHistorique_ap";
  } else {
    actionAjax = "wifiHistorique_client";
  }

  $.ajax({
    type: "POST",
    url: "/plugins/devolo_cpl/core/ajax/devolo_cpl.ajax.php",
    data: {
      action: actionAjax,
      key: key,
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
      let categories = [];
      let networks = [];
      for (entry of data.result) {
        if (!categories.includes(entry.category)) {
          categories.push(entry.category);
        }
        if (!networks.includes(entry.network)) {
          networks.push(entry.network);
        }
      }
      categories.sort();
      networks.sort();
      let categoryNr = {};
      for (i = 0; i < categories.length; i++) {
        categoryNr[categories[i]] = i;
      }
      if (selected_view == "client") {
        for (i = 0; i < categories.length; i++) {
          for (eq in eqList) {
            if (eqList[eq].serial == categories[i]) {
              categories[i] = eq;
            }
          }
        }
      }
      let datas = [];
      for (network of networks) {
        datas[network] = [];
      }
      for (entry of data.result) {
        data = {};
        data.x = parseInt(entry.connect_time);
        data.x2 = parseInt(entry.disconnect_time);
        data.y = categoryNr[entry.category];
        datas[entry.network].push(data);
      }
      chart_config = {
        ...chart_defaults,
        ...{
          chart: {
            displayErrors: true,
            type: "xrange",
            zoomType: "x",
            spacingBottom: 5,
            spacingTop: 5,
            spacingRight: 5,
            spacingLeft: 5,
            style: {
              fontFamily: "Roboto",
            },
          },
          title: {
            text: title,
          },
          accessibility: {
            point: {
              descriptionFormatter: function (point) {
                var ix = point.index + 1,
                  category = point.yCategory,
                  from = new Date(point.x),
                  to = new Date(point.x2);
                return (
                  ix +
                  ". " +
                  category +
                  ", " +
                  from.toDateString() +
                  " to " +
                  to.toDateString() +
                  "."
                );
              },
            },
          },
          xAxis: {
            type: "datetime",
          },
          yAxis: {
            title: {
              text: "",
            },
            categories: categories,
            reversed: true,
          },
          series: [],
        },
      };
      for (network of networks) {
        chart_config.series.push({
          name: network,
          borderColor: "gray",
          pointWidth: 10,
          data: datas[network],
          dataLabels: {
            enabled: true,
          },
          showInLegend: true,
        });
      }
      let chart = new Highcharts.chart(graphId, chart_config);
    },
  });
});
add_wifi_card();
$("#devolo_cpl_wifi div.card").find(".button.ok").trigger("click");
