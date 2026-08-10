/**
 * Selector de ubicación en admin Contacto:
 * pin ↔ lat/lng/zoom ↔ dirección (Nominatim reverse + forward).
 *
 * Al reverse se guarda un cache en memoria de dirección → coords,
 * para que al reescribir la misma texto el pin vuelva sin depender de search.
 */
(function () {
  "use strict";

  function parseNum(value, fallback) {
    const n = Number(value);
    return Number.isFinite(n) ? n : fallback;
  }

  function clampZoom(z) {
    const n = Math.round(parseNum(z, 17));
    return Math.min(19, Math.max(1, n));
  }

  function formatCoord(n) {
    return Number(n).toFixed(6);
  }

  /**
   * Clave comparable de dirección (minúsculas, sin acentos, sin ruído tipográfico).
   * @param {string} text
   * @returns {string}
   */
  function normalizeAddressKey(text) {
    return String(text || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/n[°ºoa\.]\s*/gi, " ")
      .replace(/\bnro\.?\b/gi, " ")
      .replace(/\bn[úu]mero\b/gi, " ")
      .replace(/\bjir[oó]n\b/gi, "jr")
      .replace(/\bjr\b\.?/gi, "jr")
      .replace(/[–—−]/g, "-")
      .replace(/[^a-z0-9]+/g, " ")
      .replace(/\s+/g, " ")
      .trim();
  }

  /**
   * Variantes de consulta para Nominatim (el reverse formatea distinto al search).
   * @param {string} raw
   * @returns {string[]}
   */
  function buildSearchQueries(raw) {
    const original = String(raw || "").trim();
    if (!original) {
      return [];
    }

    let cleaned = original
      .replace(/[–—−]/g, "-")
      .replace(/N[°º]\s*/gi, " ")
      .replace(/\bNro\.?\s*/gi, " ")
      .replace(/,/g, " ")
      .replace(/\s*-\s*/g, " ")
      .replace(/\s+/g, " ")
      .trim();

    // Quita partes consecutivas repetidas (p.ej. "Huánuco Huánuco").
    cleaned = cleaned
      .split(/\s+/)
      .filter(function (part, i, arr) {
        return i === 0 || part.toLowerCase() !== arr[i - 1].toLowerCase();
      })
      .join(" ");

    const noDupRegion = cleaned.replace(
      /\b(Hu[aá]nuco)\s+\1\b/gi,
      "$1"
    );

    const streetCity = noDupRegion
      .replace(/\s*Per[uú]\s*$/i, "")
      .replace(/\s+/g, " ")
      .trim();

    // Primeros tokens hasta antes del penúltimo/último si hay muchos segmentos.
    const dashParts = original
      .split(/\s*[-–—]\s*/)
      .map(function (p) {
        return p.trim();
      })
      .filter(Boolean);

    const fromDash =
      dashParts.length >= 2
        ? dashParts.slice(0, Math.min(3, dashParts.length)).join(" ") +
          (dashParts.length > 2 ? "" : "")
        : "";

    const jrNormalized = noDupRegion
      .replace(/\bJir[oó]n\b/gi, "Jr.")
      .replace(/\s+/g, " ")
      .trim();

    const withPeru = /per[uú]/i.test(noDupRegion)
      ? noDupRegion
      : noDupRegion + " Perú";

    const candidates = [
      original,
      cleaned,
      noDupRegion,
      streetCity,
      jrNormalized,
      withPeru,
      fromDash,
      dashParts.length >= 1 ? dashParts[0] + " Huánuco Perú" : "",
      dashParts.length >= 2
        ? dashParts[0] + " " + dashParts[1] + " Huánuco Perú"
        : "",
    ];

    const seen = {};
    const out = [];
    candidates.forEach(function (q) {
      const t = String(q || "")
        .replace(/\s+/g, " ")
        .trim();
      if (t.length < 5) {
        return;
      }
      const key = normalizeAddressKey(t);
      if (!key || seen[key]) {
        return;
      }
      seen[key] = true;
      out.push(t);
    });
    return out;
  }

  /**
   * Arma una dirección legible desde el detalle de Nominatim.
   * @param {Record<string, unknown>} data
   * @returns {string}
   */
  function formatAddressFromNominatim(data) {
    if (!data || typeof data !== "object") {
      return "";
    }

    const a = data.address && typeof data.address === "object" ? data.address : {};
    const road =
      typeof a.road === "string"
        ? a.road
        : typeof a.pedestrian === "string"
          ? a.pedestrian
          : "";
    const house = typeof a.house_number === "string" ? a.house_number : "";
    const street = house && road ? road + " N° " + house : road || house;

    const barrio =
      (typeof a.suburb === "string" && a.suburb) ||
      (typeof a.neighbourhood === "string" && a.neighbourhood) ||
      (typeof a.quarter === "string" && a.quarter) ||
      "";

    const city =
      (typeof a.city === "string" && a.city) ||
      (typeof a.town === "string" && a.town) ||
      (typeof a.village === "string" && a.village) ||
      (typeof a.municipality === "string" && a.municipality) ||
      "";

    const state = typeof a.state === "string" ? a.state : "";
    const country = typeof a.country === "string" ? a.country : "";

    // Evita "Huánuco - Huánuco" cuando city === state.
    const parts = [];
    if (street) {
      parts.push(street);
    }
    if (barrio) {
      parts.push(barrio);
    }
    if (city) {
      parts.push(city);
    }
    if (state && normalizeAddressKey(state) !== normalizeAddressKey(city)) {
      parts.push(state);
    }
    if (country) {
      parts.push(country);
    }

    if (parts.length > 0) {
      return parts.join(" - ");
    }

    return typeof data.display_name === "string" ? data.display_name : "";
  }

  function initMapaAdmin() {
    const el = document.getElementById("yuniorrojas-mapa-admin");
    const latInput = document.getElementById("yuniorrojas_mapa_lat");
    const lngInput = document.getElementById("yuniorrojas_mapa_lng");
    const zoomInput = document.getElementById("yuniorrojas_mapa_zoom");
    const dirInput = document.getElementById("yuniorrojas_direccion");
    const dirStatus = document.getElementById("yuniorrojas-mapa-dir-status");

    if (
      !el ||
      !(latInput instanceof HTMLInputElement) ||
      !(lngInput instanceof HTMLInputElement) ||
      !(zoomInput instanceof HTMLInputElement) ||
      typeof L === "undefined"
    ) {
      return;
    }

    let lat = parseNum(latInput.value, -9.9297);
    let lng = parseNum(lngInput.value, -76.2422);
    let zoom = clampZoom(zoomInput.value);
    let syncing = false;
    let reverseTimer = 0;
    let forwardTimer = 0;
    let reverseSeq = 0;
    let forwardSeq = 0;
    /** @type {Record<string, {lat:number,lng:number,zoom:number}>} */
    const addressCache = Object.create(null);
    let addressSynced = dirInput instanceof HTMLInputElement ? dirInput.value.trim() : "";

    // Si ya hay dirección + coords cargadas, cachear al iniciar.
    if (addressSynced) {
      addressCache[normalizeAddressKey(addressSynced)] = {
        lat: lat,
        lng: lng,
        zoom: zoom,
      };
    }

    const map = L.map(el, {
      scrollWheelZoom: true,
      doubleClickZoom: true,
      touchZoom: true,
      zoomControl: true,
    }).setView([lat, lng], zoom);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    const marker = L.marker([lat, lng], {
      draggable: true,
      autoPan: true,
      title: "Arrastra para ubicar el estudio",
    }).addTo(map);

    function setDirStatus(text) {
      if (dirStatus instanceof HTMLElement && text) {
        dirStatus.textContent = text;
      }
    }

    function writeFields(nextLat, nextLng, nextZoom) {
      syncing = true;
      latInput.value = formatCoord(nextLat);
      lngInput.value = formatCoord(nextLng);
      if (typeof nextZoom === "number" && Number.isFinite(nextZoom)) {
        zoomInput.value = String(clampZoom(nextZoom));
      }
      syncing = false;
    }

    function rememberAddress(text, nextLat, nextLng, nextZoom) {
      const key = normalizeAddressKey(text);
      if (!key || !Number.isFinite(nextLat) || !Number.isFinite(nextLng)) {
        return;
      }
      addressCache[key] = {
        lat: nextLat,
        lng: nextLng,
        zoom: clampZoom(nextZoom || zoom),
      };
    }

    function lookupCachedAddress(text) {
      const key = normalizeAddressKey(text);
      if (!key) {
        return null;
      }
      if (addressCache[key]) {
        return addressCache[key];
      }
      // Coincidencia aproximada: la clave escrita contine o es contenida por una cacheada.
      const keys = Object.keys(addressCache);
      for (let i = 0; i < keys.length; i++) {
        const k = keys[i];
        if (k === key || k.indexOf(key) !== -1 || key.indexOf(k) !== -1) {
          if (Math.min(k.length, key.length) >= 12) {
            return addressCache[k];
          }
        }
      }
      return null;
    }

    function movePinTo(nextLat, nextLng, nextZoom, animate) {
      lat = nextLat;
      lng = nextLng;
      if (typeof nextZoom === "number" && Number.isFinite(nextZoom)) {
        zoom = clampZoom(nextZoom);
      }
      writeFields(lat, lng, zoom);
      marker.setLatLng([lat, lng]);
      map.setView([lat, lng], zoom, { animate: Boolean(animate) });
    }

    /**
     * Reverse: pin → dirección (+ cache).
     * @param {number} nextLat
     * @param {number} nextLng
     */
    function reverseGeocodeAddress(nextLat, nextLng) {
      if (!(dirInput instanceof HTMLInputElement)) {
        return;
      }

      window.clearTimeout(reverseTimer);
      reverseTimer = window.setTimeout(function () {
        const seq = ++reverseSeq;
        setDirStatus("Buscando dirección…");

        const url =
          "https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=18&accept-language=es&lat=" +
          encodeURIComponent(String(nextLat)) +
          "&lon=" +
          encodeURIComponent(String(nextLng));

        fetch(url, {
          method: "GET",
          headers: { Accept: "application/json" },
        })
          .then(function (res) {
            if (!res.ok) {
              throw new Error("geocode " + res.status);
            }
            return res.json();
          })
          .then(function (data) {
            if (seq !== reverseSeq) {
              return;
            }
            const formatted = formatAddressFromNominatim(data);
            if (formatted) {
              dirInput.value = formatted;
              addressSynced = formatted.trim();
              rememberAddress(formatted, nextLat, nextLng, zoom);
              // También guarda display_name crudo si difiere.
              if (typeof data.display_name === "string") {
                rememberAddress(data.display_name, nextLat, nextLng, zoom);
              }
              setDirStatus("Dirección actualizada desde el mapa (puedes editarla).");
            } else {
              setDirStatus("No se encontró una dirección clara; edítala a mano.");
            }
          })
          .catch(function () {
            if (seq !== reverseSeq) {
              return;
            }
            setDirStatus(
              "No se pudo obtener la dirección (red). Lat/lng quedaron; escribe la dirección a mano."
            );
          });
      }, 350);
    }

    /**
     * @param {string} query
     * @param {boolean} restrictCountry
     * @returns {Promise<Array<Record<string, unknown>>>}
     */
    function nominatimSearch(query, restrictCountry) {
      let url =
        "https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=5&accept-language=es&q=" +
        encodeURIComponent(query);
      if (restrictCountry) {
        url += "&countrycodes=pe";
      }

      return fetch(url, {
        method: "GET",
        headers: { Accept: "application/json" },
      }).then(function (res) {
        if (!res.ok) {
          throw new Error("search " + res.status);
        }
        return res.json();
      });
    }

    /**
     * Prueba variantes de texto hasta obtener un hit.
     * @param {string[]} queries
     * @returns {Promise<{lat:number,lng:number}|null>}
     */
    function searchWithFallbacks(queries) {
      let chain = Promise.resolve(null);

      queries.forEach(function (q) {
        chain = chain.then(function (found) {
          if (found) {
            return found;
          }
          return nominatimSearch(q, true).then(function (results) {
            if (Array.isArray(results) && results.length > 0) {
              return results[0];
            }
            // Sin filtro de país si no hubo acierto.
            return nominatimSearch(q, false).then(function (results2) {
              if (Array.isArray(results2) && results2.length > 0) {
                return results2[0];
              }
              return null;
            });
          });
        });
      });

      return chain.then(function (hit) {
        if (!hit) {
          return null;
        }
        const nextLat = parseNum(hit.lat, NaN);
        const nextLng = parseNum(hit.lon, NaN);
        if (!Number.isFinite(nextLat) || !Number.isFinite(nextLng)) {
          return null;
        }
        return { lat: nextLat, lng: nextLng, raw: hit };
      });
    }

    /**
     * Forward: dirección → pin (+ lat/lng).
     * @param {boolean} immediate
     */
    function forwardGeocodeAddress(immediate) {
      if (!(dirInput instanceof HTMLInputElement)) {
        return;
      }

      window.clearTimeout(forwardTimer);

      const run = function () {
        const query = dirInput.value.trim();
        if (query.length < 5) {
          if (query.length > 0) {
            setDirStatus("Escribe una dirección más completa para ubicar el pin.");
          }
          return;
        }

        if (query === addressSynced) {
          const cachedSame = lookupCachedAddress(query);
          if (cachedSame) {
            window.clearTimeout(reverseTimer);
            reverseSeq += 1;
            movePinTo(cachedSame.lat, cachedSame.lng, cachedSame.zoom, true);
            setDirStatus("Ubicación recuperada (dirección ya conocida).");
          }
          return;
        }

        // 1) Cache de pins que generamos con reverse (caso re-pegar la misma dirección).
        const cached = lookupCachedAddress(query);
        if (cached) {
          window.clearTimeout(reverseTimer);
          reverseSeq += 1;
          movePinTo(cached.lat, cached.lng, cached.zoom, true);
          addressSynced = query;
          rememberAddress(query, cached.lat, cached.lng, cached.zoom);
          setDirStatus("Mapa restaurado a esa dirección (memoria del pin).");
          return;
        }

        const seq = ++forwardSeq;
        setDirStatus("Buscando en el mapa…");

        const queries = buildSearchQueries(query);

        searchWithFallbacks(queries)
          .then(function (hit) {
            if (seq !== forwardSeq) {
              return;
            }
            if (!hit) {
              setDirStatus(
                "No se encontró esa dirección en el buscador. Mueve el pin o usa menos guiones / “Jr.” abreviado."
              );
              return;
            }

            window.clearTimeout(reverseTimer);
            reverseSeq += 1;

            movePinTo(hit.lat, hit.lng, Math.max(zoom, 16), true);
            addressSynced = query;
            rememberAddress(query, hit.lat, hit.lng, zoom);
            if (hit.raw) {
              const nicer = formatAddressFromNominatim(hit.raw);
              if (nicer) {
                rememberAddress(nicer, hit.lat, hit.lng, zoom);
              }
              if (typeof hit.raw.display_name === "string") {
                rememberAddress(hit.raw.display_name, hit.lat, hit.lng, zoom);
              }
            }

            setDirStatus("Mapa actualizado desde la dirección. Revisa el pin y guarda.");
          })
          .catch(function () {
            if (seq !== forwardSeq) {
              return;
            }
            setDirStatus(
              "No se pudo buscar la dirección (red). Mueve el pin o prueba de nuevo."
            );
          });
      };

      if (immediate) {
        run();
      } else {
        forwardTimer = window.setTimeout(run, 700);
      }
    }

    function applyFromMarker(ll) {
      lat = ll.lat;
      lng = ll.lng;
      writeFields(lat, lng);
      reverseGeocodeAddress(lat, lng);
    }

    marker.on("dragend", function () {
      applyFromMarker(marker.getLatLng());
    });

    map.on("click", function (event) {
      marker.setLatLng(event.latlng);
      applyFromMarker(event.latlng);
    });

    map.on("zoomend", function () {
      if (syncing) {
        return;
      }
      zoom = clampZoom(map.getZoom());
      syncing = true;
      zoomInput.value = String(zoom);
      syncing = false;
    });

    function moveMapFromCoordInputs() {
      if (syncing) {
        return;
      }
      const nextLat = parseNum(latInput.value, lat);
      const nextLng = parseNum(lngInput.value, lng);
      const nextZoom = clampZoom(zoomInput.value);
      if (!Number.isFinite(nextLat) || !Number.isFinite(nextLng)) {
        return;
      }
      movePinTo(nextLat, nextLng, nextZoom, false);
      reverseGeocodeAddress(lat, lng);
    }

    ["change", "blur"].forEach(function (evt) {
      latInput.addEventListener(evt, moveMapFromCoordInputs);
      lngInput.addEventListener(evt, moveMapFromCoordInputs);
      zoomInput.addEventListener(evt, function () {
        if (syncing) {
          return;
        }
        zoom = clampZoom(zoomInput.value);
        map.setZoom(zoom, { animate: false });
      });
    });

    if (dirInput instanceof HTMLInputElement) {
      dirInput.addEventListener("blur", function () {
        forwardGeocodeAddress(true);
      });
      dirInput.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
          event.preventDefault();
          forwardGeocodeAddress(true);
        }
      });
      dirInput.addEventListener("input", function () {
        forwardGeocodeAddress(false);
      });
    }

    writeFields(lat, lng, zoom);

    window.setTimeout(function () {
      map.invalidateSize();
    }, 80);
    window.setTimeout(function () {
      map.invalidateSize();
    }, 300);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initMapaAdmin);
  } else {
    initMapaAdmin();
  }
})();
