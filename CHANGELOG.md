# Changelog

Todas las notas de versión relevantes de **Barbería Junior Rojas** / **BarberFlow**.  
Formato inspirado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/)  
y versionado [SemVer](https://semver.org/lang/es/).

Release único de la plataforma:

https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/releases/tag/v1.0.0

---

## [Unreleased]

Cambios posteriores al tag `v1.0.0` (siguiente ciclo).

---

## [1.0.0] — 2026-08-10

**Versión final 1.0.0** — primera release pública consolidada (tema + plugins BarberFlow).

Release: https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/releases/tag/v1.0.0

### Platform
- Marca **BarberFlow**: Theme, Core, Book, Payments, Pro (slugs de carpeta: `yuniorrojastheme`, `juniorrojas-*`).
- Arquitectura modular en 4 plugins + tema UI:
  - **juniorrojas-domain** — CPT, constants, helpers, schema `wp_jr_*`, catálogo
  - **juniorrojas-reservas** — disponibilidad, REST, notificaciones, fidelidad
  - **juniorrojas-pagos** — Culqi, medios de pago, webhook
  - **juniorrojas-core** — admin operativo, contacto, auth front, bridge
- Tema `yuniorrojastheme` solo presentación (sin lógica de dominio embebida)

### Frontend
- UI dark/gold del salón: inicio, servicios, equipo, galería, contacto, auth, Mi cuenta
- Reservas (pasos, barberos, slots, confirmación, medios de pago)
- Logo dinámico por **Customizer** (logo sitio + monograma opcional) y menú móvil de **3 líneas**
- Reserva móvil: carruseles, paginación de servicios, UX de cards al seleccionar
- Contacto: mapa admin con pin y vista pública; menú coherente
- Frontend modular (`js/modules/*`)

### Pagos y negocio
- Culqi (tarjeta / Yape) con **idempotency** y refund en fallos de insert
- Pagos manuales (Plin / transferencia) + comprobantes con ownership
- Disponibilidad por duración; reprogramar / cancelar (estudio)
- Fidelidad Gold/Platinum con descuento online en cobro Culqi

### Admin
- Agenda, ingresos, clientes, medios de pago, bloqueos, producción (SMTP/HTTPS)
- Escritorio operativo JR y widget de contacto
- Admin **solo light** de WordPress (sin tema dark del panel)

### CI / Deploy / Docs
- **Theme smoke** (lint PHP, JS modules, sin semillas Culqi hardcodeadas)
- Deploy GitHub Actions (staging / production FTP)
- Release assets: ZIPs tema + 4 plugins
- Documentación: `README.md`, `DEPLOY.md`, `docs/*`, `.github/*`

### Security
- Secret Culqi solo servidor; rate limit; validación de adjuntos
- `.gitignore` excluye wp-config, SQL, xdebuginfo, uploads

### Known limitations
- Secrets FTP deben estar en GitHub Environments (`staging` / `production`)
- Deploy production: manual o `DEPLOY_ON_PUSH_MAIN` según setup
- Branch protection con approvals: cuando el equipo > 1

---

## Cómo documentar la siguiente versión

1. Mueve ítems de `[Unreleased]` a `## [X.Y.Z] — YYYY-MM-DD`
2. Crea tag y release en GitHub con el mismo número
3. Enlace en el release al ancla del CHANGELOG

```bash
git tag -a v1.0.1 -m "v1.0.1 — …"
git push origin v1.0.1
```

[Unreleased]: https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/releases/tag/v1.0.0
