/** Auto-split from main.js — reservar */
(function () {
  function yuniorrojasInit_reservar() {
    // Reserva: pasos experiencia → cita (UI)
    const reservaRoot = document.querySelector("[data-reserva]");
    
    if (reservaRoot instanceof HTMLElement) {
      const vistaExperiencia = reservaRoot.querySelector('[data-reserva-vista="experiencia"]');
      const vistaCita = reservaRoot.querySelector('[data-reserva-vista="cita"]');
      const vistaDatos = reservaRoot.querySelector('[data-reserva-vista="datos"]');
      const vistaCheckout = reservaRoot.querySelector('[data-reserva-vista="checkout"]');
      const servicioEls = Array.from(reservaRoot.querySelectorAll("[data-reserva-servicio]"));
      const barberoEls = Array.from(reservaRoot.querySelectorAll("[data-reserva-barbero]"));
      const summaryServicio = reservaRoot.querySelector("[data-summary-servicio]");
      const summaryPrecio = reservaRoot.querySelector("[data-summary-precio]");
      const summaryBarbero = reservaRoot.querySelector("[data-summary-barbero]");
      const summaryTotal = reservaRoot.querySelector("[data-summary-total]");
      const continuarBtn = reservaRoot.querySelector("[data-reserva-continuar]");
      const volverBtn = reservaRoot.querySelector("[data-reserva-volver]");
      const volverHorarioBtn = reservaRoot.querySelector("[data-reserva-volver-horario]");
      const volverDatosBtn = reservaRoot.querySelector("[data-reserva-volver-datos]");
      const datosBtn = reservaRoot.querySelector("[data-reserva-datos]");
      const irCheckoutBtn = reservaRoot.querySelector("[data-reserva-ir-checkout]");
      const procederPagoBtn = reservaRoot.querySelector("[data-reserva-proceder-pago]");
      const pagoModal = reservaRoot.querySelector("[data-reserva-pago-modal]");
      const errorModal = reservaRoot.querySelector("[data-reserva-error-modal]");
      const confirmadaModal = reservaRoot.querySelector("[data-reserva-confirmada-modal]");
      const errorServicio = reservaRoot.querySelector("[data-error-servicio]");
      const errorBarbero = reservaRoot.querySelector("[data-error-barbero]");
      const errorTotal = reservaRoot.querySelector("[data-error-total]");
      const errorReintentarBtn = reservaRoot.querySelector("[data-error-reintentar]");
      const errorCambiarMetodoBtn = reservaRoot.querySelector("[data-error-cambiar-metodo]");
      const pagoMetodosRoot = reservaRoot.querySelector("[data-pago-metodos]") || reservaRoot.querySelector(".reservar-checkout__metodos");
      const datosForm = reservaRoot.querySelector("[data-reserva-form]");
      const campoNombres = reservaRoot.querySelector('[data-reserva-campo="nombres"]');
      const campoApellidos = reservaRoot.querySelector('[data-reserva-campo="apellidos"]');
      const campoTelefono = reservaRoot.querySelector('[data-reserva-campo="telefono"]');
      const campoEmail = reservaRoot.querySelector('[data-reserva-campo="email"]');
      const campoNotas = reservaRoot.querySelector('[data-reserva-campo="notas"]');
      const pagoMetodos = Array.from(reservaRoot.querySelectorAll("[data-pago-metodo]"));
      const pagoPanels = Array.from(reservaRoot.querySelectorAll("[data-pago-panel]"));
    
      const citaServicio = reservaRoot.querySelector("[data-cita-servicio]");
      const citaPrecio = reservaRoot.querySelector("[data-cita-precio]");
      const citaBarbero = reservaRoot.querySelector("[data-cita-barbero]");
      const citaCargo = reservaRoot.querySelector("[data-cita-cargo]");
      const citaAvatar = reservaRoot.querySelector("[data-cita-avatar]");
      const citaFecha = reservaRoot.querySelector("[data-cita-fecha]");
      const citaHora = reservaRoot.querySelector("[data-cita-hora]");
      const citaSubtotal = reservaRoot.querySelector("[data-cita-subtotal]");
      const citaTotal = reservaRoot.querySelector("[data-cita-total]");
    
      const datosServicio = reservaRoot.querySelector("[data-datos-servicio]");
      const datosDuracion = reservaRoot.querySelector("[data-datos-duracion]");
      const datosBarbero = reservaRoot.querySelector("[data-datos-barbero]");
      const datosFecha = reservaRoot.querySelector("[data-datos-fecha]");
      const datosHora = reservaRoot.querySelector("[data-datos-hora]");
      const datosTotal = reservaRoot.querySelector("[data-datos-total]");
      const datosCliente = reservaRoot.querySelector("[data-datos-cliente]");
      const datosTelefono = reservaRoot.querySelector("[data-datos-telefono]");
      const datosEmail = reservaRoot.querySelector("[data-datos-email]");
      const datosNotas = reservaRoot.querySelector("[data-datos-notas]");
      const datosNotasItem = reservaRoot.querySelector("[data-datos-notas-item]");
    
      const checkoutServicio = reservaRoot.querySelector("[data-checkout-servicio]");
      const checkoutPrecio = reservaRoot.querySelector("[data-checkout-precio]");
      const checkoutMeta = reservaRoot.querySelector("[data-checkout-meta]");
      const checkoutAvatar = reservaRoot.querySelector("[data-checkout-avatar]");
      const checkoutBarbero = reservaRoot.querySelector("[data-checkout-barbero]");
      const checkoutFecha = reservaRoot.querySelector("[data-checkout-fecha]");
      const checkoutHora = reservaRoot.querySelector("[data-checkout-hora]");
      const checkoutCliente = reservaRoot.querySelector("[data-checkout-cliente]");
      const checkoutTelefono = reservaRoot.querySelector("[data-checkout-telefono]");
      const checkoutEmail = reservaRoot.querySelector("[data-checkout-email]");
      const checkoutSubtotal = reservaRoot.querySelector("[data-checkout-subtotal]");
      const checkoutTotal = reservaRoot.querySelector("[data-checkout-total]");
      const yapeMonto = reservaRoot.querySelector("[data-yape-monto]");
    
      const confirmCliente = reservaRoot.querySelector("[data-confirm-cliente]");
      const confirmTelefono = reservaRoot.querySelector("[data-confirm-telefono]");
      const confirmServicio = reservaRoot.querySelector("[data-confirm-servicio]");
      const confirmBarbero = reservaRoot.querySelector("[data-confirm-barbero]");
      const confirmFecha = reservaRoot.querySelector("[data-confirm-fecha]");
      const confirmHora = reservaRoot.querySelector("[data-confirm-hora]");
      const confirmPrecio = reservaRoot.querySelector("[data-confirm-precio]");
      const confirmUbicacion = reservaRoot.querySelector("[data-confirm-ubicacion]");
      const voucherRoot = reservaRoot.querySelector("[data-reserva-voucher]");
      const voucherDownloadBtn = reservaRoot.querySelector("[data-voucher-download]");
      const voucherContinuarBtn = reservaRoot.querySelector("[data-voucher-continuar]");
      const shareBtns = Array.from(reservaRoot.querySelectorAll("[data-share]"));
    
      const calMonthEl = reservaRoot.querySelector("[data-cal-month]");
      const calGrid = reservaRoot.querySelector("[data-cal-grid]");
      const calPrev = reservaRoot.querySelector("[data-cal-prev]");
      const calNext = reservaRoot.querySelector("[data-cal-next]");
      const horariosTitle = reservaRoot.querySelector("[data-horarios-title]");
      const slotsManana = reservaRoot.querySelector("[data-slots-manana]");
      const slotsTarde = reservaRoot.querySelector("[data-slots-tarde]");
    
      const MESES = [
        "Enero",
        "Febrero",
        "Marzo",
        "Abril",
        "Mayo",
        "Junio",
        "Julio",
        "Agosto",
        "Septiembre",
        "Octubre",
        "Noviembre",
        "Diciembre",
      ];
      const MESES_CORTO = [
        "Ene",
        "Feb",
        "Mar",
        "Abr",
        "May",
        "Jun",
        "Jul",
        "Ago",
        "Sep",
        "Oct",
        "Nov",
        "Dic",
      ];
      const DIAS_SEMANA = [
        "Domingo",
        "Lunes",
        "Martes",
        "Miércoles",
        "Jueves",
        "Viernes",
        "Sábado",
      ];
    
      const today = new Date();
      today.setHours(0, 0, 0, 0);
    
      const state = {
        viewYear: today.getFullYear(),
        viewMonth: today.getMonth(),
        selectedDate: null,
        selectedTime: "",
        paymentAttempts: 0,
        isSubmittingReserva: false,
        pagoMetodo: "",
        pagoTipo: "",
        pagoMedioId: 0,
        abreCulqi: false,
        requiereCodigo: false,
        esEstudio: false,
        voucherDownloaded: false,
        serviciosRedirectTimer: 0,
      };
    
      const getSelectedProductos = () => {
        /** @type {{id:number,qty:number}[]} */
        const lines = [];
        reservaRoot.querySelectorAll("[data-producto-check]").forEach((el) => {
          if (!(el instanceof HTMLInputElement) || !el.checked) {
            return;
          }
          const id = Number.parseInt(el.getAttribute("data-producto-id") || "0", 10) || 0;
          if (id <= 0) {
            return;
          }
          const qtyInput = reservaRoot.querySelector(
            `[data-producto-qty="${id}"]`
          );
          let qty = 1;
          if (qtyInput instanceof HTMLInputElement) {
            qty = Math.max(1, Math.min(10, Number.parseInt(qtyInput.value || "1", 10) || 1));
          }
          lines.push({ id, qty });
        });
        return lines;
      };
    
      const getProductosTotalCentimos = () => {
        let total = 0;
        reservaRoot.querySelectorAll("[data-producto-check]").forEach((el) => {
          if (!(el instanceof HTMLInputElement) || !el.checked) {
            return;
          }
          const price = Number.parseFloat(el.getAttribute("data-producto-precio") || "0") || 0;
          const id = el.getAttribute("data-producto-id") || "";
          const qtyInput = reservaRoot.querySelector(`[data-producto-qty="${id}"]`);
          let qty = 1;
          if (qtyInput instanceof HTMLInputElement) {
            qty = Math.max(1, Math.min(10, Number.parseInt(qtyInput.value || "1", 10) || 1));
          }
          total += Math.round(price * 100) * qty;
        });
        return total;
      };
    
      const CITA_STORAGE_KEY = "jr_reserva_cita_v1";
    
      const readCitaStorage = () => {
        try {
          const raw = window.sessionStorage.getItem(CITA_STORAGE_KEY);
          if (!raw) {
            return null;
          }
          const parsed = JSON.parse(raw);
          return parsed && typeof parsed === "object" ? parsed : null;
        } catch (error) {
          return null;
        }
      };
    
      const writeCitaStorage = () => {
        try {
          const servicio = getSelectedServicio();
          const barbero = getSelectedBarbero();
          window.sessionStorage.setItem(
            CITA_STORAGE_KEY,
            JSON.stringify({
              fecha: state.selectedDate || "",
              hora: state.selectedTime || "",
              servicio: servicio?.getAttribute("data-id") || "",
              barbero: barbero?.getAttribute("data-id") || "",
              paso: reservaRoot.getAttribute("data-paso") || "",
            })
          );
        } catch (error) {
          // sessionStorage puede fallar en modo privado estricto
        }
      };
    
      const syncCitaUrlParams = () => {
        const url = new URL(window.location.href);
        if (state.selectedDate) {
          url.searchParams.set("fecha", state.selectedDate);
        } else {
          url.searchParams.delete("fecha");
        }
        if (state.selectedTime) {
          url.searchParams.set("hora", state.selectedTime);
        } else {
          url.searchParams.delete("hora");
        }
        window.history.replaceState({}, "", url.toString());
      };
    
      const formatMoney = (raw) => {
        const cleaned = String(raw ?? "")
          .replace(/[^\d.,]/g, "")
          .replace(",", ".");
        const value = Number.parseFloat(cleaned);
        if (!Number.isFinite(value)) {
          return "";
        }
        return `S/. ${value.toFixed(2)}`;
      };
    
      const formatPriceLabel = (raw) => {
        const cleaned = String(raw ?? "").trim();
        if (!cleaned) {
          return "";
        }
        if (/^s\/\./i.test(cleaned)) {
          return cleaned;
        }
        return `S/. ${cleaned}`;
      };
    
      const getSelectedServicio = () =>
        reservaRoot.querySelector("[data-reserva-servicio].is-selected");
      const getSelectedBarbero = () =>
        reservaRoot.querySelector("[data-reserva-barbero].is-selected");
    
      const parseHorarioBarbero = (barbero) => {
        const defaults = {
          intervalo: 30,
          dias: {
            0: { activo: false, inicio: "10:00", fin: "18:00" },
            1: { activo: true, inicio: "10:00", fin: "21:00" },
            2: { activo: true, inicio: "10:00", fin: "21:00" },
            3: { activo: true, inicio: "10:00", fin: "21:00" },
            4: { activo: true, inicio: "10:00", fin: "21:00" },
            5: { activo: true, inicio: "10:00", fin: "21:00" },
            6: { activo: true, inicio: "09:00", fin: "20:00" },
          },
        };
    
        if (!(barbero instanceof HTMLElement)) {
          return defaults;
        }
    
        const raw = barbero.getAttribute("data-horario") || "";
        if (!raw) {
          return defaults;
        }
    
        try {
          const parsed = JSON.parse(raw);
          if (!parsed || typeof parsed !== "object") {
            return defaults;
          }
    
          const intervalo = [15, 30, 45, 60].includes(Number(parsed.intervalo))
            ? Number(parsed.intervalo)
            : 30;
          const dias = { ...defaults.dias };
    
          if (parsed.dias && typeof parsed.dias === "object") {
            Object.keys(defaults.dias).forEach((key) => {
              const item = parsed.dias[key];
              if (!item || typeof item !== "object") {
                return;
              }
              dias[key] = {
                activo: Boolean(item.activo),
                inicio: String(item.inicio || defaults.dias[key].inicio),
                fin: String(item.fin || defaults.dias[key].fin),
              };
            });
          }
    
          return { intervalo, dias };
        } catch (error) {
          return defaults;
        }
      };
    
      const timeToMinutes = (hhmm) => {
        const parts = String(hhmm).split(":");
        const h = Number(parts[0]);
        const m = Number(parts[1]);
        if (!Number.isFinite(h) || !Number.isFinite(m)) {
          return null;
        }
        return h * 60 + m;
      };
    
      const formatSlotLabel = (totalMinutes) => {
        let h = Math.floor(totalMinutes / 60);
        const m = totalMinutes % 60;
        const suffix = h >= 12 ? "PM" : "AM";
        h = h % 12;
        if (h === 0) {
          h = 12;
        }
        return `${h}:${String(m).padStart(2, "0")} ${suffix}`;
      };
    
      const getDiaConfig = (date, horario) => {
        const key = String(date.getDay());
        return horario.dias[key] || { activo: false, inicio: "10:00", fin: "18:00" };
      };
    
      const isBarberoDisponible = (date, horario) => {
        const dia = getDiaConfig(date, horario);
        return Boolean(dia.activo);
      };
    
      const generateSlotsForDate = (date, horario) => {
        const dia = getDiaConfig(date, horario);
        if (!dia.activo) {
          return { manana: [], tarde: [] };
        }
    
        const start = timeToMinutes(dia.inicio);
        const end = timeToMinutes(dia.fin);
        const step = Number(horario.intervalo) || 30;
    
        if (start === null || end === null || end <= start) {
          return { manana: [], tarde: [] };
        }
    
        const manana = [];
        const tarde = [];
        const now = new Date();
        const isToday =
          date.getFullYear() === now.getFullYear() &&
          date.getMonth() === now.getMonth() &&
          date.getDate() === now.getDate();
        const nowMinutes = now.getHours() * 60 + now.getMinutes();
    
        for (let mins = start; mins <= end; mins += step) {
          if (isToday && mins <= nowMinutes + 15) {
            continue;
          }
    
          const slot = { label: formatSlotLabel(mins), disabled: false, hora: null };
          if (mins < 13 * 60) {
            manana.push(slot);
          } else {
            tarde.push(slot);
          }
        }
    
        return { manana, tarde };
      };
    
      const mapApiSlots = (apiSlots) => {
        const mapSide = (list) =>
          (Array.isArray(list) ? list : [])
            .filter((s) => s && s.libre)
            .map((s) => ({
              label: s.label || formatSlotLabel(timeToMinutes(s.hora) || 0),
              disabled: false,
              hora: s.hora || "",
            }));
    
        return {
          manana: mapSide(apiSlots?.manana),
          tarde: mapSide(apiSlots?.tarde),
          libres: Number(apiSlots?.libres) || 0,
        };
      };
    
      const fetchDisponibilidad = async () => {
        const endpoint = window.yuniorrojasTheme?.restDisponibilidad;
        const barbero = getSelectedBarbero();
        const servicio = getSelectedServicio();
        if (!endpoint || !barbero || !state.selectedDate) {
          return null;
        }
    
        const barberoId = Number.parseInt(barbero.getAttribute("data-id") || "0", 10) || 0;
        const servicioId = Number.parseInt(servicio?.getAttribute("data-id") || "0", 10) || 0;
        const duracion = Number.parseInt(servicio?.getAttribute("data-duracion") || "60", 10) || 60;
        const excludeId =
          Number.parseInt(reservaRoot.getAttribute("data-reprogramar-id") || "0", 10) || 0;
    
        const params = new URLSearchParams({
          barbero_id: String(barberoId),
          fecha: state.selectedDate,
          servicio_id: String(servicioId),
          duracion: String(duracion),
          exclude_id: String(excludeId),
        });
    
        const response = await fetch(`${endpoint}?${params.toString()}`, {
          credentials: "same-origin",
          headers: {
            "X-WP-Nonce": window.yuniorrojasTheme?.restNonce || "",
          },
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data?.slots) {
          return null;
        }
        return mapApiSlots(data.slots);
      };
    
      const findNextAvailableDate = (fromDate, horario, maxDays = 60) => {
        const cursor = new Date(fromDate);
        cursor.setHours(0, 0, 0, 0);
    
        for (let i = 0; i < maxDays; i += 1) {
          if (isBarberoDisponible(cursor, horario)) {
            if (i === 0) {
              const slots = generateSlotsForDate(cursor, horario);
              if (slots.manana.length + slots.tarde.length > 0) {
                return new Date(cursor);
              }
            } else {
              return new Date(cursor);
            }
          }
          cursor.setDate(cursor.getDate() + 1);
        }
    
        return null;
      };
    
      const dateKey = (date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, "0");
        const d = String(date.getDate()).padStart(2, "0");
        return `${y}-${m}-${d}`;
      };
    
      const parseDateKey = (key) => {
        const [y, m, d] = key.split("-").map(Number);
        return new Date(y, m - 1, d);
      };
    
      const syncStep1Summary = () => {
        const servicio = getSelectedServicio();
        const barbero = getSelectedBarbero();
    
        const servicioNombre = servicio?.getAttribute("data-nombre") || "—";
        const servicioPrecio = servicio?.getAttribute("data-precio") || "";
        const barberoNombre = barbero?.getAttribute("data-nombre") || "—";
    
        if (summaryServicio instanceof HTMLElement) {
          summaryServicio.textContent = servicioNombre;
        }
        if (summaryPrecio instanceof HTMLElement) {
          summaryPrecio.textContent = formatPriceLabel(servicioPrecio);
        }
        if (summaryBarbero instanceof HTMLElement) {
          summaryBarbero.textContent = barberoNombre;
        }
        if (summaryTotal instanceof HTMLElement) {
          summaryTotal.textContent = formatMoney(servicioPrecio) || "S/. 0.00";
        }
        if (continuarBtn instanceof HTMLButtonElement) {
          continuarBtn.disabled = !(servicio && barbero);
        }
      };
    
      const syncCitaSummary = () => {
        const servicio = getSelectedServicio();
        const barbero = getSelectedBarbero();
        const precioRaw = servicio?.getAttribute("data-precio") || "";
        const money = formatMoney(precioRaw) || "S/. 0.00";
    
        if (citaServicio instanceof HTMLElement) {
          citaServicio.textContent = servicio?.getAttribute("data-nombre") || "—";
        }
        if (citaPrecio instanceof HTMLElement) {
          citaPrecio.textContent = money;
        }
        if (citaBarbero instanceof HTMLElement) {
          citaBarbero.textContent = barbero?.getAttribute("data-nombre") || "—";
        }
        if (citaCargo instanceof HTMLElement) {
          citaCargo.textContent = barbero?.getAttribute("data-cargo") || "";
        }
        if (citaAvatar instanceof HTMLElement) {
          const foto = barbero?.getAttribute("data-foto") || "";
          citaAvatar.style.backgroundImage = foto ? `url("${foto}")` : "";
        }
        if (citaSubtotal instanceof HTMLElement) {
          citaSubtotal.textContent = money;
        }
        if (citaTotal instanceof HTMLElement) {
          citaTotal.textContent = money;
        }
    
        if (citaFecha instanceof HTMLElement) {
          if (state.selectedDate) {
            const date = parseDateKey(state.selectedDate);
            citaFecha.textContent = `${DIAS_SEMANA[date.getDay()]}, ${date.getDate()} ${MESES_CORTO[date.getMonth()]} ${date.getFullYear()}`;
          } else {
            citaFecha.textContent = "—";
          }
        }
    
        if (citaHora instanceof HTMLElement) {
          citaHora.textContent = state.selectedTime || "";
        }
    
        persistCitaSeleccion();
    
        if (datosBtn instanceof HTMLButtonElement) {
          datosBtn.disabled = !(state.selectedDate && state.selectedTime);
        }
      };
    
      const updateHorariosTitle = () => {
        if (!(horariosTitle instanceof HTMLElement)) {
          return;
        }
        if (!state.selectedDate) {
          horariosTitle.textContent = "Horarios disponibles";
          return;
        }
        const date = parseDateKey(state.selectedDate);
        horariosTitle.textContent = `Horarios disponibles para el ${date.getDate()} de ${MESES[date.getMonth()]}`;
      };
    
      const buildSlots = (container, slots) => {
        if (!(container instanceof HTMLElement)) {
          return;
        }
        container.innerHTML = "";
    
        if (!slots.length) {
          return;
        }
    
        slots.forEach((slot) => {
          const btn = document.createElement("button");
          btn.type = "button";
          btn.className = "reservar-horario";
          btn.textContent = slot.label;
          btn.setAttribute("role", "option");
          btn.setAttribute("data-hora", slot.label);
          btn.setAttribute("aria-selected", "false");
    
          if (slot.disabled) {
            btn.disabled = true;
            btn.classList.add("is-disabled");
          }
    
          if (state.selectedTime === slot.label) {
            btn.classList.add("is-selected");
            btn.setAttribute("aria-selected", "true");
          }
    
          btn.addEventListener("click", () => {
            if (btn.disabled) {
              return;
            }
            state.selectedTime = slot.label;
            persistCitaSeleccion();
            reservaRoot.querySelectorAll(".reservar-horario.is-selected").forEach((el) => {
              el.classList.remove("is-selected");
              el.setAttribute("aria-selected", "false");
            });
            btn.classList.add("is-selected");
            btn.setAttribute("aria-selected", "true");
            syncCitaSummary();
          });
    
          container.appendChild(btn);
        });
      };
    
      const renderSlots = async () => {
        const barbero = getSelectedBarbero();
        const horario = parseHorarioBarbero(barbero);
        const date = state.selectedDate ? parseDateKey(state.selectedDate) : null;
        let slots = date
          ? generateSlotsForDate(date, horario)
          : { manana: [], tarde: [], libres: 0 };
    
        const esperaBox = reservaRoot.querySelector("[data-lista-espera]");
        const esperaOk = reservaRoot.querySelector("[data-lista-espera-ok]");
    
        if (date && barbero) {
          try {
            const apiSlots = await fetchDisponibilidad();
            if (apiSlots) {
              slots = apiSlots;
            }
          } catch (error) {
            // Fallback: slots del horario local (sin ocupación).
          }
        }
    
        const allLabels = [...slots.manana, ...slots.tarde].map((s) => s.label);
        if (state.selectedTime && !allLabels.includes(state.selectedTime)) {
          state.selectedTime = "";
          persistCitaSeleccion();
        }
    
        buildSlots(slotsManana, slots.manana);
        buildSlots(slotsTarde, slots.tarde);
    
        const mananaGroup = slotsManana?.closest(".reservar-horarios__group");
        const tardeGroup = slotsTarde?.closest(".reservar-horarios__group");
        if (mananaGroup instanceof HTMLElement) {
          mananaGroup.hidden = slots.manana.length === 0;
        }
        if (tardeGroup instanceof HTMLElement) {
          tardeGroup.hidden = slots.tarde.length === 0;
        }
    
        const horariosRoot = reservaRoot.querySelector("[data-reserva-horarios]");
        let emptyEl = horariosRoot?.querySelector("[data-horarios-empty]");
        const sinLibres = slots.manana.length === 0 && slots.tarde.length === 0;
        if (sinLibres) {
          if (horariosRoot instanceof HTMLElement && !emptyEl) {
            emptyEl = document.createElement("p");
            emptyEl.className = "reservar-horarios__empty";
            emptyEl.setAttribute("data-horarios-empty", "");
            emptyEl.textContent = "No hay horarios disponibles para este día con el barbero seleccionado.";
            horariosRoot.insertBefore(emptyEl, esperaBox || null);
          }
        } else if (emptyEl) {
          emptyEl.remove();
        }
    
        if (esperaBox instanceof HTMLElement) {
          esperaBox.hidden = !sinLibres || !state.selectedDate;
          if (esperaOk instanceof HTMLElement) {
            esperaOk.hidden = true;
          }
        }
    
        updateHorariosTitle();
        syncCitaSummary();
      };
    
      const renderCalendar = () => {
        if (!(calGrid instanceof HTMLElement) || !(calMonthEl instanceof HTMLElement)) {
          return;
        }
    
        const horario = parseHorarioBarbero(getSelectedBarbero());
        calMonthEl.textContent = `${MESES[state.viewMonth]} ${state.viewYear}`;
    
        const firstDay = new Date(state.viewYear, state.viewMonth, 1);
        const startWeekday = firstDay.getDay();
        const daysInMonth = new Date(state.viewYear, state.viewMonth + 1, 0).getDate();
        const prevMonthDays = new Date(state.viewYear, state.viewMonth, 0).getDate();
    
        const canGoPrev =
          state.viewYear > today.getFullYear() ||
          (state.viewYear === today.getFullYear() && state.viewMonth > today.getMonth());
    
        if (calPrev instanceof HTMLButtonElement) {
          calPrev.disabled = !canGoPrev;
        }
    
        calGrid.innerHTML = "";
    
        for (let i = 0; i < 42; i += 1) {
          const btn = document.createElement("button");
          btn.type = "button";
          btn.className = "reservar-calendar__day";
          btn.setAttribute("role", "gridcell");
    
          let dayNum;
          let cellDate;
          let outside = false;
    
          if (i < startWeekday) {
            dayNum = prevMonthDays - startWeekday + i + 1;
            cellDate = new Date(state.viewYear, state.viewMonth - 1, dayNum);
            outside = true;
          } else if (i >= startWeekday + daysInMonth) {
            dayNum = i - (startWeekday + daysInMonth) + 1;
            cellDate = new Date(state.viewYear, state.viewMonth + 1, dayNum);
            outside = true;
          } else {
            dayNum = i - startWeekday + 1;
            cellDate = new Date(state.viewYear, state.viewMonth, dayNum);
          }
    
          btn.textContent = String(dayNum);
          const key = dateKey(cellDate);
          btn.setAttribute("data-date", key);
    
          const isPast = cellDate < today;
          const barberoOff = !isBarberoDisponible(cellDate, horario);
    
          if (outside) {
            btn.classList.add("is-outside");
            btn.disabled = true;
          } else if (isPast || barberoOff) {
            btn.classList.add("is-disabled");
            btn.disabled = true;
          }
    
          if (state.selectedDate === key) {
            btn.classList.add("is-selected");
            btn.setAttribute("aria-selected", "true");
          }
    
          btn.addEventListener("click", () => {
            if (btn.disabled) {
              return;
            }
            state.selectedDate = key;
            state.selectedTime = "";
            persistCitaSeleccion();
            renderCalendar();
            void renderSlots();
            syncCitaSummary();
          });
    
          calGrid.appendChild(btn);
        }
      };
    
      const prepareCitaState = () => {
        const horario = parseHorarioBarbero(getSelectedBarbero());
        const next = findNextAvailableDate(today, horario);
    
        if (next) {
          state.selectedDate = dateKey(next);
          state.viewYear = next.getFullYear();
          state.viewMonth = next.getMonth();
        } else {
          state.selectedDate = null;
          state.viewYear = today.getFullYear();
          state.viewMonth = today.getMonth();
        }
    
        // Solo limpia hora si aún no hay una seleccionada/persistida.
        if (!state.selectedTime && !reservaRoot.getAttribute("data-reserva-hora")) {
          state.selectedTime = "";
        }
        persistCitaSeleccion();
      };
    
      const formatHoraResumen = (label) => {
        // "10:00 AM" → "- 10.00 hrs"
        const match = String(label).match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i);
        if (!match) {
          return label ? `- ${label}` : "";
        }
        let h = Number(match[1]);
        const m = match[2];
        const ampm = match[3].toUpperCase();
        if (ampm === "PM" && h < 12) {
          h += 12;
        }
        if (ampm === "AM" && h === 12) {
          h = 0;
        }
        return `- ${h}.${m} hrs`;
      };
    
      const formatHoraCheckout = (label) => {
        const match = String(label || "").match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i);
        if (!match) {
          return String(label || "").trim();
        }
        let h = Number(match[1]);
        const m = match[2];
        const ampm = match[3].toUpperCase();
        if (ampm === "PM" && h < 12) {
          h += 12;
        }
        if (ampm === "AM" && h === 12) {
          h = 0;
        }
        return `${h}:${m} hrs`;
      };
    
      const persistCitaSeleccion = () => {
        if (state.selectedDate) {
          reservaRoot.setAttribute("data-reserva-fecha", state.selectedDate);
        } else {
          reservaRoot.removeAttribute("data-reserva-fecha");
        }
        if (state.selectedTime) {
          reservaRoot.setAttribute("data-reserva-hora", state.selectedTime);
        } else {
          reservaRoot.removeAttribute("data-reserva-hora");
        }
        writeCitaStorage();
        syncCitaUrlParams();
      };
    
      const persistClienteDatos = () => {
        const nombres = campoNombres instanceof HTMLInputElement ? campoNombres.value.trim() : "";
        const apellidos = campoApellidos instanceof HTMLInputElement ? campoApellidos.value.trim() : "";
        const telefono = campoTelefono instanceof HTMLInputElement ? campoTelefono.value.trim() : "";
        const email = campoEmail instanceof HTMLInputElement ? campoEmail.value.trim() : "";
    
        if (nombres) {
          reservaRoot.setAttribute("data-reserva-nombres", nombres);
        }
        if (apellidos) {
          reservaRoot.setAttribute("data-reserva-apellidos", apellidos);
        }
        if (telefono) {
          reservaRoot.setAttribute("data-reserva-telefono", telefono);
        }
        if (email) {
          reservaRoot.setAttribute("data-reserva-email", email);
        }
      };
    
      const getHoraSeleccionada = () => {
        if (state.selectedTime) {
          return state.selectedTime;
        }
    
        const stored = reservaRoot.getAttribute("data-reserva-hora");
        if (stored) {
          return stored;
        }
    
        const selectedSlot = reservaRoot.querySelector(".reservar-horario.is-selected");
        if (selectedSlot instanceof HTMLElement) {
          return (selectedSlot.getAttribute("data-hora") || selectedSlot.textContent || "").trim();
        }
    
        if (checkoutHora instanceof HTMLElement) {
          const text = checkoutHora.textContent.trim();
          if (text && text !== "—") {
            return text;
          }
        }
        if (datosHora instanceof HTMLElement) {
          const text = datosHora.textContent.trim().replace(/^-\s*/, "");
          if (text && text !== "—") {
            return text;
          }
        }
        if (citaHora instanceof HTMLElement) {
          const text = citaHora.textContent.trim();
          if (text && text !== "—") {
            return text;
          }
        }
    
        return "";
      };
    
      const getFechaSeleccionada = () => {
        if (state.selectedDate) {
          return state.selectedDate;
        }
        return reservaRoot.getAttribute("data-reserva-fecha") || "";
      };
    
      const syncDatosSummary = () => {
        const servicio = getSelectedServicio();
        const barbero = getSelectedBarbero();
        const precioRaw = servicio?.getAttribute("data-precio") || "";
        const money = formatMoney(precioRaw) || "S/. 0.00";
        const duracion = (servicio?.getAttribute("data-duracion") || "").trim();
        const fechaKey = getFechaSeleccionada();
        const horaLabel = getHoraSeleccionada();
    
        if (horaLabel && !state.selectedTime) {
          state.selectedTime = horaLabel;
        }
        if (fechaKey && !state.selectedDate) {
          state.selectedDate = fechaKey;
        }
        persistCitaSeleccion();
    
        if (datosServicio instanceof HTMLElement) {
          datosServicio.textContent = servicio?.getAttribute("data-nombre") || "—";
        }
        if (datosDuracion instanceof HTMLElement) {
          datosDuracion.textContent = duracion ? `${duracion} min` : "";
        }
        if (datosBarbero instanceof HTMLElement) {
          datosBarbero.textContent = barbero?.getAttribute("data-nombre") || "—";
        }
        if (datosTotal instanceof HTMLElement) {
          datosTotal.textContent = money;
        }
    
        if (datosFecha instanceof HTMLElement) {
          if (fechaKey) {
            const date = parseDateKey(fechaKey);
            datosFecha.textContent = `${DIAS_SEMANA[date.getDay()]}, ${date.getDate()} ${MESES_CORTO[date.getMonth()]} ${date.getFullYear()}`;
          } else {
            datosFecha.textContent = "—";
          }
        }
    
        if (datosHora instanceof HTMLElement) {
          datosHora.textContent = formatHoraResumen(horaLabel);
        }
    
        const nombres =
          campoNombres instanceof HTMLInputElement
            ? campoNombres.value.trim()
            : reservaRoot.getAttribute("data-reserva-nombres") || "";
        const apellidos =
          campoApellidos instanceof HTMLInputElement
            ? campoApellidos.value.trim()
            : reservaRoot.getAttribute("data-reserva-apellidos") || "";
        const telefono =
          campoTelefono instanceof HTMLInputElement
            ? campoTelefono.value.trim()
            : reservaRoot.getAttribute("data-reserva-telefono") || "";
        const email =
          campoEmail instanceof HTMLInputElement
            ? campoEmail.value.trim()
            : reservaRoot.getAttribute("data-reserva-email") || "";
        const notas =
          campoNotas instanceof HTMLTextAreaElement ? campoNotas.value.trim() : "";
    
        if (datosCliente instanceof HTMLElement) {
          const fullName = [nombres, apellidos].filter(Boolean).join(" ").trim();
          datosCliente.textContent = fullName || "—";
        }
        if (datosTelefono instanceof HTMLElement) {
          datosTelefono.textContent = telefono || "—";
        }
        if (datosEmail instanceof HTMLElement) {
          datosEmail.textContent = email || "—";
        }
        if (datosNotas instanceof HTMLElement && datosNotasItem instanceof HTMLElement) {
          if (notas) {
            datosNotas.textContent = notas;
            datosNotasItem.hidden = false;
          } else {
            datosNotas.textContent = "";
            datosNotasItem.hidden = true;
          }
        }
      };
    
      const syncCheckoutSummary = () => {
        const servicio = getSelectedServicio();
        const barbero = getSelectedBarbero();
        const precioRaw = servicio?.getAttribute("data-precio") || "";
        const money = formatMoney(precioRaw) || "S/. 0.00";
        const duracion = (servicio?.getAttribute("data-duracion") || "").trim();
        const fechaKey = getFechaSeleccionada();
        const horaLabel = getHoraSeleccionada();
    
        if (horaLabel && !state.selectedTime) {
          state.selectedTime = horaLabel;
        }
        if (fechaKey && !state.selectedDate) {
          state.selectedDate = fechaKey;
        }
        persistCitaSeleccion();
    
        if (checkoutServicio instanceof HTMLElement) {
          checkoutServicio.textContent = servicio?.getAttribute("data-nombre") || "—";
        }
        if (checkoutPrecio instanceof HTMLElement) {
          checkoutPrecio.textContent = money;
        }
        if (checkoutMeta instanceof HTMLElement) {
          checkoutMeta.textContent = duracion
            ? `${duracion} min · Premium Grooming`
            : "Premium Grooming";
        }
        if (checkoutBarbero instanceof HTMLElement) {
          checkoutBarbero.textContent = barbero?.getAttribute("data-nombre") || "—";
        }
        if (checkoutAvatar instanceof HTMLElement) {
          const foto = barbero?.getAttribute("data-foto") || "";
          checkoutAvatar.style.backgroundImage = foto ? `url("${foto}")` : "";
        }
    
        if (checkoutFecha instanceof HTMLElement) {
          if (fechaKey) {
            const date = parseDateKey(fechaKey);
            const diaCorto = DIAS_SEMANA[date.getDay()].slice(0, 3);
            checkoutFecha.textContent = `${diaCorto}, ${date.getDate()} ${MESES_CORTO[date.getMonth()]}`;
          } else {
            checkoutFecha.textContent = "—";
          }
        }
    
        if (checkoutHora instanceof HTMLElement) {
          checkoutHora.textContent = horaLabel ? formatHoraCheckout(horaLabel) : "";
        }
    
        const nombres =
          campoNombres instanceof HTMLInputElement
            ? campoNombres.value.trim()
            : reservaRoot.getAttribute("data-reserva-nombres") || "";
        const apellidos =
          campoApellidos instanceof HTMLInputElement
            ? campoApellidos.value.trim()
            : reservaRoot.getAttribute("data-reserva-apellidos") || "";
        const telefono =
          campoTelefono instanceof HTMLInputElement
            ? campoTelefono.value.trim()
            : reservaRoot.getAttribute("data-reserva-telefono") || "";
        const email =
          campoEmail instanceof HTMLInputElement
            ? campoEmail.value.trim()
            : reservaRoot.getAttribute("data-reserva-email") || "";
    
        if (checkoutCliente instanceof HTMLElement) {
          const fullName = [nombres, apellidos].filter(Boolean).join(" ").trim();
          checkoutCliente.textContent = fullName || "—";
        }
        if (checkoutTelefono instanceof HTMLElement) {
          checkoutTelefono.textContent = telefono || "—";
        }
        if (checkoutEmail instanceof HTMLElement) {
          checkoutEmail.textContent = email || "—";
        }
    
        if (checkoutSubtotal instanceof HTMLElement) {
          checkoutSubtotal.textContent = money;
        }
        const prodCents = getProductosTotalCentimos();
        const servCents = parsePrecioACentimos(precioRaw);
        const totalCents = servCents + prodCents;
        const totalMoney =
          "S/. " + (totalCents / 100).toFixed(2);
        const prodEl = reservaRoot.querySelector("[data-checkout-productos-total]");
        if (prodEl instanceof HTMLElement) {
          prodEl.textContent = "S/. " + (prodCents / 100).toFixed(2);
        }
        if (checkoutTotal instanceof HTMLElement) {
          checkoutTotal.textContent = totalMoney;
        }
        if (yapeMonto instanceof HTMLElement) {
          yapeMonto.textContent = totalMoney;
        }
      };
    
      reservaRoot.addEventListener("change", (event) => {
        const t = event.target;
        if (!(t instanceof Element)) {
          return;
        }
        if (
          t.matches("[data-producto-check]") ||
          t.matches("[data-producto-qty]")
        ) {
          syncCheckoutSummary();
        }
      });
    
      const parseHoraToMinutes = (label) => {
        const match = String(label || "").match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i);
        if (!match) {
          return null;
        }
        let h = Number(match[1]);
        const m = Number(match[2]);
        const ampm = match[3].toUpperCase();
        if (ampm === "PM" && h < 12) {
          h += 12;
        }
        if (ampm === "AM" && h === 12) {
          h = 0;
        }
        return h * 60 + m;
      };
    
      const formatConfirmFecha = () => {
        const fechaKey = getFechaSeleccionada();
        if (!fechaKey) {
          return "—";
        }
        const date = parseDateKey(fechaKey);
        const diaCorto = DIAS_SEMANA[date.getDay()].slice(0, 3);
        return `${diaCorto}, ${date.getDate()} ${MESES_CORTO[date.getMonth()]}`;
      };
    
      const formatConfirmHora = () => {
        const horaLabel = getHoraSeleccionada();
        if (!horaLabel) {
          // Fallback: texto ya renderizado en checkout/datos.
          const fromCheckout = checkoutHora instanceof HTMLElement ? checkoutHora.textContent.trim() : "";
          if (fromCheckout) {
            return fromCheckout;
          }
          const fromDatos = datosHora instanceof HTMLElement ? datosHora.textContent.trim() : "";
          if (fromDatos) {
            return fromDatos.replace(/^-\s*/, "");
          }
          return "—";
        }
        return formatHoraCheckout(horaLabel) || horaLabel;
      };
    
      const getClienteNombreCompleto = () => {
        const nombresInput = campoNombres instanceof HTMLInputElement ? campoNombres.value.trim() : "";
        const apellidosInput = campoApellidos instanceof HTMLInputElement ? campoApellidos.value.trim() : "";
        const nombres = nombresInput || reservaRoot.getAttribute("data-reserva-nombres") || "";
        const apellidos = apellidosInput || reservaRoot.getAttribute("data-reserva-apellidos") || "";
        return [nombres, apellidos].filter(Boolean).join(" ").trim();
      };
    
      const getClienteTelefono = () => {
        const fromInput = campoTelefono instanceof HTMLInputElement ? campoTelefono.value.trim() : "";
        return fromInput || reservaRoot.getAttribute("data-reserva-telefono") || "";
      };
    
      const syncConfirmadaSummary = () => {
        const servicio = getSelectedServicio();
        const barbero = getSelectedBarbero();
        const precioRaw = servicio?.getAttribute("data-precio") || "";
        const money = formatMoney(precioRaw) || "S/. 0.00";
        const cliente = getClienteNombreCompleto();
        const telefono = getClienteTelefono();
    
        // Recuperar hora desde DOM si el state se perdió.
        if (!state.selectedTime) {
          const recovered = getHoraSeleccionada();
          if (recovered) {
            state.selectedTime = recovered;
            persistCitaSeleccion();
          }
        }
    
        if (confirmCliente instanceof HTMLElement) {
          confirmCliente.textContent = cliente || "—";
        }
        if (confirmTelefono instanceof HTMLElement) {
          confirmTelefono.textContent = telefono || "—";
        }
        if (confirmServicio instanceof HTMLElement) {
          confirmServicio.textContent = servicio?.getAttribute("data-nombre") || "—";
        }
        if (confirmBarbero instanceof HTMLElement) {
          confirmBarbero.textContent = barbero?.getAttribute("data-nombre") || "—";
        }
        if (confirmFecha instanceof HTMLElement) {
          confirmFecha.textContent = formatConfirmFecha();
        }
        if (confirmHora instanceof HTMLElement) {
          confirmHora.textContent = formatConfirmHora();
        }
        if (confirmPrecio instanceof HTMLElement) {
          confirmPrecio.textContent = money;
        }
      };
    
      const buildVoucherShareText = () => {
        const cliente = confirmCliente?.textContent?.trim() || "—";
        const telefono = confirmTelefono?.textContent?.trim() || "—";
        const servicio = confirmServicio?.textContent?.trim() || "—";
        const barbero = confirmBarbero?.textContent?.trim() || "—";
        const fecha = confirmFecha?.textContent?.trim() || "—";
        const hora = confirmHora?.textContent?.trim() || "—";
        const precio = confirmPrecio?.textContent?.trim() || "—";
        const ubicacion = confirmUbicacion?.textContent?.trim() || "—";
        const brand =
          window.yuniorrojasTheme?.siteName ||
          document.querySelector("[data-confirm-brand]")?.textContent?.trim() ||
          "Junior Rojas Barber Studio";
    
        return [
          `✅ Reserva confirmada — ${brand}`,
          `Cliente: ${cliente}`,
          `Teléfono: ${telefono}`,
          `Servicio: ${servicio}`,
          `Barbero: ${barbero}`,
          `Fecha: ${fecha}`,
          `Hora: ${hora}`,
          `Total: ${precio}`,
          `Ubicación: ${ubicacion}`,
        ].join("\n");
      };
    
      const captureVoucherCanvas = async () => {
        if (!(voucherRoot instanceof HTMLElement)) {
          throw new Error("No se encontró el voucher");
        }
        if (typeof html2canvas !== "function") {
          throw new Error("html2canvas no está disponible");
        }
    
        return html2canvas(voucherRoot, {
          backgroundColor: "#000000",
          scale: 2,
          useCORS: true,
          logging: false,
        });
      };
    
      const downloadVoucherImage = async () => {
        if (!(voucherDownloadBtn instanceof HTMLButtonElement)) {
          return false;
        }
    
        voucherDownloadBtn.classList.add("is-loading");
        voucherDownloadBtn.disabled = true;
        const original = voucherDownloadBtn.innerHTML;
        voucherDownloadBtn.innerHTML = '<i class="ti ti-loader" aria-hidden="true"></i> Generando...';
    
        try {
          const canvas = await captureVoucherCanvas();
          const link = document.createElement("a");
          const stamp = new Date().toISOString().slice(0, 10);
          link.download = `voucher-reserva-${stamp}.png`;
          link.href = canvas.toDataURL("image/png");
          link.click();
          state.voucherDownloaded = true;
          return true;
        } catch (error) {
          window.alert("No se pudo generar el voucher. Intenta de nuevo.");
          return false;
        } finally {
          voucherDownloadBtn.classList.remove("is-loading");
          voucherDownloadBtn.disabled = false;
          voucherDownloadBtn.innerHTML = original;
        }
      };
    
      const clearServiciosRedirect = () => {
        if (state.serviciosRedirectTimer) {
          window.clearTimeout(state.serviciosRedirectTimer);
          state.serviciosRedirectTimer = 0;
        }
      };
    
      /**
       * Tras reserva exitosa (Plin / Culqi / estudio): ir a página de servicios.
       */
      const goToServicios = () => {
        clearServiciosRedirect();
        try {
          window.sessionStorage.removeItem(CITA_STORAGE_KEY);
        } catch (error) {
          // ignore
        }
    
        closeConfirmadaModal(true);
        document.body.classList.remove("is-reserva-pago-modal", "is-reserva-error-modal");
    
        const serviciosUrl =
          window.yuniorrojasTheme?.serviciosUrl ||
          window.yuniorrojasTheme?.homeUrl ||
          "/";
        window.location.assign(serviciosUrl);
      };
    
      const scheduleRedirectToServicios = (delayMs = 3200) => {
        clearServiciosRedirect();
        state.serviciosRedirectTimer = window.setTimeout(() => {
          goToServicios();
        }, delayMs);
      };
    
      const finishAfterVoucherDownload = () => {
        goToServicios();
      };
    
      const shareVoucher = async (channel) => {
        const text = buildVoucherShareText();
        const homeUrl = window.yuniorrojasTheme?.homeUrl || window.location.origin;
    
        if (channel === "whatsapp") {
          const url = `https://wa.me/?text=${encodeURIComponent(`${text}\n${homeUrl}`)}`;
          window.open(url, "_blank", "noopener,noreferrer");
          return;
        }
    
        if (channel === "facebook") {
          const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(homeUrl)}&quote=${encodeURIComponent(text)}`;
          window.open(url, "_blank", "noopener,noreferrer");
          return;
        }
    
        // Nativo: intenta compartir imagen + texto.
        try {
          if (navigator.share) {
            let files = [];
            try {
              const canvas = await captureVoucherCanvas();
              const blob = await new Promise((resolve) => canvas.toBlob(resolve, "image/png"));
              if (blob) {
                files = [new File([blob], "voucher-reserva.png", { type: "image/png" })];
              }
            } catch (error) {
              files = [];
            }
    
            const payload = {
              title: "Reserva confirmada",
              text,
              url: homeUrl,
            };
    
            if (files.length && navigator.canShare && navigator.canShare({ files })) {
              payload.files = files;
            }
    
            await navigator.share(payload);
            return;
          }
        } catch (error) {
          // Si cancela o falla, cae a copiar texto.
        }
    
        try {
          await navigator.clipboard.writeText(`${text}\n${homeUrl}`);
          window.alert("Detalle de la reserva copiado. Ya puedes pegarlo en tus redes.");
        } catch (error) {
          window.alert("No se pudo compartir automáticamente. Copia el voucher descargándolo como imagen.");
        }
      };
    
      if (voucherDownloadBtn instanceof HTMLButtonElement) {
        voucherDownloadBtn.addEventListener("click", async (event) => {
          event.preventDefault();
          const ok = await downloadVoucherImage();
          if (ok) {
            // Pequeña pausa para que el navegador dispare la descarga.
            window.setTimeout(() => {
              goToServicios();
            }, 450);
          }
        });
      }
    
      if (voucherContinuarBtn instanceof HTMLButtonElement) {
        voucherContinuarBtn.addEventListener("click", (event) => {
          event.preventDefault();
          goToServicios();
        });
      }
    
      shareBtns.forEach((btn) => {
        btn.addEventListener("click", (event) => {
          event.preventDefault();
          // No cancelar el auto-redirect: pueden compartir y seguir a servicios.
          const channel = btn.getAttribute("data-share") || "nativo";
          shareVoucher(channel);
        });
      });
    
      const setPagoMetodo = (metodo) => {
        state.pagoMetodo = metodo || state.pagoMetodo || "";
        const activeBtn = pagoMetodos.find((btn) => btn.getAttribute("data-pago-metodo") === metodo) || null;
        state.pagoTipo = activeBtn?.getAttribute("data-pago-tipo") || "";
        state.pagoMedioId = Number.parseInt(activeBtn?.getAttribute("data-pago-medio-id") || "0", 10) || 0;
        state.abreCulqi = activeBtn?.getAttribute("data-abre-culqi") === "1";
        state.requiereCodigo = activeBtn?.getAttribute("data-requiere-codigo") === "1";
        state.esEstudio = state.pagoTipo === "estudio";
    
        pagoMetodos.forEach((btn) => {
          const active = btn.getAttribute("data-pago-metodo") === metodo;
          btn.classList.toggle("is-selected", active);
          btn.setAttribute("aria-checked", active ? "true" : "false");
        });
    
        pagoPanels.forEach((panel) => {
          const key = panel.getAttribute("data-pago-panel");
          panel.hidden = key !== metodo;
        });
    
        if (procederPagoBtn instanceof HTMLButtonElement) {
          if (state.pagoTipo === "estudio" || state.esEstudio) {
            procederPagoBtn.innerHTML = 'Confirmar reserva <span aria-hidden="true">→</span>';
          } else if (state.requiereCodigo) {
            procederPagoBtn.innerHTML = 'Finalizar reserva <span aria-hidden="true">→</span>';
          } else {
            procederPagoBtn.innerHTML = 'Pagar ahora <span aria-hidden="true">→</span>';
          }
        }
      };
    
      const syncFormConfirm = () => {
        if (!(irCheckoutBtn instanceof HTMLButtonElement)) {
          return;
        }
        const nombres = campoNombres instanceof HTMLInputElement ? campoNombres.value.trim() : "";
        const apellidos = campoApellidos instanceof HTMLInputElement ? campoApellidos.value.trim() : "";
        const telefono = campoTelefono instanceof HTMLInputElement ? campoTelefono.value.trim() : "";
        const email = campoEmail instanceof HTMLInputElement ? campoEmail.value.trim() : "";
        const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        irCheckoutBtn.disabled = !(nombres && apellidos && telefono && emailOk);
      };
    
      const openConfirmadaModal = () => {
        if (!(confirmadaModal instanceof HTMLElement)) {
          // Sin modal (DOM incompleto): ir directo a servicios.
          scheduleRedirectToServicios(400);
          return;
        }
        state.voucherDownloaded = false;
        syncConfirmadaSummary();
        closeErrorModal();
        closePagoModal();
        confirmadaModal.hidden = false;
        document.body.classList.add("is-reserva-confirmada-modal");
        document.body.style.overflow = "hidden";
        // Tras Plin / pago exitoso no dejamos al usuario atrapado en el checkout.
        scheduleRedirectToServicios(3200);
      };
    
      const closeConfirmadaModal = (force = false) => {
        if (!(confirmadaModal instanceof HTMLElement)) {
          return;
        }
        if (!force && !state.voucherDownloaded && !confirmadaModal.hidden) {
          // Cerrar = continuar a servicios (antes se exigía descargar).
          goToServicios();
          return;
        }
        clearServiciosRedirect();
        confirmadaModal.hidden = true;
        document.body.classList.remove("is-reserva-confirmada-modal");
        document.body.style.overflow = "";
      };
    
      if (confirmadaModal instanceof HTMLElement) {
        const confirmadaBackdrop = confirmadaModal.querySelector(
          ".reservar-confirmada-modal__backdrop"
        );
    
        if (confirmadaBackdrop instanceof HTMLElement) {
          confirmadaBackdrop.addEventListener("click", (event) => {
            event.preventDefault();
            closeConfirmadaModal(false);
          });
        }
    
        confirmadaModal.addEventListener("click", (event) => {
          if (event.target === confirmadaModal) {
            closeConfirmadaModal(false);
          }
        });
      }
    
      document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") {
          return;
        }
        if (!(confirmadaModal instanceof HTMLElement) || confirmadaModal.hidden) {
          return;
        }
        event.preventDefault();
        closeConfirmadaModal(false);
      });
    
      const setPaso = (paso, pushUrl = true) => {
        reservaRoot.setAttribute("data-paso", paso);
    
        if (vistaExperiencia instanceof HTMLElement) {
          vistaExperiencia.hidden = paso !== "experiencia";
        }
        if (vistaCita instanceof HTMLElement) {
          vistaCita.hidden = paso !== "cita";
        }
        if (vistaDatos instanceof HTMLElement) {
          vistaDatos.hidden = paso !== "datos";
        }
        if (vistaCheckout instanceof HTMLElement) {
          // Mantener checkout visible detrás del modal de confirmación.
          vistaCheckout.hidden = !(paso === "checkout" || paso === "confirmada");
        }
    
        if (paso === "cita") {
          if (!state.selectedDate) {
            prepareCitaState();
          }
          renderCalendar();
          void renderSlots();
          syncCitaSummary();
          window.scrollTo({ top: 0, behavior: "smooth" });
        }
    
        if (paso === "datos") {
          syncDatosSummary();
          syncFormConfirm();
          closeConfirmadaModal();
          window.scrollTo({ top: 0, behavior: "smooth" });
        }
    
        if (paso === "checkout") {
          syncCheckoutSummary();
          closeConfirmadaModal();
          window.scrollTo({ top: 0, behavior: "smooth" });
        }
    
        if (paso === "confirmada") {
          openConfirmadaModal();
        } else if (paso !== "checkout") {
          closeConfirmadaModal();
        }
    
        if (pushUrl) {
          const url = new URL(window.location.href);
          const servicio = getSelectedServicio();
          const barbero = getSelectedBarbero();
          if (servicio) {
            url.searchParams.set("servicio", servicio.getAttribute("data-id") || "");
          }
          if (barbero) {
            url.searchParams.set("barbero", barbero.getAttribute("data-id") || "");
          }
          if (paso === "cita" || paso === "datos" || paso === "checkout" || paso === "confirmada") {
            url.searchParams.set("paso", paso);
          } else {
            url.searchParams.delete("paso");
          }
          if (state.selectedDate) {
            url.searchParams.set("fecha", state.selectedDate);
          } else {
            url.searchParams.delete("fecha");
          }
          if (state.selectedTime) {
            url.searchParams.set("hora", state.selectedTime);
          } else {
            url.searchParams.delete("hora");
          }
          const reprogramarId = reservaRoot.getAttribute("data-reprogramar-id") || "";
          if (reprogramarId) {
            url.searchParams.set("reprogramar", reprogramarId);
          }
          window.history.replaceState({}, "", url.toString());
          writeCitaStorage();
        }
      };
    
      const selectExclusive = (items, selected) => {
        items.forEach((el) => {
          const isActive = el === selected;
          el.classList.toggle("is-selected", isActive);
          el.setAttribute("aria-selected", isActive ? "true" : "false");
        });
      };

      /**
       * Barberos móvil: un slide a la vez; cambio SOLO con flechas (← →).
       * Tablet/desktop: grilla CSS (sin track transform).
       * Sin swipe y sin scroll vertical al cambiar de barbero.
       */
      const initBarberosSlider = () => {
        const slider = reservaRoot.querySelector("[data-barberos-slider]");
        if (!(slider instanceof HTMLElement) || !slider.classList.contains("is-slider")) {
          barberoEls.forEach((el) => {
            if (el instanceof HTMLElement) {
              el.setAttribute("tabindex", "0");
            }
          });
          return { scrollSelectedIntoView: () => {} };
        }

        const viewport = slider.querySelector("[data-barberos-viewport]");
        const track = slider.querySelector("[data-barberos-track]");
        const prevBtn = slider.querySelector("[data-barberos-prev]");
        const nextBtn = slider.querySelector("[data-barberos-next]");
        const slideItems = () => Array.from(slider.querySelectorAll(".reservar-barberos__item"));
        const mqCarousel = window.matchMedia("(max-width: 767.98px)");

        if (!(viewport instanceof HTMLElement) || !(track instanceof HTMLElement)) {
          barberoEls.forEach((el) => {
            if (el instanceof HTMLElement) {
              el.setAttribute("tabindex", "0");
            }
          });
          return { scrollSelectedIntoView: () => {} };
        }

        let index = 0;
        let slideWidthPx = 0;

        const isCarousel = () => mqCarousel.matches;
        const maxIndex = () => Math.max(0, slideItems().length - 1);

        // Sin scroll-behavior smooth (si no, “sube/baja” animado al seleccionar).
        const rootEl = document.documentElement;
        const prevRootScrollBehavior = rootEl.style.scrollBehavior;
        rootEl.style.scrollBehavior = "auto";

        const nativeScrollIntoView = Element.prototype.scrollIntoView;
        Element.prototype.scrollIntoView = function jrBarberosScrollIntoViewGuard(arg) {
          if (this instanceof Element && slider.contains(this)) {
            return;
          }
          return nativeScrollIntoView.call(this, arg);
        };

        const measureSlideWidth = () => {
          slideWidthPx = Math.max(1, Math.round(viewport.getBoundingClientRect().width));
          return slideWidthPx;
        };

        const syncFocusability = () => {
          const carousel = isCarousel();
          barberoEls.forEach((el) => {
            if (el instanceof HTMLElement) {
              // Carrusel: no enfocables (evita scroll-on-focus). Grid: sí.
              el.setAttribute("tabindex", carousel ? "-1" : "0");
            }
          });
          viewport.setAttribute("tabindex", carousel ? "0" : "-1");
        };

        const clearTrackMotion = () => {
          track.style.transform = "";
        };

        const applyTransform = () => {
          if (!isCarousel()) {
            clearTrackMotion();
            return;
          }
          const w = slideWidthPx || measureSlideWidth();
          track.style.transform = `translate3d(${-index * w}px, 0, 0)`;
        };

        const updateArrows = () => {
          const atStart = index <= 0;
          const atEnd = index >= maxIndex();
          if (prevBtn instanceof HTMLButtonElement) {
            prevBtn.disabled = atStart;
            prevBtn.setAttribute("aria-disabled", atStart ? "true" : "false");
          }
          if (nextBtn instanceof HTMLButtonElement) {
            nextBtn.disabled = atEnd;
            nextBtn.setAttribute("aria-disabled", atEnd ? "true" : "false");
          }
        };

        const blurCardFocus = () => {
          const active = document.activeElement;
          if (active instanceof HTMLElement && active.hasAttribute("data-reserva-barbero")) {
            active.blur();
          }
        };

        const goTo = (next, select = true) => {
          if (!isCarousel()) {
            return;
          }

          const section =
            slider.closest(".reservar-step--barberos") ||
            slider.closest(".reservar-step") ||
            slider;
          const topBefore =
            section instanceof HTMLElement ? section.getBoundingClientRect().top : 0;

          index = Math.min(maxIndex(), Math.max(0, next));
          applyTransform();
          updateArrows();

          if (select) {
            const card = slideItems()[index]?.querySelector("[data-reserva-barbero]");
            if (card instanceof HTMLElement) {
              selectExclusive(barberoEls, card);
              syncStep1Summary();
            }
            blurCardFocus();
          }

          // Mantenerse en la sección de barberos al cambiar (no saltar el scroll).
          const pinSection = () => {
            if (!(section instanceof HTMLElement)) {
              return;
            }
            const topAfter = section.getBoundingClientRect().top;
            const delta = topAfter - topBefore;
            if (Math.abs(delta) > 0.5) {
              window.scrollBy(0, delta);
            }
          };
          pinSection();
          window.requestAnimationFrame(() => {
            pinSection();
            window.requestAnimationFrame(pinSection);
          });
        };

        const indexFromSelected = () => {
          const selected = getSelectedBarbero();
          if (!(selected instanceof HTMLElement)) {
            return 0;
          }
          const item = selected.closest(".reservar-barberos__item");
          if (!(item instanceof HTMLElement)) {
            return 0;
          }
          const i = slideItems().indexOf(item);
          return i >= 0 ? i : 0;
        };

        const scrollSelectedIntoView = () => {
          if (!isCarousel()) {
            clearTrackMotion();
            return;
          }
          index = indexFromSelected();
          applyTransform();
          updateArrows();
        };

        // Flechas: único control de cambio en móvil
        if (prevBtn instanceof HTMLButtonElement) {
          prevBtn.addEventListener("mousedown", (event) => {
            event.preventDefault();
          });
          prevBtn.addEventListener("click", (event) => {
            event.preventDefault();
            if (!isCarousel() || prevBtn.disabled) {
              return;
            }
            goTo(index - 1, true);
          });
        }

        if (nextBtn instanceof HTMLButtonElement) {
          nextBtn.addEventListener("mousedown", (event) => {
            event.preventDefault();
          });
          nextBtn.addEventListener("click", (event) => {
            event.preventDefault();
            if (!isCarousel() || nextBtn.disabled) {
              return;
            }
            goTo(index + 1, true);
          });
        }

        // Teclado en el viewport del carrusel
        viewport.addEventListener("keydown", (event) => {
          if (!isCarousel()) {
            return;
          }
          if (event.key === "ArrowRight") {
            event.preventDefault();
            goTo(index + 1, true);
          } else if (event.key === "ArrowLeft") {
            event.preventDefault();
            goTo(index - 1, true);
          } else if (event.key === "Home") {
            event.preventDefault();
            goTo(0, true);
          } else if (event.key === "End") {
            event.preventDefault();
            goTo(maxIndex(), true);
          }
        });

        // Clicks en cards en modo carrusel: NO cambian de barbero (solo las flechas).
        // En grid desktop el listener global sí selecciona.
        barberoEls.forEach((el) => {
          el.addEventListener(
            "click",
            (event) => {
              if (!isCarousel()) {
                return;
              }
              event.preventDefault();
              event.stopPropagation();
              // Refuerza la selección del slide actual (por si el estado se desincronizó)
              goTo(index, true);
            },
            true
          );
        });

        const syncCarouselLayout = () => {
          syncFocusability();
          if (isCarousel()) {
            measureSlideWidth();
            index = indexFromSelected();
            applyTransform();
            updateArrows();
          } else {
            clearTrackMotion();
            updateArrows();
          }
        };

        if (typeof mqCarousel.addEventListener === "function") {
          mqCarousel.addEventListener("change", syncCarouselLayout);
        } else if (typeof mqCarousel.addListener === "function") {
          mqCarousel.addListener(syncCarouselLayout);
        }

        let resizeTimer = 0;
        window.addEventListener("resize", () => {
          window.clearTimeout(resizeTimer);
          resizeTimer = window.setTimeout(() => {
            if (!isCarousel()) {
              syncFocusability();
              clearTrackMotion();
              return;
            }
            measureSlideWidth();
            applyTransform();
          }, 120);
        });

        window.addEventListener(
          "pagehide",
          () => {
            Element.prototype.scrollIntoView = nativeScrollIntoView;
            rootEl.style.scrollBehavior = prevRootScrollBehavior;
          },
          { once: true }
        );

        index = indexFromSelected();
        window.requestAnimationFrame(() => {
          syncCarouselLayout();
        });

        return { scrollSelectedIntoView };
      };

      const barberosSlider = initBarberosSlider();

      /**
       * Servicios:
       * - 4 por página (móvil 1 col; tablet/desktop 2×2)
       * - Flechas solo si total > 4 (a partir de la 2.ª página)
       * - Al paginar en móvil, tablet y desktop: se queda en la sección de servicios
       */
      const initServiciosSlider = () => {
        const slider = reservaRoot.querySelector("[data-servicios-slider]");
        if (!(slider instanceof HTMLElement)) {
          return;
        }

        const items = Array.from(slider.querySelectorAll("[data-servicios-item]"));
        if (!items.length) {
          return;
        }

        const viewport = slider.querySelector("[data-servicios-viewport]");
        const prevBtn = slider.querySelector("[data-servicios-prev]");
        const nextBtn = slider.querySelector("[data-servicios-next]");
        const nav = slider.querySelector("[data-servicios-nav]");
        const pageLabel = slider.querySelector("[data-servicios-page-label]");
        const section =
          slider.closest(".reservar-step--servicios") ||
          slider.closest(".reservar-step") ||
          slider;

        const selectedAttr = Number.parseInt(slider.getAttribute("data-selected-index") || "0", 10);
        const selectedIndex = Number.isFinite(selectedAttr) ? Math.max(0, selectedAttr) : 0;
        const attrSize = Number.parseInt(slider.getAttribute("data-page-size") || "4", 10);
        const PAGE_SIZE = Number.isFinite(attrSize) && attrSize > 0 ? attrSize : 4;
        let page = 0;

        const pageSize = () => PAGE_SIZE;
        const maxPage = () => Math.max(0, Math.ceil(items.length / pageSize()) - 1);

        const applyPage = () => {
          const size = pageSize();
          const max = maxPage();
          page = Math.min(max, Math.max(0, page));
          const start = page * size;
          const end = start + size;

          items.forEach((item, i) => {
            if (!(item instanceof HTMLElement)) {
              return;
            }
            item.classList.toggle("is-page-hidden", !(i >= start && i < end));
          });

          // Solo con 2.ª página en adelante (total > 4). Con 1–4: sin botones.
          const multi = items.length > size;
          slider.classList.toggle("is-nav-visible", multi);
          if (nav instanceof HTMLElement) {
            nav.hidden = !multi;
          }

          if (pageLabel instanceof HTMLElement) {
            pageLabel.textContent = multi ? `${page + 1} / ${max + 1}` : "";
          }

          if (prevBtn instanceof HTMLButtonElement) {
            prevBtn.disabled = page <= 0;
            prevBtn.setAttribute("aria-disabled", page <= 0 ? "true" : "false");
          }
          if (nextBtn instanceof HTMLButtonElement) {
            nextBtn.disabled = page >= max;
            nextBtn.setAttribute("aria-disabled", page >= max ? "true" : "false");
          }
        };

        /**
         * Cambia de página y mantiene la vista en Servicios (móvil, tablet y desktop).
         * Al reducir de 4 cards a 1 el alto baja; sin esto el scroll “cae” en Barberos.
         */
        const goTo = (next) => {
          const target = Math.min(maxPage(), Math.max(0, next));
          if (target === page) {
            return;
          }

          const root = document.documentElement;
          const prevBehavior = root.style.scrollBehavior;
          root.style.scrollBehavior = "auto";

          // Toda la sección de servicios (título + grilla + flechas)
          const block =
            section instanceof HTMLElement ? section : slider;
          const heightBefore = block.getBoundingClientRect().height;
          const topBefore = block.getBoundingClientRect().top;

          page = target;
          applyPage();

          const keepServiciosInView = () => {
            // 1) Compensa el cambio de altura del bloque (todas las resoluciones).
            const heightAfter = block.getBoundingClientRect().height;
            const deltaH = heightBefore - heightAfter;
            if (Math.abs(deltaH) > 0.5) {
              window.scrollBy(0, -deltaH);
            }

            // 2) Si el bloque se movió en pantalla, re-alinea su top.
            const topAfter = block.getBoundingClientRect().top;
            const deltaTop = topAfter - topBefore;
            if (Math.abs(deltaTop) > 0.5) {
              window.scrollBy(0, deltaTop);
            }

            // 3) Garantía: si “02 Barberos” entra en la mitad superior, fija “01 Servicios”.
            const barberos = reservaRoot.querySelector(".reservar-step--barberos");
            const title =
              document.getElementById("reservar-paso-servicio") || block;
            if (!(barberos instanceof HTMLElement) || !(title instanceof HTMLElement)) {
              return;
            }
            const vh = window.innerHeight || 1;
            if (barberos.getBoundingClientRect().top < vh * 0.5) {
              const headerEl = document.querySelector(".header");
              const headerOffset =
                headerEl instanceof HTMLElement
                  ? Math.ceil(headerEl.getBoundingClientRect().height)
                  : 80;
              const dest =
                title.getBoundingClientRect().top +
                window.scrollY -
                headerOffset -
                12;
              window.scrollTo(0, Math.max(0, dest));
            }
          };

          keepServiciosInView();
          window.requestAnimationFrame(() => {
            keepServiciosInView();
            window.requestAnimationFrame(() => {
              keepServiciosInView();
              root.style.scrollBehavior = prevBehavior;
            });
          });
        };

        const pageOfIndex = (itemIndex) =>
          Math.floor(Math.max(0, itemIndex) / pageSize());

        if (prevBtn instanceof HTMLButtonElement) {
          prevBtn.addEventListener("mousedown", (event) => {
            event.preventDefault();
          });
          prevBtn.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();
            if (typeof event.stopImmediatePropagation === "function") {
              event.stopImmediatePropagation();
            }
            if (prevBtn.disabled) {
              return;
            }
            goTo(page - 1);
          });
        }

        if (nextBtn instanceof HTMLButtonElement) {
          nextBtn.addEventListener("mousedown", (event) => {
            event.preventDefault();
          });
          nextBtn.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();
            if (typeof event.stopImmediatePropagation === "function") {
              event.stopImmediatePropagation();
            }
            if (nextBtn.disabled) {
              return;
            }
            goTo(page + 1);
          });
        }

        if (viewport instanceof HTMLElement) {
          viewport.addEventListener("keydown", (event) => {
            if (event.key === "ArrowRight") {
              event.preventDefault();
              goTo(page + 1);
            } else if (event.key === "ArrowLeft") {
              event.preventDefault();
              goTo(page - 1);
            } else if (event.key === "Home") {
              event.preventDefault();
              goTo(0);
            } else if (event.key === "End") {
              event.preventDefault();
              goTo(maxPage());
            }
          });
        }

        // Resize: no volver a página del preseleccionado (bug móvil).
        let resizeTimer = 0;
        window.addEventListener("resize", () => {
          window.clearTimeout(resizeTimer);
          resizeTimer = window.setTimeout(() => {
            const max = maxPage();
            if (page > max) {
              page = max;
            }
            applyPage();
          }, 150);
        });

        page = pageOfIndex(selectedIndex);
        applyPage();
      };

      initServiciosSlider();
    
      servicioEls.forEach((el) => {
        el.addEventListener("click", () => {
          selectExclusive(servicioEls, el);
          syncStep1Summary();
        });
      });
    
      barberoEls.forEach((el) => {
        el.addEventListener("click", () => {
          // En móvil carrusel el handler del slider (capture) se encarga / bloquea.
          // Aquí solo actúa grid desktop o un solo barbero.
          selectExclusive(barberoEls, el);
          syncStep1Summary();
          if (barberosSlider && typeof barberosSlider.scrollSelectedIntoView === "function") {
            barberosSlider.scrollSelectedIntoView();
          }
        });
      });
    
      if (continuarBtn instanceof HTMLButtonElement) {
        continuarBtn.addEventListener("click", (event) => {
          event.preventDefault();
          if (continuarBtn.disabled) {
            return;
          }
          prepareCitaState();
          setPaso("cita");
        });
      }
    
      if (volverBtn instanceof HTMLButtonElement) {
        volverBtn.addEventListener("click", (event) => {
          event.preventDefault();
          setPaso("experiencia");
          window.scrollTo({ top: 0, behavior: "smooth" });
        });
      }
    
      if (volverHorarioBtn instanceof HTMLButtonElement) {
        volverHorarioBtn.addEventListener("click", (event) => {
          event.preventDefault();
          setPaso("cita");
        });
      }
    
      if (volverDatosBtn instanceof HTMLButtonElement) {
        volverDatosBtn.addEventListener("click", (event) => {
          event.preventDefault();
          setPaso("datos");
        });
      }
    
      if (calPrev instanceof HTMLButtonElement) {
        calPrev.addEventListener("click", () => {
          if (calPrev.disabled) {
            return;
          }
          state.viewMonth -= 1;
          if (state.viewMonth < 0) {
            state.viewMonth = 11;
            state.viewYear -= 1;
          }
          renderCalendar();
        });
      }
    
      if (calNext instanceof HTMLButtonElement) {
        calNext.addEventListener("click", () => {
          state.viewMonth += 1;
          if (state.viewMonth > 11) {
            state.viewMonth = 0;
            state.viewYear += 1;
          }
          renderCalendar();
        });
      }
    
      if (datosBtn instanceof HTMLButtonElement) {
        datosBtn.addEventListener("click", (event) => {
          event.preventDefault();
          if (datosBtn.disabled) {
            return;
          }
          // Asegurar hora antes de salir del paso cita.
          if (!state.selectedTime) {
            const selectedSlot = reservaRoot.querySelector(".reservar-horario.is-selected");
            if (selectedSlot instanceof HTMLElement) {
              state.selectedTime =
                selectedSlot.getAttribute("data-hora") || selectedSlot.textContent?.trim() || "";
            }
          }
          persistCitaSeleccion();
          setPaso("datos");
        });
      }
    
      [campoNombres, campoApellidos, campoTelefono, campoEmail].forEach((campo) => {
        if (campo instanceof HTMLInputElement) {
          campo.addEventListener("input", () => {
            syncFormConfirm();
            persistClienteDatos();
            syncDatosSummary();
          });
          campo.addEventListener("blur", () => {
            syncFormConfirm();
            persistClienteDatos();
            syncDatosSummary();
          });
        }
      });
    
      if (campoNotas instanceof HTMLTextAreaElement) {
        campoNotas.addEventListener("input", () => {
          syncDatosSummary();
        });
        campoNotas.addEventListener("blur", () => {
          syncDatosSummary();
        });
      }
    
      if (datosForm instanceof HTMLFormElement) {
        datosForm.addEventListener("submit", (event) => {
          event.preventDefault();
        });
      }
    
      if (irCheckoutBtn instanceof HTMLButtonElement) {
        irCheckoutBtn.addEventListener("click", (event) => {
          event.preventDefault();
          if (irCheckoutBtn.disabled) {
            return;
          }
          persistCitaSeleccion();
          persistClienteDatos();
          setPaso("checkout");
        });
      }
    
      pagoMetodos.forEach((btn) => {
        btn.addEventListener("click", () => {
          const metodo = btn.getAttribute("data-pago-metodo") || "tarjeta";
          setPagoMetodo(metodo);
        });
      });
    
      Array.from(reservaRoot.querySelectorAll("[data-copiar-yape]")).forEach((btn) => {
        if (!(btn instanceof HTMLButtonElement)) {
          return;
        }
        btn.addEventListener("click", async () => {
          const valor = btn.getAttribute("data-copiar-valor") || "";
          if (!valor) {
            return;
          }
    
          const marcarCopiado = () => {
            const original = btn.textContent || "Copiar";
            btn.textContent = "Copiado";
            btn.classList.add("is-copied");
            window.setTimeout(() => {
              btn.textContent = original;
              btn.classList.remove("is-copied");
            }, 1600);
          };
    
          try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
              await navigator.clipboard.writeText(valor);
              marcarCopiado();
              return;
            }
          } catch (error) {
            // fallback abajo
          }
    
          const input = document.createElement("input");
          input.value = valor;
          document.body.appendChild(input);
          input.select();
          document.execCommand("copy");
          input.remove();
          marcarCopiado();
        });
      });
    
      const openPagoModal = () => {
        if (!(pagoModal instanceof HTMLElement)) {
          return;
        }
        closeErrorModal();
        pagoModal.hidden = false;
        document.body.classList.add("is-reserva-pago-modal");
        document.body.classList.remove("is-reserva-error-modal");
      };
    
      const closePagoModal = () => {
        if (!(pagoModal instanceof HTMLElement)) {
          return;
        }
        pagoModal.hidden = true;
        document.body.classList.remove("is-reserva-pago-modal");
      };
    
      const syncErrorSummary = () => {
        const servicio = getSelectedServicio();
        const barbero = getSelectedBarbero();
        const money = formatMoney(servicio?.getAttribute("data-precio") || "") || "S/. 0.00";
    
        if (errorServicio instanceof HTMLElement) {
          errorServicio.textContent = servicio?.getAttribute("data-nombre") || "—";
        }
        if (errorBarbero instanceof HTMLElement) {
          errorBarbero.textContent = barbero?.getAttribute("data-nombre") || "—";
        }
        if (errorTotal instanceof HTMLElement) {
          errorTotal.textContent = money;
        }
      };
    
      const openErrorModal = (mensaje) => {
        if (!(errorModal instanceof HTMLElement)) {
          return;
        }
        syncErrorSummary();
        const lead = errorModal.querySelector("#reservar-error-modal-text");
        if (lead instanceof HTMLElement && typeof mensaje === "string" && mensaje.trim()) {
          lead.textContent = mensaje.trim();
        }
        errorModal.hidden = false;
        document.body.classList.add("is-reserva-error-modal");
        document.body.classList.remove("is-reserva-pago-modal");
      };
    
      const closeErrorModal = () => {
        if (!(errorModal instanceof HTMLElement)) {
          return;
        }
        errorModal.hidden = true;
        document.body.classList.remove("is-reserva-error-modal");
      };
    
      if (errorModal instanceof HTMLElement) {
        const errorBackdrop = errorModal.querySelector(".reservar-error-modal__backdrop");
    
        if (errorBackdrop instanceof HTMLElement) {
          errorBackdrop.addEventListener("click", () => {
            closeErrorModal();
          });
        }
    
        errorModal.addEventListener("click", (event) => {
          if (event.target === errorModal) {
            closeErrorModal();
          }
        });
      }
    
      const preparePagoState = () => {
        if (!state.selectedTime) {
          const hora = getHoraSeleccionada();
          if (hora) {
            state.selectedTime = hora;
          }
        }
        persistCitaSeleccion();
        persistClienteDatos();
        syncCheckoutSummary();
      };
    
      const parsePrecioACentimos = (raw) => {
        const cleaned = String(raw || "")
          .replace(/S\/\.?|PEN/gi, "")
          .replace(/\s/g, "")
          .replace(",", ".");
        const n = Number.parseFloat(cleaned);
        if (!Number.isFinite(n) || n <= 0) {
          return 0;
        }
        return Math.round(n * 100);
      };
    
      const getMontoCheckoutCentimos = () => {
        const servicio = getSelectedServicio();
        const base = parsePrecioACentimos(servicio?.getAttribute("data-precio") || "0");
        return base + getProductosTotalCentimos();
      };
    
      /**
       * Abre Culqi Checkout y resuelve con token id (tkn_… / ype_…).
       * @returns {Promise<string>}
       */
      const solicitarTokenCulqi = (emailHint) => {
        const culqiCfg = window.yuniorrojasTheme?.culqi;
        if (!culqiCfg?.enabled || !culqiCfg.publicKey) {
          return Promise.reject(
            new Error("Los pagos online no están configurados. Elige Plin o pago en el estudio.")
          );
        }
    
        const amount = getMontoCheckoutCentimos();
        if (amount < 100) {
          return Promise.reject(new Error("El monto del servicio no es válido para cobro online."));
        }
    
        // Prioridad: sesión WP (cuenta) → hint backend → atributo DOM → formulario.
        const resolveEmail = () => {
          const fromCampo =
            campoEmail instanceof HTMLInputElement ? campoEmail.value.trim() : "";
          const fromRoot = (reservaRoot.getAttribute("data-reserva-email") || "").trim();
          const fromTheme = String(window.yuniorrojasTheme?.userEmail || "").trim();
          const fromHint = String(emailHint || "").trim();
          const candidates = [fromTheme, fromHint, fromRoot, fromCampo];
          for (let i = 0; i < candidates.length; i += 1) {
            const value = candidates[i];
            if (value && value.includes("@")) {
              return value;
            }
          }
          return "";
        };
    
        const email = resolveEmail();
        if (!email) {
          return Promise.reject(
            new Error("No se encontró tu correo de cuenta. Completa tus datos e inténtalo de nuevo.")
          );
        }
    
        const publicKey = String(culqiCfg.publicKey);
        const siteName = window.yuniorrojasTheme?.siteName || "Reserva";
        const settings = {
          title: siteName,
          currency: culqiCfg.currency || "PEN",
          amount,
          description: "Reserva de cita",
        };
    
        /**
         * Email de cuenta en Culqi: precargado y deshabilitado (no editable).
         * Culqi re-renderiza el modal; reaplicamos disabled mientras esté abierto.
         */
        const precargarEmailEnModal = (correo) => {
          if (!correo) {
            return;
          }
    
          if (window.__jrCulqiEmailLock && typeof window.__jrCulqiEmailLock.cleanup === "function") {
            window.__jrCulqiEmailLock.cleanup();
          }
    
          const nativeSet = Object.getOwnPropertyDescriptor(
            window.HTMLInputElement.prototype,
            "value"
          )?.set;
    
          const esCampoSitio = (el) => {
            if (!(el instanceof HTMLInputElement)) {
              return true;
            }
            if (el.id === "reserva-email" || el.closest("[data-reserva]") || el.closest(".reservar-datos")) {
              return true;
            }
            return false;
          };
    
          const esEmailCulqi = (el) => {
            if (!(el instanceof HTMLInputElement) || esCampoSitio(el)) {
              return false;
            }
            const type = (el.type || "").toLowerCase();
            const name = (el.name || "").toLowerCase();
            const id = (el.id || "").toLowerCase();
            const ph = (el.placeholder || "").toLowerCase();
            const auto = (el.autocomplete || "").toLowerCase();
            return (
              type === "email" ||
              name.includes("email") ||
              name.includes("correo") ||
              id.includes("email") ||
              id.includes("correo") ||
              auto === "email" ||
              ph.includes("correo") ||
              ph.includes("email")
            );
          };
    
          const findEmailInputs = () => {
            /** @type {HTMLInputElement[]} */
            const found = [];
            document.querySelectorAll("input").forEach((el) => {
              if (el instanceof HTMLInputElement && esEmailCulqi(el)) {
                found.push(el);
              }
            });
            return found;
          };
    
          const deshabilitarEmail = () => {
            findEmailInputs().forEach((el) => {
              try {
                if (typeof nativeSet === "function") {
                  nativeSet.call(el, correo);
                } else {
                  el.value = correo;
                }
                el.setAttribute("value", correo);
              } catch (error) {
                // ignore
              }
              el.disabled = true;
              el.readOnly = true;
              el.setAttribute("disabled", "disabled");
              el.setAttribute("readonly", "readonly");
              el.setAttribute("aria-disabled", "true");
              el.tabIndex = -1;
              el.style.cursor = "not-allowed";
              el.style.opacity = "0.85";
              el.style.backgroundColor = "#f3f4f6";
              el.style.pointerEvents = "none";
              el.title = "Correo de tu cuenta (no editable)";
            });
          };
    
          const intervalId = window.setInterval(deshabilitarEmail, 120);
          const observer = new MutationObserver(() => {
            deshabilitarEmail();
          });
          try {
            observer.observe(document.body, { childList: true, subtree: true });
          } catch (error) {
            // ignore
          }
    
          deshabilitarEmail();
          [80, 250, 600, 1200, 2000].forEach((ms) => {
            window.setTimeout(deshabilitarEmail, ms);
          });
    
          let saw = false;
          let miss = 0;
          const watchClose = window.setInterval(() => {
            if (findEmailInputs().length > 0) {
              saw = true;
              miss = 0;
              return;
            }
            if (!saw) {
              return;
            }
            miss += 1;
            if (miss >= 4) {
              cleanup();
            }
          }, 500);
    
          const cleanup = () => {
            window.clearInterval(intervalId);
            window.clearInterval(watchClose);
            try {
              observer.disconnect();
            } catch (error) {
              // ignore
            }
            if (window.__jrCulqiEmailLock) {
              window.__jrCulqiEmailLock = null;
            }
          };
    
          window.setTimeout(cleanup, 180000);
    
          window.__jrCulqiEmailLock = {
            email: correo,
            cleanup,
          };
        };
    
        /**
         * Cierra el modal de Culqi (Checkout v4 / legacy).
         * Si solo tokenizamos y luego cobramos en servidor, Culqi no cierra solo.
         */
        const cerrarModalCulqi = () => {
          try {
            if (
              window.__jrCulqiEmailLock &&
              typeof window.__jrCulqiEmailLock.cleanup === "function"
            ) {
              window.__jrCulqiEmailLock.cleanup();
            }
          } catch (error) {
            // ignore
          }
    
          const C = window.Culqi;
          try {
            if (C && typeof C.close === "function") {
              C.close();
            }
          } catch (error) {
            // ignore
          }
    
          // Por si la instancia no dejó de montar el overlay.
          try {
            const closeSelectors = [
              'button[aria-label="Cerrar"]',
              'button[aria-label="Close"]',
              'button[aria-label="close"]',
              '[class*="close" i][class*="culqi" i]',
              '#culqi-container .close',
              '.culqi-close',
            ];
            for (let i = 0; i < closeSelectors.length; i += 1) {
              const btn = document.querySelector(closeSelectors[i]);
              if (btn instanceof HTMLElement && btn.offsetParent !== null) {
                btn.click();
                break;
              }
            }
          } catch (error) {
            // ignore
          }
    
          try {
            document.body.style.overflow = "";
            document.documentElement.style.overflow = "";
          } catch (error) {
            // ignore
          }
        };
    
        return new Promise((resolve, reject) => {
          let settled = false;
          const finishOk = (tokenId) => {
            if (settled) {
              return;
            }
            settled = true;
            cerrarModalCulqi();
            // Refuerzo: a veces Culqi reabre/queda un frame tras el close inmediato.
            window.setTimeout(cerrarModalCulqi, 50);
            window.setTimeout(cerrarModalCulqi, 250);
            resolve(tokenId);
          };
          const finishErr = (message) => {
            if (settled) {
              return;
            }
            settled = true;
            cerrarModalCulqi();
            reject(new Error(message || "No se pudo completar el pago con Culqi."));
          };
    
          const handleCulqiResult = () => {
            const C = window.Culqi;
            if (!C) {
              finishErr("Culqi no respondió. Recarga e inténtalo de nuevo.");
              return;
            }
            const token = C.token;
            if (token && token.id) {
              finishOk(String(token.id));
              return;
            }
            const err = C.error;
            if (err) {
              const msg =
                err.user_message ||
                err.merchant_message ||
                err.message ||
                "Pago cancelado o rechazado.";
              finishErr(String(msg));
              return;
            }
            finishErr("Cerraste el pago sin completar. Intenta de nuevo cuando estés listo.");
          };
    
          const clientConfig = {
            email,
          };
    
          const optionsConfig = {
            lang: "es",
            installments: false,
            modal: true,
            paymentMethods: {
              tarjeta: true,
              yape: true,
              billetera: false,
              bancaMovil: false,
              agente: false,
              cuotealo: false,
            },
            paymentMethodsSort: ["tarjeta", "yape"],
          };
    
          try {
            if (typeof window.CulqiCheckout === "function") {
              const instance = new window.CulqiCheckout(publicKey, {
                settings,
                options: optionsConfig,
                client: clientConfig,
              });
              window.Culqi = instance;
              instance.culqi = handleCulqiResult;
              instance.open();
              precargarEmailEnModal(email);
              return;
            }
    
            if (window.Culqi && typeof window.Culqi.settings === "function") {
              window.Culqi.publicKey = publicKey;
              window.Culqi.settings(settings);
              if (typeof window.Culqi.options === "function") {
                window.Culqi.options(optionsConfig);
              }
              // Algunas builds exponen client como propiedad editable.
              try {
                window.Culqi.client = clientConfig;
              } catch (error) {
                // ignore
              }
              window.culqi = handleCulqiResult;
              window.Culqi.open();
              precargarEmailEnModal(email);
              return;
            }
    
            finishErr("No se pudo cargar Culqi. Recarga la página e inténtalo otra vez.");
          } catch (error) {
            const message = error instanceof Error ? error.message : "Error al abrir Culqi.";
            finishErr(message);
          }
        });
      };
    
      const buildReservaPayload = (culqiToken = "") => {
        const servicio = getSelectedServicio();
        const barbero = getSelectedBarbero();
        const nombres =
          campoNombres instanceof HTMLInputElement
            ? campoNombres.value.trim()
            : reservaRoot.getAttribute("data-reserva-nombres") || "";
        const apellidos =
          campoApellidos instanceof HTMLInputElement
            ? campoApellidos.value.trim()
            : reservaRoot.getAttribute("data-reserva-apellidos") || "";
        const telefono =
          campoTelefono instanceof HTMLInputElement
            ? campoTelefono.value.trim()
            : reservaRoot.getAttribute("data-reserva-telefono") || "";
        const email =
          campoEmail instanceof HTMLInputElement
            ? campoEmail.value.trim()
            : reservaRoot.getAttribute("data-reserva-email") || "";
        const notas =
          campoNotas instanceof HTMLTextAreaElement ? campoNotas.value.trim() : "";
        const codigoInput = reservaRoot.querySelector(
          `[data-medio-codigo="${state.pagoMetodo}"]`
        ) || reservaRoot.querySelector("[data-yape-codigo]");
    
        return {
          servicio_id: Number.parseInt(servicio?.getAttribute("data-id") || "0", 10) || 0,
          barbero_id: Number.parseInt(barbero?.getAttribute("data-id") || "0", 10) || 0,
          fecha: state.selectedDate || reservaRoot.getAttribute("data-reserva-fecha") || "",
          hora: state.selectedTime || getHoraSeleccionada() || "",
          nombres,
          apellidos,
          telefono,
          email,
          notas,
          metodo_pago: state.pagoMetodo || "estudio",
          medio_pago_id: state.pagoMedioId || 0,
          reprogramar_id:
            Number.parseInt(reservaRoot.getAttribute("data-reprogramar-id") || "0", 10) || 0,
          codigo_operacion:
            codigoInput instanceof HTMLInputElement ? codigoInput.value.trim() : "",
          culqi_token: culqiToken || "",
          productos: JSON.stringify(getSelectedProductos()),
        };
      };
    
      const confirmarReserva = async () => {
        if (state.isSubmittingReserva) {
          return;
        }
    
        preparePagoState();
        let payload = buildReservaPayload();
        const endpoint = window.yuniorrojasTheme?.restReservas;
        const culqiCfg = window.yuniorrojasTheme?.culqi;
    
        if (!endpoint) {
          openErrorModal("No se pudo conectar con el servidor de reservas. Recarga la página.");
          return;
        }
    
        if (
          !payload.servicio_id ||
          !payload.barbero_id ||
          !payload.fecha ||
          !payload.hora ||
          !payload.nombres ||
          !payload.apellidos ||
          !payload.telefono ||
          !payload.email
        ) {
          openErrorModal("Faltan datos de la reserva. Revisa servicio, horario y tus datos.");
          return;
        }
    
        if (state.requiereCodigo && !payload.reprogramar_id) {
          if (!payload.codigo_operacion || payload.codigo_operacion.length < 4) {
            openErrorModal("Ingresa el código de operación (mínimo 4 caracteres).");
            return;
          }
        }
    
        if (state.abreCulqi && !payload.reprogramar_id) {
          if (!culqiCfg?.enabled) {
            openErrorModal(
              "Los pagos online no están disponibles. Elige otro medio de pago."
            );
            return;
          }
    
          try {
            const tokenId = await solicitarTokenCulqi(payload.email);
            payload = buildReservaPayload(tokenId);
          } catch (error) {
            const message =
              error instanceof Error
                ? error.message
                : "No se pudo completar el pago online.";
            openErrorModal(message);
            return;
          }
        }
    
        state.isSubmittingReserva = true;
        state.paymentAttempts += 1;
        openPagoModal();
    
        try {
          const formData = new FormData();
          Object.keys(payload).forEach((key) => {
            formData.append(key, payload[key] == null ? "" : String(payload[key]));
          });
    
          const response = await fetch(endpoint, {
            method: "POST",
            credentials: "same-origin",
            headers: {
              "X-WP-Nonce": window.yuniorrojasTheme?.restNonce || "",
            },
            body: formData,
          });
    
          const data = await response.json().catch(() => ({}));
          closePagoModal();
          // Asegurar que Culqi no quede encima del voucher.
          try {
            if (window.Culqi && typeof window.Culqi.close === "function") {
              window.Culqi.close();
            }
          } catch (errClose) {
            // ignore
          }
    
          if (!response.ok) {
            const message =
              data?.message ||
              data?.data?.message ||
              "No pudimos confirmar tu reserva. Inténtalo de nuevo.";
            openErrorModal(String(message));
            if (response.status === 409) {
              void renderSlots();
            }
            return;
          }
    
          closeErrorModal();
          if (data?.id) {
            reservaRoot.setAttribute("data-reserva-id", String(data.id));
          }
          if (data?.message) {
            reservaRoot.setAttribute("data-reserva-mensaje", String(data.message));
          }
          setPaso("confirmada");
          try {
            if (window.Culqi && typeof window.Culqi.close === "function") {
              window.Culqi.close();
            }
          } catch (errClose2) {
            // ignore
          }
        } catch (error) {
          closePagoModal();
          try {
            if (window.Culqi && typeof window.Culqi.close === "function") {
              window.Culqi.close();
            }
          } catch (errClose3) {
            // ignore
          }
          openErrorModal("Error de conexión. Verifica tu internet e inténtalo otra vez.");
        } finally {
          state.isSubmittingReserva = false;
        }
      };
    
      if (procederPagoBtn instanceof HTMLButtonElement) {
        procederPagoBtn.addEventListener("click", (event) => {
          event.preventDefault();
          void confirmarReserva();
        });
      }
    
      const listaEsperaBtn = reservaRoot.querySelector("[data-lista-espera-btn]");
      if (listaEsperaBtn instanceof HTMLButtonElement) {
        listaEsperaBtn.addEventListener("click", async (event) => {
          event.preventDefault();
          const endpoint = window.yuniorrojasTheme?.restListaEspera;
          const barbero = getSelectedBarbero();
          const servicio = getSelectedServicio();
          if (!endpoint || !barbero) {
            openErrorModal("No se pudo registrar la lista de espera.");
            return;
          }
          listaEsperaBtn.disabled = true;
          try {
            const response = await fetch(endpoint, {
              method: "POST",
              credentials: "same-origin",
              headers: {
                "Content-Type": "application/json",
                "X-WP-Nonce": window.yuniorrojasTheme?.restNonce || "",
              },
              body: JSON.stringify({
                barbero_id: Number.parseInt(barbero.getAttribute("data-id") || "0", 10) || 0,
                servicio_id: Number.parseInt(servicio?.getAttribute("data-id") || "0", 10) || 0,
                fecha: state.selectedDate || "",
              }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
              openErrorModal(String(data?.message || "No se pudo unir a la lista de espera."));
              return;
            }
            const ok = reservaRoot.querySelector("[data-lista-espera-ok]");
            if (ok instanceof HTMLElement) {
              ok.hidden = false;
            }
            listaEsperaBtn.hidden = true;
          } catch (error) {
            openErrorModal("Error de conexión al unirte a la lista de espera.");
          } finally {
            listaEsperaBtn.disabled = false;
          }
        });
      }
    
      if (errorReintentarBtn instanceof HTMLButtonElement) {
        errorReintentarBtn.addEventListener("click", (event) => {
          event.preventDefault();
          closeErrorModal();
          void confirmarReserva();
        });
      }
    
      if (errorCambiarMetodoBtn instanceof HTMLButtonElement) {
        errorCambiarMetodoBtn.addEventListener("click", (event) => {
          event.preventDefault();
          closeErrorModal();
          setPaso("checkout");
          const metodos =
            pagoMetodosRoot instanceof HTMLElement
              ? pagoMetodosRoot
              : reservaRoot.querySelector(".reservar-checkout__metodos");
          if (metodos instanceof HTMLElement) {
            metodos.scrollIntoView({ behavior: "smooth", block: "center" });
          }
        });
      }
    
      const primerMetodo =
        pagoMetodos[0]?.getAttribute("data-pago-metodo") ||
        window.yuniorrojasTheme?.mediosPago?.[0]?.slug ||
        "estudio";
      setPagoMetodo(primerMetodo);
      syncStep1Summary();
    
      // Restaurar fecha/hora: URL → sessionStorage → atributos DOM.
      const urlParams = new URLSearchParams(window.location.search);
      const storedCita = readCitaStorage();
      const restoredFecha =
        urlParams.get("fecha") ||
        storedCita?.fecha ||
        reservaRoot.getAttribute("data-reserva-fecha") ||
        "";
      const restoredHora =
        urlParams.get("hora") ||
        storedCita?.hora ||
        reservaRoot.getAttribute("data-reserva-hora") ||
        "";
    
      if (restoredFecha && /^\d{4}-\d{2}-\d{2}$/.test(restoredFecha)) {
        state.selectedDate = restoredFecha;
        const restoredDate = parseDateKey(restoredFecha);
        state.viewYear = restoredDate.getFullYear();
        state.viewMonth = restoredDate.getMonth();
      }
      if (restoredHora) {
        state.selectedTime = restoredHora;
      }
    
      persistCitaSeleccion();
    
      const initialPaso = reservaRoot.getAttribute("data-paso") || "experiencia";
      if (
        initialPaso === "cita" ||
        initialPaso === "datos" ||
        initialPaso === "checkout" ||
        initialPaso === "confirmada"
      ) {
        if (
          (initialPaso === "datos" || initialPaso === "checkout" || initialPaso === "confirmada") &&
          !state.selectedDate
        ) {
          prepareCitaState();
        }
        setPaso(initialPaso, false);
        // Reaplicar URL/storage tras setPaso (sin push) para dejar hora activa.
        persistCitaSeleccion();
        if (initialPaso === "cita") {
          renderCalendar();
          void renderSlots();
          syncCitaSummary();
        }
        if (initialPaso === "datos" || initialPaso === "checkout" || initialPaso === "confirmada") {
          syncDatosSummary();
          syncCheckoutSummary();
        }
      }
    
      syncFormConfirm();
      persistClienteDatos();
      syncDatosSummary();
    }
    
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', yuniorrojasInit_reservar);
  } else {
    yuniorrojasInit_reservar();
  }
})();
