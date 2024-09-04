$(".mainnav li:first-child").addClass("active");

function hideall() {
  $("#div_devolo_cpl > div").hide();
  $(".mainnav li").removeClass("active");
}

$("#div_devolo_cpl").on("click", ".bt-close_card", function () {
  $(this).closest(".card").remove();
});

$(".mainnav li[data-panel]").on("click", function () {
  hideall();
  panelId = $(this).data("panel");
  $(this).addClass("active");
  $("#" + panelId).show();
});

$(".mainnav li[data-panel=devolo_cpl_rates]").trigger("click");

chart_defaults = {
  credits: {
    enabled: false,
  },
  exporting: {
    libURL: "3rdparty/highstock/lib/",
  },
  lang: {
    downloadCSV: "{{Téléchargement CSV}}",
    downloadJPEG: "{{Téléchargement JPEG}}",
    downloadPDF: "{{Téléchargement PDF}}",
    downloadPNG: "{{Téléchargement PNG}}",
    downloadSVG: "{{Téléchargement SVG}}",
    downloadXLS: "{{Téléchargement XLS}}",
    printChart: "{{Imprimer}}",
    viewFullscreen: "{{Plein écran}}",
  },
  legend: {
    enabled: true,
    align: "left",
  },
  rangeSelector: {
    buttonTheme: {
      width: "auto",
      padding: 4,
    },
    buttons: [
      {
        type: "all",
        count: 1,
        text: "{{Tous}}",
      },
      {
        type: "hour",
        count: 1,
        text: "{{Heure}}",
      },
      {
        type: "day",
        count: 1,
        text: "{{Jour}}",
      },
      {
        type: "week",
        count: 1,
        text: "{{Semaine}}",
      },
      {
        type: "month",
        count: 1,
        text: "{{Mois}}",
      },
    ],
    selected: 2,
    inputEnabled: false,
    x: 0,
    y: 0,
  },
  xAxis: {
    type: "datetime",
  },
};
