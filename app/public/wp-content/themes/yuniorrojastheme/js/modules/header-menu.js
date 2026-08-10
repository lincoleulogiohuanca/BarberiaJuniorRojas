/** Auto-split from main.js — header-menu */
(function () {
  function yuniorrojasInit_header_menu() {
    const header = document.querySelector(".header");
    const menuToggle = document.querySelector("[data-menu-toggle]");
    const menu = document.querySelector("[data-menu]");
    const menuOverlay = document.querySelector("[data-menu-overlay]");
    
    if (header && menuToggle && menu && menuOverlay) {
      const openMenu = () => {
        header.setAttribute("data-open", "true");
        menu.setAttribute("data-open", "true");
        menuOverlay.setAttribute("data-open", "true");
        menuToggle.setAttribute("data-open", "true");
        menuToggle.setAttribute("aria-expanded", "true");
        menuToggle.setAttribute("aria-label", "Cerrar menú");
        document.body.style.overflow = "hidden";
        document.documentElement.style.overflow = "hidden";
        document.body.setAttribute("data-menu-open", "true");
      };
    
      const closeMenu = () => {
        header.setAttribute("data-open", "false");
        menu.setAttribute("data-open", "false");
        menuOverlay.setAttribute("data-open", "false");
        menuToggle.setAttribute("data-open", "false");
        menuToggle.setAttribute("aria-expanded", "false");
        menuToggle.setAttribute("aria-label", "Abrir menú");
        document.body.style.overflow = "";
        document.documentElement.style.overflow = "";
        document.body.removeAttribute("data-menu-open");
      };
    
      menuToggle.addEventListener("click", () => {
        const isOpen = header.getAttribute("data-open") === "true";
        if (isOpen) {
          closeMenu();
        } else {
          openMenu();
        }
      });
    
      menuOverlay.addEventListener("click", closeMenu);
    
      menu.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", closeMenu);
      });
    
      document.addEventListener("click", (event) => {
        if (header.getAttribute("data-open") !== "true") {
          return;
        }
    
        const target = event.target;
        if (!(target instanceof Node)) {
          return;
        }
    
        if (menu.contains(target) || menuToggle.contains(target)) {
          return;
        }
    
        closeMenu();
      });
    
      document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
          closeMenu();
        }
      });
    
      const desktopBreakpoint = window.matchMedia("(min-width: 1145px)");
      desktopBreakpoint.addEventListener("change", (event) => {
        if (event.matches) {
          closeMenu();
        }
      });
    }
    
    // Header: dropdown de cuenta (cliente logueado)
    document.querySelectorAll("[data-header-account]").forEach((account) => {
      if (!(account instanceof HTMLElement)) {
        return;
      }
    
      const toggle = account.querySelector("[data-header-account-toggle]");
      const panel = account.querySelector("[data-header-account-menu]");
      if (!(toggle instanceof HTMLButtonElement) || !(panel instanceof HTMLElement)) {
        return;
      }
    
      const setOpen = (open) => {
        account.setAttribute("data-open", open ? "true" : "false");
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
        panel.hidden = !open;
      };
    
      toggle.addEventListener("click", (event) => {
        event.stopPropagation();
        const isOpen = account.getAttribute("data-open") === "true";
        setOpen(!isOpen);
      });
    
      document.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof Node) || account.contains(target)) {
          return;
        }
        setOpen(false);
      });
    
      document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
          setOpen(false);
        }
      });
    });
    
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', yuniorrojasInit_header_menu);
  } else {
    yuniorrojasInit_header_menu();
  }
})();
