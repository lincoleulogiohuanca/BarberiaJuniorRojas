/** Auto-split from main.js — cuenta */
(function () {
  function yuniorrojasInit_cuenta() {
    // Panel cliente: menú lateral móvil
    const clienteDash = document.querySelector("[data-cliente-dash]");
    if (clienteDash instanceof HTMLElement) {
      const sidebar = clienteDash.querySelector("[data-cliente-sidebar]");
      const overlay = clienteDash.querySelector("[data-cliente-menu-overlay]");
      const toggle = clienteDash.querySelector("[data-cliente-menu-toggle]");
    
      const setOpen = (open) => {
        if (!(sidebar instanceof HTMLElement)) {
          return;
        }
        sidebar.setAttribute("data-open", open ? "true" : "false");
        if (overlay instanceof HTMLElement) {
          overlay.hidden = !open;
        }
        if (toggle instanceof HTMLButtonElement) {
          toggle.setAttribute("aria-expanded", open ? "true" : "false");
        }
        document.body.style.overflow = open ? "hidden" : "";
      };
    
      if (toggle instanceof HTMLButtonElement) {
        toggle.addEventListener("click", () => {
          const open = sidebar?.getAttribute("data-open") === "true";
          setOpen(!open);
        });
      }
    
      if (overlay instanceof HTMLElement) {
        overlay.addEventListener("click", () => setOpen(false));
      }
    
      const cancelModal = clienteDash.querySelector("[data-cliente-cancel-modal]");
      const cancelTitle = clienteDash.querySelector("#cliente-cancel-title");
      const cancelLead = clienteDash.querySelector("[data-cliente-cancel-lead]");
      const cancelMeta = clienteDash.querySelector("[data-cliente-cancel-meta]");
      const cancelWarningText = clienteDash.querySelector(
        "[data-cliente-cancel-warning-text]"
      );
      const cancelCloseBtn = clienteDash.querySelector(
        ".cliente-cancel-modal__btn--ghost[data-cliente-cancel-close]"
      );
      const cancelConfirmBtn = clienteDash.querySelector(
        "[data-cliente-cancel-confirm]"
      );
      let cancelTargetBtn = null;
    
      const closeCancelModal = () => {
        if (!(cancelModal instanceof HTMLElement)) {
          return;
        }
        cancelModal.hidden = true;
        cancelModal.classList.remove("is-blocked");
        document.body.classList.remove("is-cliente-cancel-modal");
        cancelTargetBtn = null;
      };
    
      const openCancelModal = (btn) => {
        if (!(cancelModal instanceof HTMLElement) || !(btn instanceof HTMLButtonElement)) {
          return;
        }
    
        cancelTargetBtn = btn;
        const puede = btn.getAttribute("data-puede-cancelar") === "1";
        const servicio = btn.getAttribute("data-servicio") || "";
        const fechaLabel = btn.getAttribute("data-fecha-label") || "";
    
        cancelModal.classList.toggle("is-blocked", !puede);
    
        if (cancelTitle instanceof HTMLElement) {
          cancelTitle.textContent = puede
            ? "Cancelar cita"
            : "Cancelación no disponible";
        }
    
        if (cancelMeta instanceof HTMLElement) {
          const metaText = [servicio, fechaLabel].filter(Boolean).join(" · ");
          cancelMeta.textContent = metaText;
          cancelMeta.hidden = !metaText;
        }
    
        if (cancelLead instanceof HTMLElement) {
          cancelLead.textContent = puede
            ? "¿Seguro que deseas cancelar esta cita? Esta acción no se puede deshacer."
            : "Esta cita no se puede cancelar desde tu cuenta.";
        }
    
        if (cancelWarningText instanceof HTMLElement) {
          cancelWarningText.textContent = puede
            ? "Solo se permite cancelar reservas con pago en el estudio (efectivo). Si pagaste con tarjeta, transferencia, Yape o Plin, ya no podrás cancelar."
            : "Tu reserva tiene pago digital (tarjeta, transferencia, Yape o Plin). Por política del estudio no se puede cancelar. Contáctanos por WhatsApp si necesitas ayuda.";
        }
    
        if (cancelCloseBtn instanceof HTMLButtonElement) {
          cancelCloseBtn.textContent = puede ? "Mantener cita" : "Entendido";
        }
    
        if (cancelConfirmBtn instanceof HTMLButtonElement) {
          cancelConfirmBtn.hidden = !puede;
          cancelConfirmBtn.disabled = !puede;
          cancelConfirmBtn.textContent = "Sí, cancelar";
        }
    
        cancelModal.hidden = false;
        document.body.classList.add("is-cliente-cancel-modal");
      };
    
      clienteDash.querySelectorAll("[data-cliente-cancel-close]").forEach((el) => {
        el.addEventListener("click", (event) => {
          event.preventDefault();
          closeCancelModal();
        });
      });
    
      if (cancelConfirmBtn instanceof HTMLButtonElement) {
        cancelConfirmBtn.addEventListener("click", async (event) => {
          event.preventDefault();
          const btn = cancelTargetBtn;
          const reservaId = btn?.getAttribute("data-reserva-id") || "";
          const puede = btn?.getAttribute("data-puede-cancelar") === "1";
          const base = window.yuniorrojasTheme?.restReservas;
          if (!btn || !reservaId || !base || !puede) {
            closeCancelModal();
            return;
          }
    
          cancelConfirmBtn.disabled = true;
          const original = cancelConfirmBtn.textContent;
          cancelConfirmBtn.textContent = "Cancelando...";
    
          try {
            const response = await fetch(
              `${base.replace(/\/$/, "")}/${reservaId}/cancelar`,
              {
                method: "POST",
                credentials: "same-origin",
                headers: {
                  "Content-Type": "application/json",
                  "X-WP-Nonce": window.yuniorrojasTheme?.restNonce || "",
                },
                body: JSON.stringify({}),
              }
            );
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
              window.alert(
                data?.message || "No se pudo cancelar la reserva."
              );
              cancelConfirmBtn.disabled = false;
              cancelConfirmBtn.textContent = original || "Sí, cancelar";
              return;
            }
    
            const card = btn.closest("[data-cita-id]");
            closeCancelModal();
            if (card instanceof HTMLElement) {
              card.remove();
            } else {
              window.location.reload();
            }
          } catch (error) {
            window.alert("Error de conexión al cancelar.");
            cancelConfirmBtn.disabled = false;
            cancelConfirmBtn.textContent = original || "Sí, cancelar";
          }
        });
      }
    
      document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") {
          return;
        }
        if (!(cancelModal instanceof HTMLElement) || cancelModal.hidden) {
          return;
        }
        closeCancelModal();
      });
    
      clienteDash.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
          return;
        }
        const btn = target.closest("[data-cancelar-cita]");
        if (!(btn instanceof HTMLButtonElement)) {
          return;
        }
        event.preventDefault();
        openCancelModal(btn);
      });
    
      // Subir captura de pago Plin / manual desde "Próximas citas".
      clienteDash.addEventListener("change", async (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement) || target.type !== "file") {
          return;
        }
        if (!target.hasAttribute("data-comprobante-input")) {
          return;
        }
    
        const file = target.files && target.files[0] ? target.files[0] : null;
        const wrap = target.closest("[data-comprobante-wrap]");
        const reservaId =
          target.getAttribute("data-reserva-id") ||
          (wrap instanceof HTMLElement ? wrap.getAttribute("data-reserva-id") : "") ||
          "";
        const statusEl =
          wrap instanceof HTMLElement
            ? wrap.querySelector("[data-comprobante-status]")
            : null;
        const base = window.yuniorrojasTheme?.restReservas || "";
    
        const setStatus = (text, isError) => {
          if (!(statusEl instanceof HTMLElement)) {
            return;
          }
          statusEl.hidden = !text;
          statusEl.textContent = text || "";
          statusEl.classList.toggle("is-error", Boolean(isError));
          statusEl.classList.toggle("is-ok", Boolean(text) && !isError);
        };
    
        if (!file || !reservaId || !base) {
          setStatus("Selecciona una imagen válida.", true);
          return;
        }
        if (!file.type || file.type.indexOf("image/") !== 0) {
          setStatus("El archivo debe ser una imagen (JPG, PNG o WEBP).", true);
          target.value = "";
          return;
        }
    
        setStatus("Subiendo captura…", false);
        target.disabled = true;
    
        try {
          const formData = new FormData();
          formData.append("comprobante", file);
          const response = await fetch(
            `${base.replace(/\/$/, "")}/${reservaId}/comprobante`,
            {
              method: "POST",
              credentials: "same-origin",
              headers: {
                "X-WP-Nonce": window.yuniorrojasTheme?.restNonce || "",
              },
              body: formData,
            }
          );
          const data = await response.json().catch(() => ({}));
          if (!response.ok) {
            setStatus(
              String(
                data?.message ||
                  data?.data?.message ||
                  "No se pudo subir el comprobante."
              ),
              true
            );
            return;
          }
    
          setStatus(
            String(data?.message || "Comprobante subido correctamente."),
            false
          );
    
          const newUrl = String(data?.comprobante_url || "");
          if (newUrl && wrap instanceof HTMLElement) {
            const viewLink = wrap.querySelector("[data-comprobante-view]");
            if (viewLink instanceof HTMLAnchorElement) {
              viewLink.href = newUrl;
            } else {
              const row = wrap.querySelector(".cliente-cita__comprobante-row");
              if (row instanceof HTMLElement) {
                const a = document.createElement("a");
                a.className = "cliente-cita__comprobante-link";
                a.href = newUrl;
                a.target = "_blank";
                a.rel = "noopener noreferrer";
                a.setAttribute("data-comprobante-view", "");
                a.innerHTML =
                  '<i class="ti ti-photo" aria-hidden="true"></i> Ver captura';
                row.insertBefore(a, row.firstChild);
              }
            }
            const lead = wrap.querySelector(".cliente-cita__comprobante-lead");
            if (lead instanceof HTMLElement) {
              lead.textContent =
                "Ya enviaste una captura. Puedes reemplazarla si hace falta.";
            }
            const btnLabel = wrap.querySelector(".cliente-cita__btn--comprobante");
            if (btnLabel instanceof HTMLElement) {
              btnLabel.innerHTML =
                '<i class="ti ti-upload" aria-hidden="true"></i> Cambiar captura';
            }
          }
        } catch (error) {
          setStatus("Error de conexión. Intenta de nuevo.", true);
        } finally {
          target.disabled = false;
          target.value = "";
        }
      });
    }
    
    // Historial cliente: filtros dark custom + paginación (10 por página)
    const historialRoot = document.querySelector("[data-cliente-historial]");
    if (historialRoot instanceof HTMLElement) {
      const PAGE_SIZE = 10;
      const anioSelect = historialRoot.querySelector("[data-historial-anio]");
      const servicioSelect = historialRoot.querySelector(
        "[data-historial-servicio]"
      );
      const cards = Array.from(
        historialRoot.querySelectorAll("[data-historial-card]")
      ).filter((card) => card instanceof HTMLElement);
      const pagination = historialRoot.querySelector("[data-historial-pagination]");
      const prevBtn = historialRoot.querySelector("[data-historial-prev]");
      const nextBtn = historialRoot.querySelector("[data-historial-next]");
      const pageInfo = historialRoot.querySelector("[data-historial-page-info]");
      const pageNums = historialRoot.querySelector("[data-historial-page-nums]");
      let currentPage = 1;
    
      /**
       * Select nativo se oculta: UI custom con paleta oscura/dorado.
       * @param {HTMLSelectElement} selectEl
       */
      const enhanceDarkSelect = (selectEl) => {
        if (selectEl.dataset.enhanced === "1") {
          return;
        }
        selectEl.dataset.enhanced = "1";
    
        const wrap = selectEl.closest(".cliente-historial-page__filter");
        if (!(wrap instanceof HTMLElement)) {
          return;
        }
    
        const trigger = document.createElement("button");
        trigger.type = "button";
        trigger.className = "cliente-historial-page__select-trigger";
        trigger.setAttribute("aria-haspopup", "listbox");
        trigger.setAttribute("aria-expanded", "false");
    
        const menu = document.createElement("div");
        menu.className = "cliente-historial-page__select-menu";
        menu.setAttribute("role", "listbox");
        menu.hidden = true;
    
        const syncFromSelect = () => {
          menu.innerHTML = "";
          Array.from(selectEl.options).forEach((opt, index) => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "cliente-historial-page__select-option";
            btn.setAttribute("role", "option");
            btn.setAttribute("data-value", opt.value);
            btn.textContent = opt.textContent || opt.value;
            if (opt.selected || selectEl.selectedIndex === index) {
              btn.classList.add("is-selected");
              btn.setAttribute("aria-selected", "true");
            } else {
              btn.setAttribute("aria-selected", "false");
            }
            btn.addEventListener("click", () => {
              selectEl.value = opt.value;
              selectEl.dispatchEvent(new Event("change", { bubbles: true }));
              closeMenu();
            });
            menu.appendChild(btn);
          });
    
          const selected = selectEl.options[selectEl.selectedIndex];
          const label = selected ? selected.textContent || selected.value : "";
          trigger.innerHTML =
            `<span>${label}</span><i class="ti ti-chevron-down" aria-hidden="true"></i>`;
        };
    
        const closeMenu = () => {
          menu.hidden = true;
          wrap.classList.remove("is-open");
          trigger.setAttribute("aria-expanded", "false");
        };
    
        const openMenu = () => {
          historialRoot
            .querySelectorAll(".cliente-historial-page__filter.is-open")
            .forEach((other) => {
              if (other !== wrap) {
                other.classList.remove("is-open");
                const otherMenu = other.querySelector(
                  ".cliente-historial-page__select-menu"
                );
                const otherTrigger = other.querySelector(
                  ".cliente-historial-page__select-trigger"
                );
                if (otherMenu instanceof HTMLElement) {
                  otherMenu.hidden = true;
                }
                if (otherTrigger instanceof HTMLElement) {
                  otherTrigger.setAttribute("aria-expanded", "false");
                }
              }
            });
          menu.hidden = false;
          wrap.classList.add("is-open");
          trigger.setAttribute("aria-expanded", "true");
        };
    
        trigger.addEventListener("click", () => {
          if (menu.hidden) {
            openMenu();
          } else {
            closeMenu();
          }
        });
    
        document.addEventListener("click", (event) => {
          const target = event.target;
          if (!(target instanceof Node) || !wrap.contains(target)) {
            closeMenu();
          }
        });
    
        document.addEventListener("keydown", (event) => {
          if (event.key === "Escape") {
            closeMenu();
          }
        });
    
        selectEl.addEventListener("change", syncFromSelect);
    
        wrap.appendChild(trigger);
        wrap.appendChild(menu);
        syncFromSelect();
      };
    
      if (anioSelect instanceof HTMLSelectElement) {
        enhanceDarkSelect(anioSelect);
      }
      if (servicioSelect instanceof HTMLSelectElement) {
        enhanceDarkSelect(servicioSelect);
      }
    
      const matchingCards = () => {
        const anio =
          anioSelect instanceof HTMLSelectElement ? anioSelect.value : "*";
        const servicio =
          servicioSelect instanceof HTMLSelectElement
            ? servicioSelect.value
            : "*";
    
        return cards.filter((card) => {
          const cardAnio = card.getAttribute("data-anio") || "";
          const cardServicio = card.getAttribute("data-servicio") || "";
          const okAnio = anio === "*" || cardAnio === anio;
          const okServicio = servicio === "*" || cardServicio === servicio;
          return okAnio && okServicio;
        });
      };
    
      const render = () => {
        const matched = matchingCards();
        const totalPages = Math.max(1, Math.ceil(matched.length / PAGE_SIZE));
        if (currentPage > totalPages) {
          currentPage = totalPages;
        }
        if (currentPage < 1) {
          currentPage = 1;
        }
    
        const start = (currentPage - 1) * PAGE_SIZE;
        const end = start + PAGE_SIZE;
        const visibleSet = new Set(matched.slice(start, end));
    
        cards.forEach((card) => {
          card.hidden = !visibleSet.has(card);
        });
    
        if (pagination instanceof HTMLElement) {
          pagination.hidden = matched.length <= PAGE_SIZE;
        }
    
        if (pageInfo instanceof HTMLElement) {
          pageInfo.textContent =
            matched.length === 0
              ? "Sin resultados"
              : `Página ${currentPage} de ${totalPages}`;
        }
    
        if (prevBtn instanceof HTMLButtonElement) {
          prevBtn.disabled = currentPage <= 1 || matched.length === 0;
        }
        if (nextBtn instanceof HTMLButtonElement) {
          nextBtn.disabled = currentPage >= totalPages || matched.length === 0;
        }
    
        if (pageNums instanceof HTMLElement) {
          pageNums.innerHTML = "";
          if (matched.length > 0 && totalPages > 1) {
            for (let page = 1; page <= totalPages; page += 1) {
              const btn = document.createElement("button");
              btn.type = "button";
              btn.className =
                "cliente-historial-page__page-num" +
                (page === currentPage ? " is-active" : "");
              btn.textContent = String(page);
              btn.setAttribute("aria-label", `Ir a página ${page}`);
              if (page === currentPage) {
                btn.setAttribute("aria-current", "page");
              }
              btn.addEventListener("click", () => {
                currentPage = page;
                render();
                historialRoot.scrollIntoView({
                  behavior: "smooth",
                  block: "start",
                });
              });
              pageNums.appendChild(btn);
            }
          }
        }
      };
    
      const onFilterChange = () => {
        currentPage = 1;
        render();
      };
    
      if (anioSelect instanceof HTMLSelectElement) {
        anioSelect.addEventListener("change", onFilterChange);
      }
      if (servicioSelect instanceof HTMLSelectElement) {
        servicioSelect.addEventListener("change", onFilterChange);
      }
      if (prevBtn instanceof HTMLButtonElement) {
        prevBtn.addEventListener("click", () => {
          if (currentPage > 1) {
            currentPage -= 1;
            render();
            historialRoot.scrollIntoView({ behavior: "smooth", block: "start" });
          }
        });
      }
      if (nextBtn instanceof HTMLButtonElement) {
        nextBtn.addEventListener("click", () => {
          currentPage += 1;
          render();
          historialRoot.scrollIntoView({ behavior: "smooth", block: "start" });
        });
      }
    
      render();
    }
    
    // Foto de perfil (sidebar + preferencias): sube y borra anterior en el servidor
    const avatarInputs = document.querySelectorAll("[data-cliente-avatar-input]");
    if (avatarInputs.length > 0) {
      const theme = window.yuniorrojasTheme || {};
      const avatarEndpoint = String(theme.restAvatar || "").replace(/\/$/, "");
      const nonce = String(theme.restNonce || "");
      const statusEl = document.querySelector("[data-cliente-avatar-status]");
      const removeBtn = document.querySelector("[data-cliente-avatar-remove]");
      const labelEl = document.querySelector("[data-cliente-avatar-label]");
      const imgEls = document.querySelectorAll(
        "[data-cliente-avatar-img], .header__account-avatar, .footer__account-avatar"
      );
    
      const setStatus = (message, type) => {
        if (!(statusEl instanceof HTMLElement)) {
          return;
        }
        if (!message) {
          statusEl.hidden = true;
          statusEl.textContent = "";
          statusEl.classList.remove("is-ok", "is-error");
          return;
        }
        statusEl.hidden = false;
        statusEl.textContent = message;
        statusEl.classList.toggle("is-ok", type === "ok");
        statusEl.classList.toggle("is-error", type === "error");
      };
    
      const applyAvatarUrl = (url, isFallback) => {
        const next = String(url || "");
        if (!next) {
          return;
        }
        imgEls.forEach((img) => {
          if (img instanceof HTMLImageElement) {
            img.src = next;
            img.classList.toggle("is-fallback", Boolean(isFallback));
          }
        });
      };
    
      const setHasAvatar = (has) => {
        if (removeBtn instanceof HTMLButtonElement) {
          removeBtn.hidden = !has;
        }
        if (labelEl instanceof HTMLElement) {
          labelEl.textContent = has ? "Cambiar foto" : "Subir foto";
        }
      };
    
      const uploadAvatar = async (file, sourceInput) => {
        if (!avatarEndpoint) {
          setStatus("Endpoint de avatar no disponible.", "error");
          return;
        }
        if (!(file instanceof File)) {
          return;
        }
    
        const maxBytes = 4 * 1024 * 1024;
        if (file.size > maxBytes) {
          setStatus("La imagen no debe superar 4 MB.", "error");
          return;
        }
    
        const allowed = ["image/jpeg", "image/png", "image/webp"];
        if (file.type && !allowed.includes(file.type)) {
          setStatus("Usa JPG, PNG o WEBP.", "error");
          return;
        }
    
        const wrap =
          sourceInput instanceof HTMLElement
            ? sourceInput.closest(".cliente-dash__avatar-wrap")
            : null;
        if (wrap instanceof HTMLElement) {
          wrap.classList.add("is-loading");
        }
        setStatus("Subiendo foto…", null);
    
        try {
          const formData = new FormData();
          formData.append("avatar", file);
    
          const res = await fetch(avatarEndpoint, {
            method: "POST",
            headers: {
              "X-WP-Nonce": nonce,
            },
            credentials: "same-origin",
            body: formData,
          });
    
          const data = await res.json().catch(() => ({}));
          if (!res.ok) {
            const msg =
              data?.message ||
              data?.data?.message ||
              (typeof data === "object" && data?.code
                ? "No se pudo subir la foto."
                : "No se pudo subir la foto.");
            throw new Error(String(msg));
          }
    
          applyAvatarUrl(data?.avatar_url || "", false);
          setHasAvatar(true);
          setStatus(data?.message || "Foto de perfil actualizada.", "ok");
        } catch (err) {
          const message =
            err instanceof Error && err.message
              ? err.message
              : "No se pudo subir la foto.";
          setStatus(message, "error");
        } finally {
          if (wrap instanceof HTMLElement) {
            wrap.classList.remove("is-loading");
          }
          if (sourceInput instanceof HTMLInputElement) {
            sourceInput.value = "";
          }
        }
      };
    
      avatarInputs.forEach((input) => {
        if (!(input instanceof HTMLInputElement)) {
          return;
        }
        input.addEventListener("change", () => {
          const file = input.files && input.files[0] ? input.files[0] : null;
          if (file) {
            uploadAvatar(file, input);
          }
        });
      });
    
      if (removeBtn instanceof HTMLButtonElement) {
        removeBtn.addEventListener("click", async () => {
          if (!avatarEndpoint) {
            return;
          }
          if (!window.confirm("¿Quitar la foto de perfil?")) {
            return;
          }
          setStatus("Eliminando foto…", null);
          removeBtn.disabled = true;
          try {
            const res = await fetch(avatarEndpoint, {
              method: "DELETE",
              headers: {
                "X-WP-Nonce": nonce,
              },
              credentials: "same-origin",
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
              throw new Error(
                String(data?.message || "No se pudo eliminar la foto.")
              );
            }
            applyAvatarUrl(data?.avatar_url || "", true);
            setHasAvatar(false);
            setStatus(data?.message || "Foto de perfil eliminada.", "ok");
          } catch (err) {
            const message =
              err instanceof Error && err.message
                ? err.message
                : "No se pudo eliminar la foto.";
            setStatus(message, "error");
          } finally {
            removeBtn.disabled = false;
          }
        });
      }
    }
    
    // Citas: módulo calendario / recordatorios / compartir
    const REMINDERS_KEY = "jr_cita_recordatorios_v1";
    
    /** @returns {Record<string, {title:string,start:string,offsets:number[],fired:Record<string,number>}>} */
    const loadReminders = () => {
      try {
        const raw = localStorage.getItem(REMINDERS_KEY);
        if (!raw) {
          return {};
        }
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === "object" ? parsed : {};
      } catch (e) {
        return {};
      }
    };
    
    /** @param {Record<string, unknown>} data */
    const saveReminders = (data) => {
      try {
        localStorage.setItem(REMINDERS_KEY, JSON.stringify(data));
      } catch (e) {
        // ignore quota
      }
    };
    
    const showCitaNotification = (title, body) => {
      if (!("Notification" in window) || Notification.permission !== "granted") {
        return;
      }
      try {
        new Notification(title || "Recordatorio de cita", {
          body: body || "Tu cita se acerca.",
          icon: "/favicon.ico",
        });
      } catch (e) {
        // ignore
      }
    };
    
    const processDueReminders = () => {
      const store = loadReminders();
      const now = Date.now();
      let changed = false;
    
      Object.keys(store).forEach((id) => {
        const entry = store[id];
        if (!entry || !entry.start || !Array.isArray(entry.offsets)) {
          return;
        }
        const startMs = Date.parse(entry.start);
        if (!Number.isFinite(startMs)) {
          return;
        }
        if (!entry.fired || typeof entry.fired !== "object") {
          entry.fired = {};
        }
        entry.offsets.forEach((offset) => {
          const key = String(offset);
          const fireAt = startMs - Number(offset) * 60000;
          // Ventana de 2 minutos para disparar si la pestaña está abierta.
          if (fireAt <= now && fireAt > now - 2 * 60000 && !entry.fired[key]) {
            showCitaNotification(
              "Recordatorio de cita",
              entry.title || "Tienes una cita próxima en el estudio."
            );
            entry.fired[key] = now;
            changed = true;
          }
        });
        // Limpia citas ya pasadas (más de 6 h).
        if (startMs < now - 6 * 60 * 60000) {
          delete store[id];
          changed = true;
        }
      });
    
      if (changed) {
        saveReminders(store);
      }
    };
    
    processDueReminders();
    window.setInterval(processDueReminders, 30000);
    
    document.querySelectorAll("[data-cita-tools]").forEach((root) => {
      if (!(root instanceof HTMLElement)) {
        return;
      }
    
      const toggle = root.querySelector("[data-cita-tools-toggle]");
      const panel = root.querySelector("[data-cita-tools-panel]");
      const reservaId = root.getAttribute("data-reserva-id") || "";
      const startIso = root.getAttribute("data-start-iso") || "";
      const title = root.getAttribute("data-title") || "Cita";
      const shareText = root.getAttribute("data-share-text") || title;
      const shareUrl = root.getAttribute("data-share-url") || window.location.href;
      const remStatus = root.querySelector("[data-cita-reminder-status]");
      const remToggle = root.querySelector("[data-cita-reminder-toggle]");
      const remToggleLabel = root.querySelector("[data-cita-reminder-toggle-label]");
      const remToggleIcon = remToggle instanceof HTMLElement
        ? remToggle.querySelector("i")
        : null;
      const shareStatus = root.querySelector("[data-cita-share-status]");
      const offsetInputs = root.querySelectorAll("[data-cita-reminder-offset]");
    
      const isReminderActive = () => {
        const store = loadReminders();
        const entry = reservaId ? store[reservaId] : null;
        return !!(entry && Array.isArray(entry.offsets) && entry.offsets.length);
      };
    
      const setRemStatus = (msg, type) => {
        if (!(remStatus instanceof HTMLElement)) {
          return;
        }
        if (!msg) {
          remStatus.hidden = true;
          remStatus.textContent = "";
          remStatus.classList.remove("is-ok", "is-error");
          return;
        }
        remStatus.hidden = false;
        remStatus.textContent = msg;
        remStatus.classList.toggle("is-ok", type === "ok");
        remStatus.classList.toggle("is-error", type === "error");
      };
    
      const setShareStatus = (msg, type) => {
        if (!(shareStatus instanceof HTMLElement)) {
          return;
        }
        if (!msg) {
          shareStatus.hidden = true;
          shareStatus.textContent = "";
          shareStatus.classList.remove("is-ok", "is-error");
          return;
        }
        shareStatus.hidden = false;
        shareStatus.textContent = msg;
        shareStatus.classList.toggle("is-ok", type === "ok");
        shareStatus.classList.toggle("is-error", type === "error");
      };
    
      const syncReminderUi = () => {
        const store = loadReminders();
        const entry = reservaId ? store[reservaId] : null;
        const active = !!(entry && Array.isArray(entry.offsets) && entry.offsets.length);
    
        if (remToggleLabel instanceof HTMLElement) {
          remToggleLabel.textContent = active ? "Desactivar" : "Activar";
        }
        if (remToggleIcon instanceof HTMLElement) {
          remToggleIcon.className = active
            ? "ti ti-bell-off"
            : "ti ti-bell-ringing";
        }
        if (remToggle instanceof HTMLButtonElement) {
          remToggle.classList.toggle("cliente-cita-tools__btn--solid", !active);
          remToggle.classList.toggle("cliente-cita-tools__btn--danger", active);
          remToggle.setAttribute(
            "aria-pressed",
            active ? "true" : "false"
          );
        }
    
        if (active) {
          offsetInputs.forEach((input) => {
            if (!(input instanceof HTMLInputElement)) {
              return;
            }
            input.checked = entry.offsets.map(String).includes(input.value);
          });
          setRemStatus(
            `Recordatorios activos (${entry.offsets.length}).`,
            "ok"
          );
        } else {
          setRemStatus("", null);
        }
      };
    
      if (toggle instanceof HTMLButtonElement && panel instanceof HTMLElement) {
        toggle.addEventListener("click", () => {
          const open = panel.hidden;
          panel.hidden = !open;
          root.classList.toggle("is-open", open);
          toggle.setAttribute("aria-expanded", open ? "true" : "false");
        });
      }
    
      if (remToggle instanceof HTMLButtonElement) {
        remToggle.addEventListener("click", async () => {
          if (!reservaId || !startIso) {
            setRemStatus("No se pudo programar esta cita.", "error");
            return;
          }
    
          // Ya activos → desactivar
          if (isReminderActive()) {
            const store = loadReminders();
            delete store[reservaId];
            saveReminders(store);
            syncReminderUi();
            setRemStatus("Recordatorios desactivados.", "ok");
            return;
          }
    
          // Activar
          const offsets = [];
          offsetInputs.forEach((input) => {
            if (input instanceof HTMLInputElement && input.checked) {
              offsets.push(Number(input.value));
            }
          });
          if (offsets.length === 0) {
            setRemStatus("Elige al menos un tiempo de recordatorio.", "error");
            return;
          }
    
          if (!("Notification" in window)) {
            setRemStatus("Este navegador no soporta notificaciones.", "error");
            return;
          }
    
          let permission = Notification.permission;
          if (permission === "default") {
            permission = await Notification.requestPermission();
          }
          if (permission !== "granted") {
            setRemStatus(
              "Activa las notificaciones del navegador para recibir recordatorios.",
              "error"
            );
            return;
          }
    
          const store = loadReminders();
          store[reservaId] = {
            title,
            start: startIso,
            offsets,
            fired: {},
          };
          saveReminders(store);
          syncReminderUi();
          setRemStatus("Recordatorios activados en este dispositivo.", "ok");
          processDueReminders();
        });
      }
    
      const copyBtn = root.querySelector("[data-cita-copy]");
      if (copyBtn instanceof HTMLButtonElement) {
        copyBtn.addEventListener("click", async () => {
          const text = shareUrl || window.location.href;
          try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
              await navigator.clipboard.writeText(text);
            } else {
              const ta = document.createElement("textarea");
              ta.value = text;
              document.body.appendChild(ta);
              ta.select();
              document.execCommand("copy");
              ta.remove();
            }
            setShareStatus("Enlace copiado.", "ok");
          } catch (e) {
            setShareStatus("No se pudo copiar el enlace.", "error");
          }
        });
      }
    
      syncReminderUi();
    });
    
    // Preferencias: selección de estilo de referencia
    const prefRoot = document.querySelector("[data-cliente-pref]");
    if (prefRoot instanceof HTMLElement) {
      const estilosRoot = prefRoot.querySelector("[data-pref-estilos]");
      const estiloInput = prefRoot.querySelector("[data-pref-estilo-input]");
    
      if (estilosRoot instanceof HTMLElement) {
        estilosRoot.addEventListener("click", (event) => {
          const target = event.target;
          if (!(target instanceof Element)) {
            return;
          }
          const btn = target.closest("[data-estilo-id]");
          if (!(btn instanceof HTMLButtonElement)) {
            return;
          }
    
          estilosRoot.querySelectorAll("[data-estilo-id]").forEach((el) => {
            if (!(el instanceof HTMLButtonElement)) {
              return;
            }
            const active = el === btn;
            el.classList.toggle("is-selected", active);
            el.setAttribute("aria-selected", active ? "true" : "false");
          });
    
          if (estiloInput instanceof HTMLInputElement) {
            estiloInput.value = btn.getAttribute("data-estilo-id") || "";
          }
        });
      }
    }
    
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', yuniorrojasInit_cuenta);
  } else {
    yuniorrojasInit_cuenta();
  }
})();
