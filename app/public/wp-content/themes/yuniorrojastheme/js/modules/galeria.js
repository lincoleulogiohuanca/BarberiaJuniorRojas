/** Auto-split from main.js — galeria */
(function () {
  function yuniorrojasInit_galeria() {
    // Lightbox galería (imagen efímera: no reutiliza ni vacía la miniatura de la grilla)
    const lightbox = document.querySelector("[data-lightbox]");
    const lightboxFigure = document.querySelector("[data-lightbox-figure]");
    const lightboxCaption = document.querySelector("[data-lightbox-caption]");
    let lightboxTrigger = null;
    
    const restoreGalleryThumb = (trigger) => {
      if (!(trigger instanceof HTMLElement)) {
        return;
      }
    
      const thumb = trigger.querySelector(".galeria-item__img");
      if (!(thumb instanceof HTMLImageElement)) {
        return;
      }
    
      const thumbSrc = trigger.getAttribute("data-thumb-src") || "";
      if (!thumbSrc) {
        return;
      }
    
      thumb.loading = "eager";
      thumb.removeAttribute("srcset");
      thumb.removeAttribute("sizes");
      thumb.src = thumbSrc;
      thumb.style.visibility = "visible";
      thumb.style.opacity = "1";
    };
    
    const openLightbox = (src, caption, trigger) => {
      if (!(lightbox instanceof HTMLElement) || !(lightboxFigure instanceof HTMLElement) || !src) {
        return;
      }
    
      lightboxTrigger = trigger instanceof HTMLElement ? trigger : null;
    
      const previous = lightboxFigure.querySelector(".lightbox__img");
      if (previous) {
        previous.remove();
      }
    
      const img = document.createElement("img");
      img.className = "lightbox__img";
      img.alt = caption || "";
      img.decoding = "async";
      // URL distinta a la miniatura para evitar que el browser comparta/degrade el bitmap.
      img.src = src + (src.includes("?") ? "&" : "?") + "lightbox=1";
    
      const captionEl = lightboxFigure.querySelector("[data-lightbox-caption]");
      if (captionEl) {
        lightboxFigure.insertBefore(img, captionEl);
      } else {
        lightboxFigure.appendChild(img);
      }
    
      if (lightboxCaption instanceof HTMLElement) {
        lightboxCaption.textContent = caption || "";
      }
    
      lightbox.hidden = false;
      document.body.classList.add("is-lightbox-open");
    
      const closeBtn = lightbox.querySelector(".lightbox__close");
      if (closeBtn instanceof HTMLElement) {
        closeBtn.focus();
      }
    };
    
    const closeLightbox = () => {
      if (!(lightbox instanceof HTMLElement)) {
        return;
      }
    
      lightbox.hidden = true;
      document.body.classList.remove("is-lightbox-open");
    
      const activeImg = lightboxFigure instanceof HTMLElement
        ? lightboxFigure.querySelector(".lightbox__img")
        : null;
      if (activeImg) {
        activeImg.remove();
      }
    
      if (lightboxCaption instanceof HTMLElement) {
        lightboxCaption.textContent = "";
      }
    
      restoreGalleryThumb(lightboxTrigger);
      if (lightboxTrigger instanceof HTMLElement) {
        lightboxTrigger.blur();
      }
      lightboxTrigger = null;
    };
    
    if (lightbox instanceof HTMLElement) {
      lightbox.addEventListener("click", (event) => {
        if (event.target.closest("[data-lightbox-close]")) {
          closeLightbox();
        }
      });
    
      document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !lightbox.hidden) {
          closeLightbox();
        }
      });
    }
    
    // Galería filters + paginación (10 por página)
    const filterRoot = document.querySelector("[data-galeria-filters]");
    const galleryRoot = document.querySelector("[data-galeria-root]");
    
    if (filterRoot && galleryRoot && window.yuniorrojasTheme && yuniorrojasTheme.restGaleria) {
      let requestId = 0;
      let state = {
        filter: "*",
        group: "tag",
        page: 1,
      };
    
      const loadGaleria = async () => {
        const current = ++requestId;
        galleryRoot.classList.add("is-loading");
    
        const params = new URLSearchParams();
        params.set("page", String(state.page));
    
        if (state.filter && state.filter !== "*") {
          if (state.group === "tag") {
            params.set("etiqueta", state.filter);
          } else {
            params.set("categoria", state.filter);
          }
        }
    
        const url = `${yuniorrojasTheme.restGaleria}?${params.toString()}`;
    
        try {
          const response = await fetch(url, {
            headers: {
              Accept: "application/json",
              "X-WP-Nonce": yuniorrojasTheme.restNonce || "",
            },
          });
    
          if (!response.ok) {
            throw new Error("No se pudo cargar la galería");
          }
    
          const data = await response.json();
          if (current !== requestId) {
            return;
          }
    
          galleryRoot.innerHTML = data.html || "";
          state.page = data.page || state.page;
        } catch (error) {
          if (current === requestId) {
            galleryRoot.innerHTML = '<p class="galeria-grid__empty">No se pudo cargar la galería.</p>';
          }
        } finally {
          if (current === requestId) {
            galleryRoot.classList.remove("is-loading");
          }
        }
      };
    
      filterRoot.addEventListener("click", (event) => {
        const button = event.target.closest("[data-filter]");
        if (!button) {
          return;
        }
    
        state.filter = button.getAttribute("data-filter") || "*";
        state.group = button.classList.contains("galeria-filters__tag") ? "tag" : "cat";
        state.page = 1;
    
        filterRoot.querySelectorAll("[data-filter]").forEach((el) => {
          el.classList.toggle("is-active", el === button);
        });
    
        loadGaleria();
      });
    
      galleryRoot.addEventListener("click", (event) => {
        const openBtn = event.target.closest("[data-lightbox-open]");
        if (openBtn instanceof HTMLElement && !openBtn.disabled) {
          const src = openBtn.getAttribute("data-lightbox-src") || "";
          const caption = openBtn.getAttribute("data-lightbox-caption") || "";
          const thumb = openBtn.querySelector(".galeria-item__img");
          const fallbackSrc =
            openBtn.getAttribute("data-thumb-src") ||
            (thumb instanceof HTMLImageElement ? thumb.currentSrc || thumb.getAttribute("src") || "" : "");
          const finalSrc = src || fallbackSrc;
          if (finalSrc) {
            openLightbox(finalSrc, caption, openBtn);
          }
          return;
        }
    
        const pageBtn = event.target.closest("[data-galeria-page]");
        if (!pageBtn || pageBtn.disabled) {
          return;
        }
    
        const nextPage = Number(pageBtn.getAttribute("data-galeria-page") || "1");
        if (!Number.isFinite(nextPage) || nextPage < 1 || nextPage === state.page) {
          return;
        }
    
        state.page = nextPage;
        loadGaleria();
        galleryRoot.scrollIntoView({ behavior: "smooth", block: "start" });
      });
    }
    
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', yuniorrojasInit_galeria);
  } else {
    yuniorrojasInit_galeria();
  }
})();
