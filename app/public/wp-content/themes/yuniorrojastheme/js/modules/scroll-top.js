/** Auto-split from main.js — scroll-top */
(function () {
  function yuniorrojasInit_scroll_top() {
    // Scroll to top (páginas públicas; no se renderiza en panel cliente)
    const scrollTopBtn = document.querySelector("[data-scroll-top]");
    if (scrollTopBtn instanceof HTMLButtonElement) {
      const scrollThreshold = 320;
      let ticking = false;
    
      const updateScrollTopVisibility = () => {
        const visible = window.scrollY > scrollThreshold;
        scrollTopBtn.classList.toggle("is-visible", visible);
        if (visible) {
          scrollTopBtn.removeAttribute("hidden");
        } else {
          scrollTopBtn.setAttribute("hidden", "");
        }
        ticking = false;
      };
    
      const onScroll = () => {
        if (ticking) {
          return;
        }
        ticking = true;
        window.requestAnimationFrame(updateScrollTopVisibility);
      };
    
      window.addEventListener("scroll", onScroll, { passive: true });
      updateScrollTopVisibility();
    
      scrollTopBtn.addEventListener("click", () => {
        window.scrollTo({ top: 0, behavior: "smooth" });
      });
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', yuniorrojasInit_scroll_top);
  } else {
    yuniorrojasInit_scroll_top();
  }
})();
