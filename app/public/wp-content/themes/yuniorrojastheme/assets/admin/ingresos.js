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

  const baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 280 },
  };

  function init() {
    const root = document.querySelector("[data-jr-ingresos-charts]");
    if (!(root instanceof HTMLElement) || typeof Chart === "undefined") {
      return;
    }

    const payload =
      window.yuniorrojasIngresos && typeof window.yuniorrojasIngresos === "object"
        ? window.yuniorrojasIngresos
        : {};
    const emptyText =
      (payload.i18n && payload.i18n.vacio) || "Sin datos para este filtro.";

    const gold = "#c8a24a";
    const goldSoft = "#eac166";
    const dark = "#1c1b1b";
    const greys = ["#c8a24a", "#2a2a2a", "#8f6f2e", "#4a4a4a", "#eac166", "#6b5a3a"];

    const serie = payload.serie || {};
    const metodos = payload.metodos || {};
    const barberos = payload.barberos || {};
    const servicios = payload.servicios || {};

    const serieCanvas = document.getElementById("jr-ingresos-serie");
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
                borderColor: gold,
                backgroundColor: "rgba(200, 162, 74, 0.15)",
                fill: true,
                tension: 0.3,
                pointRadius: 2,
                pointBackgroundColor: goldSoft,
              },
            ],
          },
          options: Object.assign({}, baseOptions, {
            plugins: { legend: { display: false } },
            scales: {
              x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
              y: {
                beginAtZero: true,
                ticks: {
                  maxTicksLimit: 5,
                  callback: function (value) {
                    return "S/. " + value;
                  },
                },
              },
            },
          }),
        });
      }
    }

    const metodosCanvas = document.getElementById("jr-ingresos-metodos");
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
                backgroundColor: greys.slice(0, (metodos.values || []).length),
                borderWidth: 0,
              },
            ],
          },
          options: Object.assign({}, baseOptions, {
            cutout: "62%",
            plugins: {
              legend: {
                position: "right",
                labels: { boxWidth: 12, padding: 10, font: { size: 11 } },
              },
            },
          }),
        });
      }
    }

    const barberosCanvas = document.getElementById("jr-ingresos-barberos");
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
                backgroundColor: gold,
              },
            ],
          },
          options: Object.assign({}, baseOptions, {
            plugins: { legend: { display: false } },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  maxTicksLimit: 5,
                  callback: function (value) {
                    return "S/. " + value;
                  },
                },
              },
            },
          }),
        });
      }
    }

    const serviciosCanvas = document.getElementById("jr-ingresos-servicios");
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
                label: (payload.i18n && payload.i18n.servicios) || "Servicios",
                data: servicios.values || [],
                backgroundColor: dark,
              },
            ],
          },
          options: Object.assign({}, baseOptions, {
            indexAxis: "y",
            plugins: { legend: { display: false } },
            scales: {
              x: {
                beginAtZero: true,
                ticks: {
                  maxTicksLimit: 5,
                  callback: function (value) {
                    return "S/. " + value;
                  },
                },
              },
            },
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
