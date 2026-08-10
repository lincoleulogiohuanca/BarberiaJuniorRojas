# Barbería Junior Rojas (WordPress)

Sitio de reservas y pagos (Culqi / medios manuales) para **Barbería Junior Rojas**.

## Qué versiona este repositorio

| Incluido en Git | No incluido (no va a GitHub) |
|-----------------|------------------------------|
| Tema `yuniorrojastheme` — **BarberFlow Theme** (línea Free) | Core de WordPress |
| Plugin `juniorrojas-domain` — **BarberFlow Core** (funciones principales; Services + Staff) | Plugins de terceros (ACF, etc.) |
| Plugin `juniorrojas-reservas` — **BarberFlow Book** / Booking (reservas) | |
| Plugin `juniorrojas-pagos` — **BarberFlow Payments** (pagos) | |
| Plugin `juniorrojas-core` — **BarberFlow Pro** (CRM, agenda, galería/reseñas admin) | (secretos solo en hosting) |
| Plugin `juniorrojas-post-types` — **Fade Legacy CPTs** | `wp-config.php` y secretos Culqi/SMTP |
| Docs (`README`, `DEPLOY`, `CHANGELOG`, `SECURITY`) | Base de datos, uploads, carpeta Local |
| `.github/` (CI, deploy, templates) | |

## Estructura relevante

```text
app/public/wp-content/
├── themes/yuniorrojastheme/     ← tema (solo UI: plantillas, CSS/JS front)
└── plugins/
    ├── juniorrojas-domain/      ← BarberFlow Core: Services, Staff, schema, helpers
    ├── juniorrojas-reservas/    ← BarberFlow Book (Booking): citas, REST, fidelidad
    ├── juniorrojas-pagos/       ← BarberFlow Payments: Culqi, medios, webhook
    ├── juniorrojas-core/        ← BarberFlow Pro: CRM, agenda, Gallery/Reviews admin, dashboard
    └── juniorrojas-post-types/  ← Fade Legacy CPTs (opcional)
```



## Marca y catálogo BarberFlow

| Producto | Qué es hoy en el repo |
|----------|------------------------|
| **BarberFlow Theme** | Tema `yuniorrojastheme` (línea **Free**) |
| **BarberFlow Core** | Plugin `juniorrojas-domain` — funciones principales (incl. **Services** y **Staff**) |
| **BarberFlow Book** | Plugin `juniorrojas-reservas` — reservas (**Booking**) |
| **BarberFlow Payments** | Plugin `juniorrojas-pagos` — cobros Culqi / medios |
| **BarberFlow Pro** | Plugin `juniorrojas-core` — premium: **CRM**, agenda, admin de **Gallery** / **Reviews** |

### Suites / empaquetado

| Suite | Contenido |
|-------|-----------|
| **BarberFlow Free** | Solo **BarberFlow Theme** (vitrina) |
| **BarberFlow Booking** | Producto de reservas = **BarberFlow Book** (+ **Core**) |
| **BarberFlow Pro** (suite) | Core + Book + Payments + plugin **Pro** (CRM / agenda / admin) |
| **BarberFlow Suite** | Paquete completo: Theme + Core + Book + Payments + Pro |

### Módulos del catálogo (hoy embebidos, no son carpetas aparte)

| Módulo | Dónde vive hoy |
|--------|----------------|
| **Services** | BarberFlow Core (CPT servicios) |
| **Staff** | BarberFlow Core (CPT barberos) |
| **CRM** | BarberFlow Pro (clientes) |
| **Gallery** | BarberFlow Pro / Core (metas y REST) |
| **Reviews** | BarberFlow Pro (reseñas) |

Más adelante se pueden extraer a plugins propios manteniendo estos nombres de producto.

## Flujo de trabajo

```text
  PC (Local WP)  →  git commit / push  →  GitHub  →  Hosting (producción)
```

1. Desarrollas y pruebas en **Local**.
2. Cuando una función está lista: `commit` + `push` a GitHub.
3. En el hosting despliegas **solo el código** del tema/plugins (FTP, Git deploy o Action).
4. Contenido y datos reales viven en la **BD de producción**.

## Requisitos en hosting

- WordPress instalado
- Tema activo: **BarberFlow Theme** (`yuniorrojastheme`)
- Plugin activo: **BarberFlow Core** (`juniorrojas-domain`)
- Plugin activo: **BarberFlow Book** (`juniorrojas-reservas`)
- Plugin activo: **BarberFlow Payments** (`juniorrojas-pagos`)
- Plugin activo: **BarberFlow Pro** (`juniorrojas-core`; requiere Core + Book + Payments)
- Plugin legacy **Fade Legacy CPTs** opcional
- Permalink + REST API
- Culqi live en *Reservas → Ajustes Culqi*
- Webhook Culqi: `https://TU-DOMINIO/wp-json/yuniorrojas/v1/culqi/webhook`
- Tras activar stack: *Herramientas → BarberFlow DB Backfill* si hay reservas previas

Detalle de deploy en [DEPLOY.md](./DEPLOY.md).  
Changelog: [CHANGELOG.md](./CHANGELOG.md) · Seguridad: [SECURITY.md](./SECURITY.md)  
GitHub ops: [`.github/GITHUB_SETUP.md`](./.github/GITHUB_SETUP.md) · Crecimiento: [`.github/GROWTH.md`](./.github/GROWTH.md)

## Manual del admin

Documentación versionada en [`docs/`](./docs/) (agenda, pagos, fidelidad, producción, go-live).  
Si GitHub Pages está activo: `https://lincoleulogiohuanca.github.io/BarberiaJuniorRojas/`

## Issues y PRs

- **Bug / Feature / Go-live** → plantillas en *New issue*
- **Discussions** → ideas / Q&A del equipo
- **Milestones** → `v1.0.0` (final), `v1.1.0` (siguiente)
- **Pull requests** → checklist Culqi, reservas, SMTP, backfill, CI
- **CODEOWNERS** → `@lincoleulogiohuanca` en tema/plugin
- **Releases** → ZIPs `yuniorrojastheme.zip` + `juniorrojas-domain.zip` + `juniorrojas-reservas.zip` + `juniorrojas-pagos.zip` + `juniorrojas-core.zip` (workflow Release assets)

## Desarrollo local

Proyecto pensado para **Local** (Flywheel): `Local Sites/barberia`.

No subas `wp-config.php`, dumps SQL ni `local-xdebuginfo.php` a producción/GitHub.

Smoke tests (con WP-CLI):

```bash
wp eval-file wp-content/themes/yuniorrojastheme/tests/smoke-prod.php
```
