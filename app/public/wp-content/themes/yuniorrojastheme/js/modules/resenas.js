/** Auto-split from main.js — resenas */
(function () {
  function yuniorrojasInit_resenas() {
    // Reseñas en ficha de servicio
    const resenasRoot = document.querySelector("[data-servicio-resenas]");
    if (resenasRoot instanceof HTMLElement) {
      const theme = window.yuniorrojasTheme || {};
      const servicioId = resenasRoot.getAttribute("data-servicio-id") || "";
      const base = String(theme.restResenasBase || "").replace(/\/$/, "");
      const likeBase = String(theme.restResenaLikeBase || "").replace(/\/$/, "");
      const nonce = String(theme.restNonce || "");
      const isLoggedIn = !!theme.isLoggedIn;
      const form = resenasRoot.querySelector("[data-resena-form]");
      const ratingInput = resenasRoot.querySelector("[data-resena-rating]");
      const textoInput = resenasRoot.querySelector("[data-resena-texto]");
      const submitBtn = resenasRoot.querySelector("[data-resena-submit]");
      const statusEl = resenasRoot.querySelector("[data-resena-status]");
      const starsRoot = resenasRoot.querySelector("[data-resena-stars]");
      const listEl = resenasRoot.querySelector("[data-resenas-list]");
    
      const escapeHtml = (value) => {
        return String(value)
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;");
      };
    
      const setStatus = (msg, type) => {
        if (!(statusEl instanceof HTMLElement)) {
          return;
        }
        if (!msg) {
          statusEl.hidden = true;
          statusEl.textContent = "";
          statusEl.classList.remove("is-ok", "is-error");
          return;
        }
        statusEl.hidden = false;
        statusEl.textContent = msg;
        statusEl.classList.toggle("is-ok", type === "ok");
        statusEl.classList.toggle("is-error", type === "error");
      };
    
      const paintStars = (value) => {
        if (!(starsRoot instanceof HTMLElement)) {
          return;
        }
        starsRoot.querySelectorAll("[data-star]").forEach((btn) => {
          if (!(btn instanceof HTMLElement)) {
            return;
          }
          const n = Number(btn.getAttribute("data-star") || "0");
          btn.classList.toggle("is-on", n > 0 && n <= value);
        });
      };
    
      if (starsRoot instanceof HTMLElement) {
        const initial =
          ratingInput instanceof HTMLInputElement
            ? Number(ratingInput.value || "5")
            : 5;
        paintStars(initial);
    
        starsRoot.addEventListener("click", (event) => {
          const target = event.target;
          if (!(target instanceof Element)) {
            return;
          }
          const btn = target.closest("[data-star]");
          if (!(btn instanceof HTMLElement)) {
            return;
          }
          const value = Number(btn.getAttribute("data-star") || "0");
          if (value < 1) {
            return;
          }
          if (ratingInput instanceof HTMLInputElement) {
            ratingInput.value = String(value);
          }
          paintStars(value);
        });
      }
    
      const starsHtml = (rating) => {
        let html = '<span class="servicio-resenas__stars" aria-hidden="true">';
        for (let i = 1; i <= 5; i += 1) {
          html +=
            '<span class="servicio-resenas__star' +
            (i <= rating ? " is-on" : "") +
            '"><i class="ti ti-star-filled"></i></span>';
        }
        html += "</span>";
        return html;
      };
    
      /** @param {Record<string, unknown>} resena */
      const likeHtml = (resena) => {
        const id = String(resena.id || "");
        const likes = Math.max(0, Number(resena.likes || 0));
        const liked = !!resena.liked;
        const puede = !!resena.puede_like;
        const propia = !!resena.es_propia;
        const icon = liked || (propia && likes > 0) ? "ti-heart-filled" : "ti-heart";
        const count =
          '<span class="servicio-resenas__like-count" data-like-count">' +
          escapeHtml(String(likes)) +
          "</span>";
    
        // Otras reseñas + sesión: botón activo.
        if (puede && id) {
          const label = liked ? "Quitar me gusta" : "Me gusta";
          return (
            '<button type="button" class="servicio-resenas__like' +
            (liked ? " is-liked" : "") +
            '" data-resena-like data-resena-id="' +
            escapeHtml(id) +
            '" aria-pressed="' +
            (liked ? "true" : "false") +
            '" aria-label="' +
            label +
            '" title="' +
            label +
            '"><i class="ti ' +
            icon +
            '" aria-hidden="true"></i>' +
            count +
            "</button>"
          );
        }
    
        // Propia o visitante: icono + contador sin acción.
        const title = propia
          ? "Me gusta recibidos"
          : "Inicia sesión para dar me gusta";
        const cls =
          "servicio-resenas__like is-static" +
          (likes > 0 || liked ? " is-liked" : "");
        return (
          '<span class="' +
          cls +
          '" title="' +
          title +
          '" aria-label="' +
          title +
          '"><i class="ti ' +
          icon +
          '" aria-hidden="true"></i>' +
          count +
          "</span>"
        );
      };
    
      /** @param {Record<string, unknown>} resena */
      const prependOrUpdateItem = (resena) => {
        if (!(listEl instanceof HTMLElement) || !resena) {
          return;
        }
        const empty = listEl.querySelector("[data-resenas-empty]");
        if (empty) {
          empty.remove();
        }
    
        const id = String(resena.id || "");
        let item = id
          ? listEl.querySelector(`[data-resena-id="${id}"]`)
          : null;
        if (!(item instanceof HTMLElement)) {
          item = document.createElement("article");
          item.className = "servicio-resenas__item";
          if (id) {
            item.setAttribute("data-resena-id", id);
          }
          listEl.prepend(item);
        }
    
        // Tras publicar: es tu reseña → icono visible, sin poder darte like.
        const payload = Object.assign({}, resena, {
          es_propia: true,
          puede_like: false,
          liked: false,
          likes: Number(resena.likes || 0),
        });
    
        item.innerHTML =
          '<div class="servicio-resenas__item-top">' +
          `<strong class="servicio-resenas__author">${escapeHtml(
            String(resena.nombre || "Cliente")
          )}</strong>` +
          `<time class="servicio-resenas__date">${escapeHtml(
            String(resena.fecha || "")
          )}</time>` +
          "</div>" +
          starsHtml(Number(resena.rating || 5)) +
          `<p class="servicio-resenas__text">${escapeHtml(
            String(resena.texto || "")
          )}</p>` +
          '<div class="servicio-resenas__item-foot">' +
          likeHtml(payload) +
          "</div>";
      };
    
      const updateSummary = (promedio, total) => {
        const summary = resenasRoot.querySelector("[data-resenas-summary]");
        if (!(summary instanceof HTMLElement)) {
          return;
        }
        if (total <= 0) {
          summary.innerHTML =
            '<p class="servicio-resenas__empty-summary" data-resenas-empty-summary>Sé el primero en calificar este servicio.</p>';
          return;
        }
        summary.innerHTML =
          starsHtml(Math.round(Number(promedio) || 0)) +
          `<span class="servicio-resenas__avg" data-resenas-avg>${Number(
            promedio
          ).toFixed(1)}</span>` +
          `<span class="servicio-resenas__count" data-resenas-count>(${total})</span>`;
      };
    
      /** @param {HTMLElement} el */
      const applyLikeState = (el, liked, likes) => {
        el.classList.toggle("is-liked", liked);
        el.setAttribute("aria-pressed", liked ? "true" : "false");
        const label = liked ? "Quitar me gusta" : "Me gusta";
        el.setAttribute("aria-label", label);
        el.setAttribute("title", label);
        const icon = el.querySelector(".ti");
        if (icon instanceof HTMLElement) {
          icon.className = liked ? "ti ti-heart-filled" : "ti ti-heart";
        }
        const countEl = el.querySelector("[data-like-count]");
        if (countEl instanceof HTMLElement) {
          countEl.textContent = String(Math.max(0, likes));
        }
      };
    
      if (listEl instanceof HTMLElement) {
        listEl.addEventListener("click", async (event) => {
          const target = event.target;
          if (!(target instanceof Element)) {
            return;
          }
          const btn = target.closest("[data-resena-like]");
          if (!(btn instanceof HTMLButtonElement)) {
            return;
          }
          if (!isLoggedIn) {
            const loginUrl = String(theme.loginUrl || "");
            if (loginUrl) {
              window.location.href = loginUrl;
            }
            return;
          }
          if (!likeBase) {
            return;
          }
          const resenaId = btn.getAttribute("data-resena-id") || "";
          if (!resenaId) {
            return;
          }
    
          btn.classList.add("is-loading");
          btn.disabled = true;
    
          try {
            const res = await fetch(`${likeBase}/${resenaId}/like`, {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                "X-WP-Nonce": nonce,
              },
              credentials: "same-origin",
              body: "{}",
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
              const msg =
                data?.message ||
                (res.status === 403
                  ? "No puedes dar like a tu propia reseña."
                  : "No se pudo registrar el me gusta.");
              throw new Error(String(msg));
            }
            applyLikeState(
              btn,
              !!data?.liked,
              Number(data?.likes ?? 0)
            );
          } catch (err) {
            window.alert(
              err instanceof Error && err.message
                ? err.message
                : "No se pudo registrar el me gusta."
            );
          } finally {
            btn.classList.remove("is-loading");
            btn.disabled = false;
          }
        });
      }
    
      if (form instanceof HTMLFormElement) {
        form.addEventListener("submit", async (event) => {
          event.preventDefault();
          if (!servicioId || !base) {
            setStatus("No se pudo enviar la reseña.", "error");
            return;
          }
          const rating =
            ratingInput instanceof HTMLInputElement
              ? Number(ratingInput.value || "0")
              : 0;
          const texto =
            textoInput instanceof HTMLTextAreaElement
              ? textoInput.value.trim()
              : "";
    
          if (rating < 1 || rating > 5) {
            setStatus("Elige una calificación de 1 a 5 estrellas.", "error");
            return;
          }
          if (texto.length < 8) {
            setStatus("Escribe un comentario de al menos 8 caracteres.", "error");
            return;
          }
    
          if (submitBtn instanceof HTMLButtonElement) {
            submitBtn.disabled = true;
          }
          setStatus("Publicando reseña…", null);
    
          try {
            const res = await fetch(`${base}/${servicioId}/resenas`, {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                "X-WP-Nonce": nonce,
              },
              credentials: "same-origin",
              body: JSON.stringify({ rating, texto }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
              const msg =
                data?.message || "No se pudo publicar la reseña.";
              throw new Error(String(msg));
            }
    
            if (data?.resena) {
              // Solo listar en público si ya está publicada.
              if (!data.pending && data.resena.status !== "pending") {
                prependOrUpdateItem(data.resena);
              }
            }
            updateSummary(data?.promedio ?? 0, data?.total ?? 0);
            setStatus(
              data?.message || "Reseña enviada. Gracias por tu opinión.",
              "ok"
            );
            if (submitBtn instanceof HTMLButtonElement) {
              submitBtn.textContent = "Guardar cambios";
            }
            const formTitle = form.querySelector(".servicio-resenas__form-title");
            if (formTitle instanceof HTMLElement) {
              formTitle.textContent = "Actualiza tu reseña";
            }
          } catch (err) {
            setStatus(
              err instanceof Error && err.message
                ? err.message
                : "No se pudo publicar la reseña.",
              "error"
            );
          } finally {
            if (submitBtn instanceof HTMLButtonElement) {
              submitBtn.disabled = false;
            }
          }
        });
      }
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', yuniorrojasInit_resenas);
  } else {
    yuniorrojasInit_resenas();
  }
})();
