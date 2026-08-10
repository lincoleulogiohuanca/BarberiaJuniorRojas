# Changelog

Todas las notas de versión relevantes de **Barbería Junior Rojas**.  
Formato inspirado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/)  
y versionado [SemVer](https://semver.org/lang/es/).

Los releases de GitHub enlazan a estas secciones:
https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/releases

---

## [Unreleased]

### Changed
- Marca **BarberFlow**: Theme, Core, Book, Payments, Pro; catálogo Free / Booking / Suite.


### Changed
- Branding de plataforma: tema **BarberFlow Theme**; plugins **BarberFlow Core**, **BarberFlow Book**, **BarberFlow Payments**, **BarberFlow Pro** (slugs de carpeta sin cambio).


### Added
- Plugin **Junior Rojas Pagos**: Culqi, medios de pago, settings, webhook (idempotencia en Domain).

### Changed
- **Junior Rojas Core** v1.5.0: admin operativo / contacto; depende de Domain + Reservas + Pagos.


### Added
- Plugin **Junior Rojas Reservas**: motor de citas (disponibilidad, REST, lista de espera, notificaciones, fidelidad).

### Changed
- **Junior Rojas Core** v1.4.0: pagos/admin restante; depende de Domain + Reservas.


### Added
- Plugin **Junior Rojas Domain**: cimiento (CPT, constants, helpers, metas de catálogo, schema `wp_jr_*`, queries).

### Changed
- **Junior Rojas Core** v1.3.0 depende de Domain (reservas, pagos, admin, REST).


### Changed
- Separación arquitectura: **Junior Rojas Core** concentra el dominio (reservas, pagos, admin, REST); el tema yuniorrojastheme queda solo como UI pública.

Cambios en `developer` / `main` aún no etiquetados.

### Planned / en curso
- Secrets FTP en environments de GitHub para deploy real
- Branch protection con approvals cuando el equipo > 1
- Ajustes finos de pantallas admin de plugins de terceros

### Removed (pendiente de versionar)
- Tema oscuro del admin (CSS dark, toggle barra, preferencia usuario): solo light WP admin

---

## [1.1.0] — 2026-08-10

**Admin dark mode + Agenda UX.**  
Release: https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/releases/tag/v1.1.0

### Added
- Tema claro/oscuro del **panel admin** (preferencia por usuario + interruptor en la admin bar)
- Hoja `admin-theme-dark.css` + `admin-theme.js` / `admin-theme.php`
- Escritorio operativo JR (`admin-dashboard.php` + `dashboard.css`)
- Paleta de citas en Agenda adaptada a dark (dorado JR + joyas, sin rosa)

### Changed
- Agenda: botones rectangulares estilo WordPress (sin pills)
- Agenda dark: columna “hoy” dorada, cards con más contraste, botón **+ Nueva cita** legible
- Menú lateral dark: separadores sin línea visible pero con el mismo aire que en light
- Admin bar: **Ctrl+K** y toggle **Claro/Oscuro** centrados en su `.ab-item`
- Docs/GitHub: Pages, milestones y estado de protection actualizados (repo público)

### Fixed
- Texto invisible en botón dorado (regla de enlaces `.wrap a` pintaba dorado sobre dorado)
- CodeMirror / editor de temas-plugins y listado de Plugins en dark

---

## [1.0.0] — 2026-08-10

**Primera versión pública.**  
Release: https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/releases/tag/v1.0.0

### Added
- Tema `yuniorrojastheme` (UI dark/gold, reservas, Mi cuenta, admin operativo)
- Plugin **Junior Rojas Core**: tablas `wp_jr_reservas`, locks de slot, webhook Culqi, backfill
- Pagos Culqi (tarjeta / Yape) con **idempotency key** y refund en fallos de insert
- Pagos manuales (Plin / transferencia) + comprobantes con ownership
- Disponibilidad por duración, reprogramar, cancelar (estudio)
- Fidelidad Gold/Platinum con **descuento online** en cobro Culqi
- Admin: agenda, ingresos, clientes, medios de pago, bloqueos, producción (SMTP/HTTPS)
- Frontend modular (`js/modules/*`)
- CI **Theme smoke** (lint PHP + checks de estructura)
- Deploy GitHub Actions (staging / production FTP) + issue/PR templates
- Documentación: `README.md`, `DEPLOY.md`, `.github/GITHUB_SETUP.md`

### Security
- Secret Culqi solo servidor; rate limit; validación de adjuntos
- `.gitignore` excluye wp-config, SQL, xdebuginfo, uploads

### Known limitations (plan free + repo privado)
- Branch protection clásica no disponible sin Pro/público
- Aprobación de environment production: deploy **manual** por defecto

---

## Cómo documentar la siguiente versión

1. Mueve ítems de `[Unreleased]` a `## [X.Y.Z] — YYYY-MM-DD`
2. Crea tag y release en GitHub con el mismo número
3. Enlace en el release al ancla del CHANGELOG si quieres

```bash
git tag -a v1.0.1 -m "v1.0.1 — …"
git push origin v1.0.1
# Release notes desde CHANGELOG
```

[Unreleased]: https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/releases/tag/v1.1.0
[1.0.0]: https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/releases/tag/v1.0.0
