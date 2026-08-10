# Barbería Junior Rojas (WordPress)

Sitio de reservas y pagos (Culqi / medios manuales) para **Barbería Junior Rojas**.

## Qué versiona este repositorio

| Incluido en Git | No incluido (no va a GitHub) |
|-----------------|------------------------------|
| Tema `yuniorrojastheme` | Core de WordPress |
| Plugin `juniorrojas-core` | Plugins de terceros (ACF, etc.) |
| Plugin `juniorrojas-post-types` (legacy) | `wp-config.php` y secretos Culqi/SMTP |
| Docs (`README`, `DEPLOY`, `CHANGELOG`, `SECURITY`) | Base de datos, uploads, carpeta Local |
| `.github/` (CI, deploy, templates) | |

## Estructura relevante

```text
app/public/wp-content/
├── themes/yuniorrojastheme/     ← tema (UI, plantillas, assets)
└── plugins/
    ├── juniorrojas-core/        ← dominio: tablas, locks, webhook Culqi
    └── juniorrojas-post-types/  ← legacy CPT (opcional)
```

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
- Tema activo: `yuniorrojastheme`
- Plugin activo: **`juniorrojas-core`**
- Plugin legacy `juniorrojas-post-types` opcional
- Permalink + REST API
- Culqi live en *Reservas → Ajustes Culqi*
- Webhook Culqi: `https://TU-DOMINIO/wp-json/yuniorrojas/v1/culqi/webhook`
- Tras activar Core: *Herramientas → JR DB Backfill* si hay reservas previas

Detalle de deploy en [DEPLOY.md](./DEPLOY.md).  
Changelog: [CHANGELOG.md](./CHANGELOG.md) · Seguridad: [SECURITY.md](./SECURITY.md)  
GitHub ops: [`.github/GITHUB_SETUP.md`](./.github/GITHUB_SETUP.md) · Crecimiento: [`.github/GROWTH.md`](./.github/GROWTH.md)

## Manual del admin

Documentación versionada en [`docs/`](./docs/) (agenda, pagos, fidelidad, producción, go-live).  
Si GitHub Pages está activo: `https://lincoleulogiohuanca.github.io/BarberiaJuniorRojas/`

## Issues y PRs

- **Bug / Feature / Go-live** → plantillas en *New issue*
- **Discussions** → ideas / Q&A del equipo
- **Milestones** → `v1.1.0`, `v1.2.0`
- **Pull requests** → checklist Culqi, reservas, SMTP, backfill, CI
- **CODEOWNERS** → `@lincoleulogiohuanca` en tema/plugin
- **Releases** → ZIPs `yuniorrojastheme.zip` + `juniorrojas-core.zip` (workflow Release assets)

## Desarrollo local

Proyecto pensado para **Local** (Flywheel): `Local Sites/barberia`.

No subas `wp-config.php`, dumps SQL ni `local-xdebuginfo.php` a producción/GitHub.

Smoke tests (con WP-CLI):

```bash
wp eval-file wp-content/themes/yuniorrojastheme/tests/smoke-prod.php
```
