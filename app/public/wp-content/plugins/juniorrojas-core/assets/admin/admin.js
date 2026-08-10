(function ($) {
  "use strict";

  function initProcesos() {
    const contenedor = document.getElementById("yuniorrojas-procesos");
    const botonAgregar = document.getElementById("yuniorrojas-agregar-proceso");
    const contador = document.querySelector("[data-procesos-count]");

    if (!contenedor || !botonAgregar) {
      return;
    }

    function textoContador(total) {
      return total === 1 ? "1 proceso" : total + " procesos";
    }

    function sincronizarVacio() {
      const items = contenedor.querySelectorAll("[data-proceso-item]");
      let empty = contenedor.querySelector("[data-procesos-empty]");

      if (items.length === 0) {
        if (!empty) {
          empty = document.createElement("p");
          empty.className = "yuniorrojas-procesos-empty";
          empty.setAttribute("data-procesos-empty", "");
          empty.textContent =
            "Todavía no hay procesos. Pulsa “Añadir proceso” para crear el primero.";
          contenedor.appendChild(empty);
        }
      } else if (empty) {
        empty.remove();
      }

      if (contador) {
        contador.textContent = textoContador(items.length);
      }
    }

    function actualizarProcesos() {
      contenedor.querySelectorAll("[data-proceso-item]").forEach(function (proceso, index) {
        const encabezado = proceso.querySelector("[data-proceso-numero]");
        if (encabezado) {
          encabezado.textContent = "PROCESO " + String(index + 1).padStart(2, "0");
        }

        proceso.querySelectorAll("input, textarea").forEach(function (input) {
          const campo = input.dataset.campo;
          if (!campo) {
            return;
          }
          input.name = "yuniorrojas_procesos[" + index + "][" + campo + "]";
        });
      });

      sincronizarVacio();
    }

    function plantillaProceso(index) {
      const numero = String(index + 1).padStart(2, "0");

      return (
        '<div class="yuniorrojas-proceso" data-proceso-item>' +
        '<div class="yuniorrojas-proceso__header">' +
        '<span class="yuniorrojas-proceso__handle" title="Arrastrar para reordenar" aria-hidden="true">⋮⋮</span>' +
        '<strong class="yuniorrojas-proceso__numero" data-proceso-numero>PROCESO ' +
        numero +
        "</strong>" +
        '<button type="button" class="button yuniorrojas-eliminar-proceso">Eliminar</button>' +
        "</div>" +
        '<div class="yuniorrojas-proceso__body">' +
        "<p><label><strong>Título</strong></label>" +
        '<input type="text" class="widefat" data-campo="titulo" name="yuniorrojas_procesos[' +
        index +
        '][titulo]" placeholder="Ejemplo: Consulta de imagen"></p>' +
        "<p><label><strong>Descripción</strong></label>" +
        '<textarea class="widefat" rows="4" data-campo="descripcion" name="yuniorrojas_procesos[' +
        index +
        '][descripcion]" placeholder="Describe este proceso..."></textarea></p>' +
        "</div></div>"
      );
    }

    botonAgregar.addEventListener("click", function () {
      const index = contenedor.querySelectorAll("[data-proceso-item]").length;
      contenedor.insertAdjacentHTML("beforeend", plantillaProceso(index));
      actualizarProcesos();

      const ultimo = contenedor.querySelector("[data-proceso-item]:last-of-type input[data-campo='titulo']");
      if (ultimo) {
        ultimo.focus();
      }
    });

    contenedor.addEventListener("click", function (event) {
      const target = event.target;
      if (!(target instanceof HTMLElement) || !target.classList.contains("yuniorrojas-eliminar-proceso")) {
        return;
      }

      const proceso = target.closest("[data-proceso-item]");
      if (proceso) {
        proceso.remove();
        actualizarProcesos();
      }
    });

    if ($.fn.sortable) {
      $(contenedor).sortable({
        items: "[data-proceso-item]",
        handle: ".yuniorrojas-proceso__handle",
        axis: "y",
        opacity: 0.85,
        placeholder: "yuniorrojas-proceso-placeholder",
        forcePlaceholderSize: true,
        update: actualizarProcesos,
      });
    }

    sincronizarVacio();
  }

  function initGaleria() {
    function bindGaleria(config) {
      let frame;
      const $btn = $(config.btn);
      const $input = $(config.input);
      const $preview = $(config.preview);

      if (!$btn.length || !$input.length || !$preview.length) {
        return;
      }

      $btn.on("click", function (event) {
        event.preventDefault();

        if (frame) {
          frame.open();
          return;
        }

        frame = wp.media({
          title: config.title,
          button: { text: config.button },
          multiple: true,
          library: { type: "image" },
        });

        frame.on("select", function () {
          const selection = frame.state().get("selection");
          const idsActuales = String($input.val() || "")
            .split(",")
            .filter(Boolean);

          selection.each(function (attachment) {
            const data = attachment.toJSON();
            if (idsActuales.includes(String(data.id))) {
              return;
            }
            idsActuales.push(String(data.id));
            const url = (data.sizes && data.sizes.medium && data.sizes.medium.url) || data.url;
            $preview.append(
              '<div class="yuniorrojas-imagen" data-id="' +
                data.id +
                '"><img src="' +
                url +
                '" alt=""><button type="button" class="button yuniorrojas-eliminar-imagen">×</button></div>'
            );
          });

          $input.val(idsActuales.join(","));
        });

        frame.open();
      });

      $preview.on("click", ".yuniorrojas-eliminar-imagen", function () {
        $(this).closest(".yuniorrojas-imagen").remove();
        const ids = [];
        $preview.find(".yuniorrojas-imagen").each(function () {
          ids.push($(this).data("id"));
        });
        $input.val(ids.join(","));
      });
    }

    bindGaleria({
      btn: "#yuniorrojas-agregar-imagenes",
      input: "#yuniorrojas-galeria-input",
      preview: "#yuniorrojas-galeria-preview",
      title: "Seleccionar imágenes para el servicio",
      button: "Usar estas imágenes",
    });

    initEspecialidades();
  }

  function initEspecialidades() {
    let frame;
    const $btn = $("#yuniorrojas-agregar-especialidades");
    const $preview = $("#yuniorrojas-especialidades-preview");

    if (!$btn.length || !$preview.length) {
      return;
    }

    function reindexEspecialidades() {
      $preview.find(".yuniorrojas-especialidad").each(function (index) {
        const $item = $(this);
        const id = $item.data("id");
        $item.find('input[type="hidden"]').attr("name", "yuniorrojas_especialidades[" + index + "][id]").val(id);
        $item.find(".yuniorrojas-especialidad__titulo").attr("name", "yuniorrojas_especialidades[" + index + "][titulo]");
      });
    }

    $btn.on("click", function (event) {
      event.preventDefault();

      if (frame) {
        frame.open();
        return;
      }

      frame = wp.media({
        title: "Especialidades & trabajo",
        button: { text: "Usar estas imágenes" },
        multiple: true,
        library: { type: "image" },
      });

      frame.on("select", function () {
        const selection = frame.state().get("selection");
        const idsActuales = [];
        $preview.find(".yuniorrojas-especialidad").each(function () {
          idsActuales.push(String($(this).data("id")));
        });

        selection.each(function (attachment) {
          const data = attachment.toJSON();
          if (idsActuales.includes(String(data.id))) {
            return;
          }
          idsActuales.push(String(data.id));
          const url = (data.sizes && data.sizes.medium && data.sizes.medium.url) || data.url;
          const titulo = data.alt || data.title || "";
          const index = $preview.find(".yuniorrojas-especialidad").length;

          $preview.append(
            '<div class="yuniorrojas-especialidad" data-id="' +
              data.id +
              '">' +
              '<div class="yuniorrojas-especialidad__media">' +
              '<img src="' +
              url +
              '" alt="">' +
              '<button type="button" class="button yuniorrojas-eliminar-especialidad">×</button>' +
              "</div>" +
              '<input type="text" class="widefat yuniorrojas-especialidad__titulo" name="yuniorrojas_especialidades[' +
              index +
              '][titulo]" value="' +
              String(titulo).replace(/"/g, "&quot;") +
              '" placeholder="Ej: Degradado">' +
              '<input type="hidden" name="yuniorrojas_especialidades[' +
              index +
              '][id]" value="' +
              data.id +
              '">' +
              "</div>"
          );
        });

        reindexEspecialidades();
      });

      frame.open();
    });

    $preview.on("click", ".yuniorrojas-eliminar-especialidad", function () {
      $(this).closest(".yuniorrojas-especialidad").remove();
      reindexEspecialidades();
    });
  }

  function initImagenPerfil() {
    let framePerfil;
    const input = $("#yuniorrojas-imagen-perfil-input");
    const preview = $("#yuniorrojas-imagen-perfil-preview");
    const btnQuitar = $("#yuniorrojas-quitar-imagen-perfil");
    const btnSelect = $("#yuniorrojas-seleccionar-imagen-perfil");

    if (!input.length || !btnSelect.length) {
      return;
    }

    btnSelect.on("click", function (event) {
      event.preventDefault();

      if (framePerfil) {
        framePerfil.open();
        return;
      }

      framePerfil = wp.media({
        title: "Imagen del perfil (detalle)",
        button: { text: "Usar esta imagen" },
        multiple: false,
        library: { type: "image" },
      });

      framePerfil.on("select", function () {
        const data = framePerfil.state().get("selection").first().toJSON();
        const url = (data.sizes && data.sizes.medium && data.sizes.medium.url) || data.url;
        input.val(String(data.id));
        preview.html('<img src="' + url + '" alt="">');
        btnQuitar.prop("hidden", false);
        btnSelect.text("Cambiar imagen");
      });

      framePerfil.open();
    });

    btnQuitar.on("click", function (event) {
      event.preventDefault();
      input.val("");
      preview.empty();
      btnQuitar.prop("hidden", true);
      btnSelect.text("Seleccionar imagen");
    });
  }

  /**
   * Medios de pago (Plin / manual): selector de imagen QR.
   */
  function initMedioPagoQr() {
    const wrap = document.querySelector("[data-jr-medio-qr]");
    if (!(wrap instanceof HTMLElement)) {
      return;
    }

    const input = wrap.querySelector("[data-jr-medio-qr-id]");
    const preview = wrap.querySelector("[data-jr-medio-qr-preview]");
    const btnSelect = wrap.querySelector("[data-jr-medio-qr-select]");
    const btnClear = wrap.querySelector("[data-jr-medio-qr-clear]");

    if (
      !(input instanceof HTMLInputElement) ||
      !(btnSelect instanceof HTMLElement)
    ) {
      return;
    }

    let frameQr = null;
    const emptyLabel =
      '<span class="description" style="padding:.75rem;text-align:center;">Sin imagen subida</span>';

    const setPreview = (url) => {
      if (!(preview instanceof HTMLElement)) {
        return;
      }
      if (url) {
        preview.innerHTML =
          '<img src="' +
          url +
          '" alt="" style="max-width:100%;height:auto;display:block;">';
      } else {
        preview.innerHTML = emptyLabel;
      }
    };

    btnSelect.addEventListener("click", function (event) {
      event.preventDefault();
      if (typeof wp === "undefined" || !wp.media) {
        window.alert("No se pudo abrir la biblioteca de medios. Recarga la página.");
        return;
      }
      if (!frameQr) {
        frameQr = wp.media({
          title: "Imagen QR de pago",
          button: { text: "Usar esta imagen" },
          multiple: false,
          library: { type: "image" },
        });
        frameQr.on("select", function () {
          const data = frameQr.state().get("selection").first().toJSON();
          const url =
            (data.sizes && data.sizes.medium && data.sizes.medium.url) || data.url;
          input.value = String(data.id || 0);
          setPreview(url || "");
          if (btnClear instanceof HTMLElement) {
            btnClear.hidden = false;
          }
          btnSelect.textContent = "Cambiar imagen QR";
        });
      }
      frameQr.open();
    });

    if (btnClear instanceof HTMLElement) {
      btnClear.addEventListener("click", function (event) {
        event.preventDefault();
        input.value = "0";
        setPreview("");
        btnClear.hidden = true;
        btnSelect.textContent = "Seleccionar imagen QR";
      });
    }
  }

  function initHorarios() {
    const contenedor = document.getElementById("yuniorrojas-horarios");
    const botonAgregar = document.getElementById("yuniorrojas-agregar-horario");
    const contador = document.querySelector("[data-horarios-count]");

    if (!contenedor || !botonAgregar) {
      return;
    }

    function textoContador(total) {
      return total === 1 ? "1 horario" : total + " horarios";
    }

    function sincronizarVacio() {
      const items = contenedor.querySelectorAll("[data-horario-item]");
      let empty = contenedor.querySelector("[data-horarios-empty]");

      if (items.length === 0) {
        if (!empty) {
          empty = document.createElement("p");
          empty.className = "yuniorrojas-horarios-empty";
          empty.setAttribute("data-horarios-empty", "");
          empty.textContent =
            "Todavía no hay horarios. Pulsa “Añadir horario” para crear el primero.";
          contenedor.appendChild(empty);
        }
      } else if (empty) {
        empty.remove();
      }

      if (contador) {
        contador.textContent = textoContador(items.length);
      }
    }

    function actualizarHorarios() {
      contenedor.querySelectorAll("[data-horario-item]").forEach(function (horario, index) {
        const encabezado = horario.querySelector("[data-horario-numero]");
        if (encabezado) {
          encabezado.textContent = "HORARIO " + String(index + 1).padStart(2, "0");
        }

        horario.querySelectorAll("input").forEach(function (input) {
          const campo = input.dataset.campo;
          if (!campo) {
            return;
          }
          input.name = "yuniorrojas_contacto[horarios][" + index + "][" + campo + "]";
        });
      });

      sincronizarVacio();
    }

    function plantillaHorario(index) {
      const numero = String(index + 1).padStart(2, "0");

      return (
        '<div class="yuniorrojas-horario" data-horario-item>' +
        '<div class="yuniorrojas-horario__header">' +
        '<span class="yuniorrojas-horario__handle" title="Arrastrar para reordenar" aria-hidden="true">⋮⋮</span>' +
        '<strong class="yuniorrojas-horario__numero" data-horario-numero>HORARIO ' +
        numero +
        "</strong>" +
        '<button type="button" class="button yuniorrojas-eliminar-horario">Eliminar</button>' +
        "</div>" +
        '<div class="yuniorrojas-horario__body">' +
        "<p><label><strong>Día</strong></label>" +
        '<input type="text" class="widefat" data-campo="dia" name="yuniorrojas_contacto[horarios][' +
        index +
        '][dia]" placeholder="Ejemplo: Lun – Vie"></p>' +
        "<p><label><strong>Hora</strong></label>" +
        '<input type="text" class="widefat" data-campo="hora" name="yuniorrojas_contacto[horarios][' +
        index +
        '][hora]" placeholder="Ejemplo: 10:00 am – 9:00 pm"></p>' +
        "</div></div>"
      );
    }

    botonAgregar.addEventListener("click", function () {
      const index = contenedor.querySelectorAll("[data-horario-item]").length;
      contenedor.insertAdjacentHTML("beforeend", plantillaHorario(index));
      actualizarHorarios();

      const ultimo = contenedor.querySelector("[data-horario-item]:last-of-type input[data-campo='dia']");
      if (ultimo) {
        ultimo.focus();
      }
    });

    contenedor.addEventListener("click", function (event) {
      const target = event.target;
      if (!(target instanceof HTMLElement) || !target.classList.contains("yuniorrojas-eliminar-horario")) {
        return;
      }

      const horario = target.closest("[data-horario-item]");
      if (horario) {
        horario.remove();
        actualizarHorarios();
      }
    });

    if ($.fn.sortable) {
      $(contenedor).sortable({
        items: "[data-horario-item]",
        handle: ".yuniorrojas-horario__handle",
        axis: "y",
        opacity: 0.85,
        placeholder: "yuniorrojas-horario-placeholder",
        forcePlaceholderSize: true,
        update: actualizarHorarios,
      });
    }

    sincronizarVacio();
  }

  function initRedes() {
    const contenedor = document.getElementById("yuniorrojas-redes");
    const botonAgregar = document.getElementById("yuniorrojas-agregar-red");
    const contador = document.querySelector("[data-redes-count]");

    if (!contenedor || !botonAgregar) {
      return;
    }

    const iconos = [
      ["instagram", "Instagram"],
      ["facebook", "Facebook"],
      ["youtube", "YouTube"],
      ["tiktok", "TikTok"],
      ["whatsapp", "WhatsApp"],
      ["x", "X (Twitter)"],
      ["linkedin", "LinkedIn"],
      ["telegram", "Telegram"],
      ["threads", "Threads"],
      ["pinterest", "Pinterest"],
    ];

    function opcionesIcono(seleccionado) {
      return iconos
        .map(function (item) {
          const selected = item[0] === seleccionado ? ' selected="selected"' : "";
          return '<option value="' + item[0] + '"' + selected + ">" + item[1] + "</option>";
        })
        .join("");
    }

    function textoContador(total) {
      return total === 1 ? "1 red" : total + " redes";
    }

    function sincronizarVacio() {
      const items = contenedor.querySelectorAll("[data-red-item]");
      let empty = contenedor.querySelector("[data-redes-empty]");

      if (items.length === 0) {
        if (!empty) {
          empty = document.createElement("p");
          empty.className = "yuniorrojas-redes-empty";
          empty.setAttribute("data-redes-empty", "");
          empty.textContent =
            "Todavía no hay redes. Pulsa “Añadir red” para crear la primera.";
          contenedor.appendChild(empty);
        }
      } else if (empty) {
        empty.remove();
      }

      if (contador) {
        contador.textContent = textoContador(items.length);
      }
    }

    function actualizarRedes() {
      contenedor.querySelectorAll("[data-red-item]").forEach(function (red, index) {
        const encabezado = red.querySelector("[data-red-numero]");
        if (encabezado) {
          encabezado.textContent = "RED " + String(index + 1).padStart(2, "0");
        }

        red.querySelectorAll("input, select").forEach(function (campoEl) {
          const campo = campoEl.dataset.campo;
          if (!campo) {
            return;
          }
          campoEl.name = "yuniorrojas_contacto[redes][" + index + "][" + campo + "]";
        });
      });

      sincronizarVacio();
    }

    function plantillaRed(index) {
      const numero = String(index + 1).padStart(2, "0");

      return (
        '<div class="yuniorrojas-red" data-red-item>' +
        '<div class="yuniorrojas-red__header">' +
        '<span class="yuniorrojas-red__handle" title="Arrastrar para reordenar" aria-hidden="true">⋮⋮</span>' +
        '<strong class="yuniorrojas-red__numero" data-red-numero>RED ' +
        numero +
        "</strong>" +
        '<button type="button" class="button yuniorrojas-eliminar-red">Eliminar</button>' +
        "</div>" +
        '<div class="yuniorrojas-red__body">' +
        "<p><label><strong>Nombre</strong></label>" +
        '<input type="text" class="widefat" data-campo="nombre" name="yuniorrojas_contacto[redes][' +
        index +
        '][nombre]" placeholder="Ejemplo: Instagram"></p>' +
        "<p><label><strong>Icono</strong></label>" +
        '<select class="widefat" data-campo="icono" name="yuniorrojas_contacto[redes][' +
        index +
        '][icono]">' +
        opcionesIcono("instagram") +
        "</select></p>" +
        '<p class="yuniorrojas-red__url"><label><strong>URL</strong></label>' +
        '<input type="url" class="widefat" data-campo="url" name="yuniorrojas_contacto[redes][' +
        index +
        '][url]" placeholder="https://"></p>' +
        "</div></div>"
      );
    }

    botonAgregar.addEventListener("click", function () {
      const index = contenedor.querySelectorAll("[data-red-item]").length;
      contenedor.insertAdjacentHTML("beforeend", plantillaRed(index));
      actualizarRedes();

      const ultimo = contenedor.querySelector(
        "[data-red-item]:last-of-type input[data-campo='nombre']"
      );
      if (ultimo) {
        ultimo.focus();
      }
    });

    contenedor.addEventListener("click", function (event) {
      const target = event.target;
      if (!(target instanceof HTMLElement) || !target.classList.contains("yuniorrojas-eliminar-red")) {
        return;
      }

      const red = target.closest("[data-red-item]");
      if (red) {
        red.remove();
        actualizarRedes();
      }
    });

    if ($.fn.sortable) {
      $(contenedor).sortable({
        items: "[data-red-item]",
        handle: ".yuniorrojas-red__handle",
        axis: "y",
        opacity: 0.85,
        placeholder: "yuniorrojas-red-placeholder",
        forcePlaceholderSize: true,
        update: actualizarRedes,
      });
    }

    sincronizarVacio();
  }

  function initHorarioBarbero() {
    const root = document.querySelector("[data-horario-barbero]");
    if (!root) {
      return;
    }

    root.querySelectorAll("[data-horario-dia]").forEach(function (row) {
      const activo = row.querySelector("[data-horario-activo]");
      const inicio = row.querySelector("[data-horario-inicio]");
      const fin = row.querySelector("[data-horario-fin]");

      if (!(activo instanceof HTMLInputElement)) {
        return;
      }

      const sync = function () {
        const on = activo.checked;
        row.classList.toggle("is-off", !on);
        // readonly (no disabled): así Desde/Hasta siempre se envían al guardar.
        if (inicio instanceof HTMLInputElement) {
          inicio.readOnly = !on;
        }
        if (fin instanceof HTMLInputElement) {
          fin.readOnly = !on;
        }
      };

      activo.addEventListener("change", sync);
      sync();
    });
  }

  function initReservaAdmin() {
    const root = document.querySelector("[data-jr-reserva-admin]");
    const servicioSelect =
      document.querySelector("[data-jr-servicio-select]") ||
      document.getElementById("jr_servicio_id");
    const precioInput =
      document.querySelector("[data-jr-precio]") ||
      document.getElementById("jr_precio");
    const duracionInput =
      document.querySelector("[data-jr-duracion]") ||
      document.getElementById("jr_duracion");

    if (!root && !(servicioSelect instanceof HTMLSelectElement)) {
      return;
    }

    const title = document.getElementById("title");
    if (title instanceof HTMLInputElement) {
      title.readOnly = true;
      title.setAttribute("aria-readonly", "true");
      title.title = "Se regenera automáticamente al guardar";
    }

    const titleWrap = document.getElementById("titlediv");
    if (titleWrap && !titleWrap.querySelector("[data-jr-title-hint]")) {
      const hint = document.createElement("p");
      hint.className = "description";
      hint.setAttribute("data-jr-title-hint", "");
      hint.textContent =
        "Título automático (servicio + cliente + fecha/hora). Se actualiza al guardar.";
      titleWrap.appendChild(hint);
    }

    const syncServicioMeta = function () {
      if (!(servicioSelect instanceof HTMLSelectElement)) {
        return;
      }
      const option = servicioSelect.options[servicioSelect.selectedIndex];
      if (!option || !option.value) {
        return;
      }
      const precio = option.getAttribute("data-precio");
      const duracion = option.getAttribute("data-duracion");
      if (precioInput instanceof HTMLInputElement && precio !== null && precio !== "") {
        precioInput.value = precio;
      }
      if (
        duracionInput instanceof HTMLInputElement &&
        duracion !== null &&
        duracion !== ""
      ) {
        duracionInput.value = duracion;
      }
    };

    if (servicioSelect instanceof HTMLSelectElement) {
      servicioSelect.addEventListener("change", syncServicioMeta);
      // jQuery (por si WP/admin lo usa en el select).
      if (window.jQuery) {
        window.jQuery(servicioSelect).on("change", syncServicioMeta);
      }
    }

    const pickBtn = document.querySelector("[data-jr-resultado-pick]");
    const clearBtn = document.querySelector("[data-jr-resultado-clear]");
    const idInput = document.querySelector("[data-jr-resultado-id]");
    const preview = document.querySelector("[data-jr-resultado-preview]");
    let frameResultado = null;

    if (pickBtn instanceof HTMLElement && idInput instanceof HTMLInputElement) {
      pickBtn.addEventListener("click", function (event) {
        event.preventDefault();
        if (typeof wp === "undefined" || !wp.media) {
          return;
        }
        if (!frameResultado) {
          frameResultado = wp.media({
            title: "Foto del resultado",
            button: { text: "Usar esta foto" },
            multiple: false,
            library: { type: "image" },
          });
          frameResultado.on("select", function () {
            const attachment = frameResultado.state().get("selection").first().toJSON();
            idInput.value = String(attachment.id || "");
            if (preview instanceof HTMLElement) {
              const url = attachment.sizes && attachment.sizes.medium
                ? attachment.sizes.medium.url
                : attachment.url;
              preview.innerHTML = url
                ? '<img src="' + url + '" alt="" style="max-width:180px;height:auto;border-radius:6px;">'
                : "";
            }
          });
        }
        frameResultado.open();
      });
    }

    if (clearBtn instanceof HTMLElement && idInput instanceof HTMLInputElement) {
      clearBtn.addEventListener("click", function (event) {
        event.preventDefault();
        idInput.value = "";
        if (preview instanceof HTMLElement) {
          preview.innerHTML = "";
        }
      });
    }
  }

  $(function () {
    initProcesos();
    initHorarios();
    initRedes();
    initGaleria();
    initImagenPerfil();
    initMedioPagoQr();
    initHorarioBarbero();
    initReservaAdmin();
  });
})(jQuery);
