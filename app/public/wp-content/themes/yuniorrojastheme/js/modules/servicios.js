/** Auto-split from main.js — servicios */
(function () {
  function yuniorrojasInit_servicios() {
    // Servicios: filtros + paginación (4 por página)
    const serviciosFilterRoot = document.querySelector("[data-servicios-filters]");
    const serviciosRoot = document.querySelector("[data-servicios-root]");
    
    if (serviciosFilterRoot && serviciosRoot && window.yuniorrojasTheme && yuniorrojasTheme.restServicios) {
      let serviciosRequestId = 0;
      let serviciosState = {
        filter: "*",
        group: "tag",
        page: 1,
      };
    
      const loadServicios = async () => {
        const current = ++serviciosRequestId;
        serviciosRoot.classList.add("is-loading");
    
        const params = new URLSearchParams();
        params.set("page", String(serviciosState.page));
    
        if (serviciosState.filter && serviciosState.filter !== "*") {
          if (serviciosState.group === "tag") {
            params.set("etiqueta", serviciosState.filter);
          } else {
            params.set("categoria", serviciosState.filter);
          }
        }
    
        const url = `${yuniorrojasTheme.restServicios}?${params.toString()}`;
    
        try {
          const response = await fetch(url, {
            headers: {
              Accept: "application/json",
              "X-WP-Nonce": yuniorrojasTheme.restNonce || "",
            },
          });
    
          if (!response.ok) {
            throw new Error("No se pudo cargar los servicios");
          }
    
          const data = await response.json();
          if (current !== serviciosRequestId) {
            return;
          }
    
          serviciosRoot.innerHTML = data.html || "";
          serviciosState.page = data.page || serviciosState.page;
        } catch (error) {
          if (current === serviciosRequestId) {
            serviciosRoot.innerHTML =
              '<p class="servicios-grid__empty">No se pudo cargar los servicios.</p>';
          }
        } finally {
          if (current === serviciosRequestId) {
            serviciosRoot.classList.remove("is-loading");
          }
        }
      };
    
      serviciosFilterRoot.addEventListener("click", (event) => {
        const button = event.target.closest("[data-filter]");
        if (!button) {
          return;
        }
    
        serviciosState.filter = button.getAttribute("data-filter") || "*";
        serviciosState.group = button.classList.contains("servicios-filters__tag")
          ? "tag"
          : "cat";
        serviciosState.page = 1;
    
        serviciosFilterRoot.querySelectorAll("[data-filter]").forEach((el) => {
          el.classList.toggle("is-active", el === button);
        });
    
        loadServicios();
      });
    
      serviciosRoot.addEventListener("click", (event) => {
        const pageBtn = event.target.closest("[data-servicios-page]");
        if (!pageBtn || pageBtn.disabled) {
          return;
        }
    
        const nextPage = Number(pageBtn.getAttribute("data-servicios-page") || "1");
        if (!Number.isFinite(nextPage) || nextPage < 1 || nextPage === serviciosState.page) {
          return;
        }
    
        serviciosState.page = nextPage;
        loadServicios();
        serviciosRoot.scrollIntoView({ behavior: "smooth", block: "start" });
      });
    }
    
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', yuniorrojasInit_servicios);
  } else {
    yuniorrojasInit_servicios();
  }
})();
