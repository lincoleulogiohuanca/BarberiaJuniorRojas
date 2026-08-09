document.addEventListener("DOMContentLoaded", () => {
  const el = document.getElementById("jr-mapa");
  if (!el || typeof L === "undefined" || typeof yuniorrojasMapa === "undefined") {
    return;
  }

  const lat = Number(yuniorrojasMapa.lat);
  const lng = Number(yuniorrojasMapa.lng);
  const zoom = Number(yuniorrojasMapa.zoom) || 17;
  const initialView = { center: [lat, lng], zoom };

  if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
    return;
  }

  const map = L.map(el, {
    scrollWheelZoom: true,
    doubleClickZoom: true,
    touchZoom: true,
    boxZoom: true,
    keyboard: true,
    zoomControl: false,
    attributionControl: true,
  }).setView(initialView.center, initialView.zoom);

  L.control
    .zoom({
      position: "topright",
      zoomInTitle: "Acercar",
      zoomOutTitle: "Alejar",
    })
    .addTo(map);

  const ResetControl = L.Control.extend({
    options: { position: "topright" },
    onAdd() {
      const container = L.DomUtil.create("div", "leaflet-bar contacto-mapa__reset");
      const button = L.DomUtil.create("a", "contacto-mapa__reset-btn", container);

      button.href = "#";
      button.title = "Volver a la ubicación";
      button.setAttribute("role", "button");
      button.setAttribute("aria-label", "Volver a la ubicación");
      button.innerHTML = "&#8634;";

      L.DomEvent.disableClickPropagation(container);
      L.DomEvent.on(button, "click", (event) => {
        L.DomEvent.preventDefault(event);
        map.setView(initialView.center, initialView.zoom, { animate: true });
      });

      return container;
    },
  });

  map.addControl(new ResetControl());

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

  L.marker([lat, lng]).addTo(map).bindPopup(popupHtml).openPopup();

  requestAnimationFrame(() => {
    map.invalidateSize();
  });
});
