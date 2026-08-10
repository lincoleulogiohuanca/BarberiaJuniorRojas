/** Auto-split from main.js — faq */
(function () {
  function yuniorrojasInit_faq() {
    // FAQ: acordeón solo en móvil (uno abierto a la vez)
    const faqRoot = document.querySelector("[data-faq]");
    
    if (faqRoot) {
      const faqMobile = window.matchMedia("(max-width: 767px)");
    
      const closeFaqItem = (item) => {
        item.classList.remove("is-open");
        const trigger = item.querySelector("[data-faq-trigger]");
        const panel = item.querySelector("[data-faq-panel]");
        if (trigger) {
          trigger.setAttribute("aria-expanded", "false");
        }
        if (panel) {
          panel.setAttribute("hidden", "");
        }
      };
    
      const openFaqItem = (item) => {
        item.classList.add("is-open");
        const trigger = item.querySelector("[data-faq-trigger]");
        const panel = item.querySelector("[data-faq-panel]");
        if (trigger) {
          trigger.setAttribute("aria-expanded", "true");
        }
        if (panel) {
          panel.removeAttribute("hidden");
        }
      };
    
      const syncFaqForViewport = () => {
        faqRoot.querySelectorAll("[data-faq-item]").forEach((item) => {
          if (faqMobile.matches) {
            closeFaqItem(item);
          } else {
            openFaqItem(item);
          }
        });
      };
    
      faqRoot.addEventListener("click", (event) => {
        const trigger = event.target.closest("[data-faq-trigger]");
        if (!trigger || !faqMobile.matches) {
          return;
        }
    
        const item = trigger.closest("[data-faq-item]");
        if (!(item instanceof HTMLElement)) {
          return;
        }
    
        const wasOpen = item.classList.contains("is-open");
    
        faqRoot.querySelectorAll("[data-faq-item].is-open").forEach((openItem) => {
          closeFaqItem(openItem);
        });
    
        if (!wasOpen) {
          openFaqItem(item);
        }
      });
    
      syncFaqForViewport();
      faqMobile.addEventListener("change", syncFaqForViewport);
    }
    
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', yuniorrojasInit_faq);
  } else {
    yuniorrojasInit_faq();
  }
})();
