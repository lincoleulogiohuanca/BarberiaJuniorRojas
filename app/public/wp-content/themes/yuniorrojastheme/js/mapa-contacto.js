document.addEventListener("DOMContentLoaded", () => {
  const el = document.getElementById("jr-mapa");
  if (!el || typeof L === "undefined" || typeof yuniorrojasMapa === "undefined") {
    return;
  }

  const lat = Number(yuniorrojasMapa.lat);
  const lng = Number(yuniorrojasMapa.lng);
  const zoom = Number(yuniorrojasMapa.zoom) || 17;
  const center = [lat, lng];

  if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
    return;
  }

  const map = L.map(el, {
    center,
    zoom,
    // Vista fija: sin pan, zoom ni gestos.
    dragging: false,
    touchZoom: false,
    doubleClickZoom: false,
    scrollWheelZoom: false,
    boxZoom: false,
    keyboard: false,
    tap: false,
    bounceAtZoomLimits: false,
    zoomControl: false,
    attributionControl: true,
  });

  if (map.attributionControl) {
    map.attributionControl.setPosition("bottomleft");
  }

  // Evita arrastre en el propio contenedor (trackpads / algunos browsers).
  if (map.dragging && map.dragging.disable) {
    map.dragging.disable();
  }
  if (map.touchZoom && map.touchZoom.disable) {
    map.touchZoom.disable();
  }
  if (map.doubleClickZoom && map.doubleClickZoom.disable) {
    map.doubleClickZoom.disable();
  }
  if (map.scrollWheelZoom && map.scrollWheelZoom.disable) {
    map.scrollWheelZoom.disable();
  }
  if (map.boxZoom && map.boxZoom.disable) {
    map.boxZoom.disable();
  }
  if (map.keyboard && map.keyboard.disable) {
    map.keyboard.disable();
  }

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(map);

  const popupHtml = [
    "<strong>",
    yuniorrojasMapa.titulo || "Junior Rojas",
    "</strong>",
    yuniorrojasMapa.direccion
      ? `<br><span>${yuniorrojasMapa.direccion}</span>`
      : "",
  ].join("");

  L.marker(center).addTo(map).bindPopup(popupHtml).openPopup();

  const recenter = () => {
    map.invalidateSize({ animate: false });
    map.setView(center, zoom, { animate: false });
  };

  requestAnimationFrame(recenter);
  window.setTimeout(recenter, 120);
  window.setTimeout(recenter, 400);

  window.addEventListener("resize", recenter, { passive: true });
});
