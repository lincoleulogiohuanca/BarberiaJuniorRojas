/** Auto-split from main.js — auth */
(function () {
  function yuniorrojasInit_auth() {
    // Auth login: mostrar / ocultar contraseña
    document.querySelectorAll("[data-password-toggle]").forEach((button) => {
      if (!(button instanceof HTMLButtonElement)) {
        return;
      }
    
      button.addEventListener("click", () => {
        const field = button.closest(".auth-login__control");
        const input =
          field instanceof HTMLElement
            ? field.querySelector("[data-password-input]")
            : null;
        const icon =
          button.querySelector("[data-password-icon]") ||
          button.querySelector(".ti");
    
        if (!(input instanceof HTMLInputElement)) {
          return;
        }
    
        const showing = input.type === "text";
        input.type = showing ? "password" : "text";
        button.setAttribute("aria-pressed", showing ? "false" : "true");
        button.setAttribute(
          "aria-label",
          showing ? "Mostrar contraseña" : "Ocultar contraseña"
        );
    
        if (icon instanceof HTMLElement) {
          icon.classList.toggle("ti-eye-off", showing);
          icon.classList.toggle("ti-eye", !showing);
        }
      });
    });
    
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', yuniorrojasInit_auth);
  } else {
    yuniorrojasInit_auth();
  }
})();
