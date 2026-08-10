# Configuración de GitHub (About, Environments, protección, deploy)

Guía para el repo [BarberiaJuniorRojas](https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas).

## About del repositorio

- **Descripción:** sistema de reservas y pagos (Culqi) para Barbería Junior Rojas — WordPress.
- **Topics:** `wordpress`, `culqi`, `peru`, `reservas`, `barberia`, `php`
- **Homepage:** URL pública del salón (cuando la tengas).
- **Default branch:** `main`

## Flujo de ramas

```text
feature → developer → PR → main → (deploy production con aprobación)
              ↓
        deploy staging (automático si hay secretos FTP)
```

- **No pushees a `main` a mano** (protección de rama).
- Trabaja en `developer` o en feature branches → PR hacia `main`.

## Branch protection (`main`)

Activado vía API o UI (Settings → Branches):

| Regla | Valor |
|-------|--------|
| Require a pull request before merging | Sí |
| Require status checks to pass | `Theme smoke / php-lint` |
| Require branches to be up to date | Recomendado |
| Allow force pushes | No |
| Allow deletions | No |
| Restrict who can push | (opcional: solo admins) |

> El check se llama exactamente **`Theme smoke / php-lint`** (workflow + job).

## Environments

Settings → Environments:

### `staging`

- Secrets (FTP del hosting de pruebas, si lo tienes):
  - `FTP_SERVER` — host FTP
  - `FTP_USERNAME`
  - `FTP_PASSWORD`
  - `FTP_SERVER_DIR_THEME` — ej. `/public_html/wp-content/themes/yuniorrojastheme/`
  - `FTP_SERVER_DIR_PLUGIN` — ej. `/public_html/wp-content/plugins/juniorrojas-core/`
  - `FTP_PROTOCOL` — opcional: `ftp` | `ftps` | `sftp` (default `ftp`)
  - `FTP_PORT` — opcional (default `21`)
- Variables:
  - `SITE_URL` — URL del staging (aparece en el deploy)

Sin secretos, el workflow de staging falla con mensaje claro (no despliega a ciegas).

### `production`

- **Mismos nombres de secretos** (valores del hosting real).
- **Protection rules:**
  - Required reviewers: tú (u otro admin)
  - Wait timer: 0 min (o 5 si quieres enfriarte)
- Variables: `SITE_URL` del dominio real

### Culqi / SMTP

**No** pongas `sk_live_` ni passwords SMTP en GitHub Actions.  
Van en el **servidor** (`wp-config.php` o *Reservas → Ajustes* del WP de cada entorno).

## Workflows

| Workflow | Trigger | Environment |
|----------|---------|-------------|
| Theme smoke | push/PR `main` y `developer` | — |
| Deploy Staging | push `developer` (paths tema/core) o manual | `staging` |
| Deploy Production | push `main` (paths) o manual | `production` (+ aprobación) |

## Issues

Plantillas:

- **Bug**
- **Feature**
- **Go-live checklist**

Labels sugeridos (créalos si no existen): `bug`, `enhancement`, `go-live`, `deploy`, `pagos`, `security`.

## Checklist post-setup (humano, 5 min)

1. [ ] About + topics guardados  
2. [ ] Environments `staging` y `production` creados  
3. [ ] Secretos FTP en cada environment  
4. [ ] Production: required reviewers  
5. [ ] Branch protection en `main` con check `Theme smoke / php-lint`  
6. [ ] Probar: PR dummy o “Re-run jobs” del smoke  
7. [ ] Primer deploy staging con `workflow_dispatch`  

## Troubleshooting

| Síntoma | Causa probable |
|---------|----------------|
| Deploy falla “missing secrets” | Environment mal elegido o secretos vacíos |
| Status check “expected” nunca corre | PR a `main` sin workflow; nombres de check distintos |
| FTP 550 / path | `FTP_SERVER_DIR_*` con trailing slash y path real del hosting |
| Merge a main bloqueado | Smoke en rojo o PR sin updates |
