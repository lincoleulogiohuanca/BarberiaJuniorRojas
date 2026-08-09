/**
 * Agenda calendar — UI helpers (scroll al “ahora”).
 */
(function () {
  "use strict";

  function init() {
    const board = document.querySelector(".jr-cal__board");
    const now = document.querySelector(".jr-cal__now");
    if (!(board instanceof HTMLElement) || !(now instanceof HTMLElement)) {
      return;
    }
    const top = Number.parseFloat(now.style.top || "0");
    if (!Number.isFinite(top) || top <= 0) {
      return;
    }
    // Centra la hora actual en el viewport del board.
    const target = Math.max(0, top - board.clientHeight * 0.3);
    board.scrollTop = target;
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
