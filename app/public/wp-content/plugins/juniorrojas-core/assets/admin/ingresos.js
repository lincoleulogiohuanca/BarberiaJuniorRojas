(function () {
  "use strict";

  function emptyChartMessage(canvas, text) {
    if (!(canvas instanceof HTMLCanvasElement)) {
      return;
    }
    const parent = canvas.parentElement;
    if (!parent || parent.querySelector("[data-jr-empty]")) {
      return;
    }
    canvas.style.display = "none";
    const p = document.createElement("p");
    p.setAttribute("data-jr-empty", "");
    p.style.margin = "12px 0";
    p.style.color = "#646970";
    p.textContent = text || "Sin datos para este filtro.";
    parent.appendChild(p);
  }

  function hasValues(values) {
    return Array.isArray(values) && values.some(function (v) {
      return Number(v) > 0;
    });
  }

  function chartTheme() {
    return {
      gold: "#d4b45a",
      goldSoft: "#e4c872",
      bar: "#c8a24a",
      barAlt: "#1c1b1b",
      palette: ["#c8a24a", "#2a2a2a", "#8f6f2e", "#4a4a4a", "#eac166", "#6b5a3a"],
      tick: "#646970",
      grid: "rgba(0,0,0,0.08)",
      legend: "#1d2327",
      fill: "rgba(200, 162, 74, 0.15)",
    };
  }

  function scaleOpts(theme, horizontal) {
    var axes = horizontal ? ["x"] : ["y"];
    var base = {
      beginAtZero: true,
      ticks: {
        maxTicksLimit: 5,
        color: theme.tick,
        callback: function (value) {
          return "S/. " + value;
        },
      },
      grid: {
        color: theme.grid,
        drawBorder: false,
      },
    };
    var out = {};
    axes.forEach(function (axis) {
      out[axis] = base;
    });
    var cat = horizontal ? "y" : "x";
    out[cat] = {
      ticks: {
        color: theme.tick,
        maxRotation: 0,
        autoSkip: true,
        maxTicksLimit: 10,
      },
      grid: {
        color: theme.grid,
        drawBorder: false,
      },
    };
    return out;
  }

  var baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 280 },
  };

  function init() {
    var root = document.querySelector("[data-jr-ingresos-charts]");
    if (!(root instanceof HTMLElement) || typeof Chart === "undefined") {
      return;
    }

    var theme = chartTheme();
    var payload =
      window.yuniorrojasIngresos && typeof window.yuniorrojasIngresos === "object"
        ? window.yuniorrojasIngresos
        : {};
    var emptyText =
      (payload.i18n && payload.i18n.vacio) || "Sin datos para este filtro.";

    var serie = payload.serie || {};
    var metodos = payload.metodos || {};
    var barberos = payload.barberos || {};
    var servicios = payload.servicios || {};

    var serieCanvas = document.getElementById("jr-ingresos-serie");
    if (serieCanvas instanceof HTMLCanvasElement) {
      if (!hasValues(serie.values)) {
        emptyChartMessage(serieCanvas, emptyText);
      } else {
        new Chart(serieCanvas, {
          type: "line",
          data: {
            labels: serie.labels || [],
            datasets: [
              {
                label: (payload.i18n && payload.i18n.ingresos) || "Ingresos",
                data: serie.values || [],
                borderColor: theme.gold,
                backgroundColor: theme.fill,
                fill: true,
                tension: 0.3,
                pointRadius: 2,
                pointBackgroundColor: theme.goldSoft,
              },
            ],
          },
          options: Object.assign({}, baseOptions, {
            plugins: { legend: { display: false } },
            scales: scaleOpts(theme, false),
          }),
        });
      }
    }

    var metodosCanvas = document.getElementById("jr-ingresos-metodos");
    if (metodosCanvas instanceof HTMLCanvasElement) {
      if (!hasValues(metodos.values)) {
        emptyChartMessage(metodosCanvas, emptyText);
      } else {
        new Chart(metodosCanvas, {
          type: "doughnut",
          data: {
            labels: metodos.labels || [],
            datasets: [
              {
                data: metodos.values || [],
                backgroundColor: theme.palette.slice(0, (metodos.values || []).length),
                borderWidth: 0,
                borderColor: "#fff",
              },
            ],
          },
          options: Object.assign({}, baseOptions, {
            cutout: "62%",
            plugins: {
              legend: {
                position: "bottom",
                labels: { color: theme.legend, boxWidth: 12, padding: 12 },
              },
            },
          }),
        });
      }
    }

    var barberosCanvas = document.getElementById("jr-ingresos-barberos");
    if (barberosCanvas instanceof HTMLCanvasElement) {
      if (!hasValues(barberos.values)) {
        emptyChartMessage(barberosCanvas, emptyText);
      } else {
        new Chart(barberosCanvas, {
          type: "bar",
          data: {
            labels: barberos.labels || [],
            datasets: [
              {
                label: (payload.i18n && payload.i18n.ingresos) || "Ingresos",
                data: barberos.values || [],
                backgroundColor: theme.bar,
                borderRadius: 2,
              },
            ],
          },
          options: Object.assign({}, baseOptions, {
            plugins: { legend: { display: false } },
            scales: scaleOpts(theme, false),
          }),
        });
      }
    }

    var serviciosCanvas = document.getElementById("jr-ingresos-servicios");
    if (serviciosCanvas instanceof HTMLCanvasElement) {
      if (!hasValues(servicios.values)) {
        emptyChartMessage(serviciosCanvas, emptyText);
      } else {
        new Chart(serviciosCanvas, {
          type: "bar",
          data: {
            labels: servicios.labels || [],
            datasets: [
              {
                label: (payload.i18n && payload.i18n.ingresos) || "Ingresos",
                data: servicios.values || [],
                backgroundColor: theme.barAlt,
                borderRadius: 2,
              },
            ],
          },
          options: Object.assign({}, baseOptions, {
            indexAxis: "y",
            plugins: { legend: { display: false } },
            scales: scaleOpts(theme, true),
          }),
        });
      }
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
