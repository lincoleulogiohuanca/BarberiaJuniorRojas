(function () {
  "use strict";

  function isDarkAdmin() {
    return document.body && document.body.classList.contains("jr-admin-theme-dark");
  }

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
    p.style.color = isDarkAdmin() ? "#9aa3af" : "#646970";
    p.textContent = text || "Sin datos para este filtro.";
    parent.appendChild(p);
  }

  function hasValues(values) {
    return Array.isArray(values) && values.some(function (v) {
      return Number(v) > 0;
    });
  }

  function chartTheme() {
    var dark = isDarkAdmin();
    return {
      gold: "#d4b45a",
      goldSoft: "#e4c872",
      bar: dark ? "#d4b45a" : "#c8a24a",
      barAlt: dark ? "#e4c872" : "#1c1b1b",
      palette: dark
        ? ["#d4b45a", "#7ec0ff", "#4ade96", "#f0c14d", "#c084fc", "#fb923c", "#94a3b8"]
        : ["#c8a24a", "#2a2a2a", "#8f6f2e", "#4a4a4a", "#eac166", "#6b5a3a"],
      tick: dark ? "#9aa3af" : "#646970",
      grid: dark ? "rgba(255,255,255,0.08)" : "rgba(0,0,0,0.08)",
      legend: dark ? "#c9ced6" : "#1d2327",
      fill: dark ? "rgba(212, 180, 90, 0.18)" : "rgba(200, 162, 74, 0.15)",
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
    // La otra categoría (labels)
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
                borderWidth: isDarkAdmin() ? 2 : 0,
                borderColor: isDarkAdmin() ? "#1c2129" : "#fff",
              },
            ],
          },
          options: Object.assign({}, baseOptions, {
            cutout: "62%",
            plugins: {
              legend: {
                position: "right",
                labels: {
                  boxWidth: 12,
                  padding: 10,
                  font: { size: 11 },
                  color: theme.legend,
                },
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
                label: (payload.i18n && payload.i18n.barberos) || "Barberos",
                data: barberos.values || [],
                backgroundColor: theme.bar,
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
        var n = (servicios.values || []).length;
        var barColors = [];
        var i;
        for (i = 0; i < n; i++) {
          barColors.push(theme.palette[i % theme.palette.length]);
        }
        new Chart(serviciosCanvas, {
          type: "bar",
          data: {
            labels: servicios.labels || [],
            datasets: [
              {
                label: (payload.i18n && payload.i18n.servicios) || "Servicios",
                data: servicios.values || [],
                backgroundColor: isDarkAdmin() ? barColors : theme.barAlt,
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
