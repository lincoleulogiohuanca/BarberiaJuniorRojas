# Configuración de GitHub (About, Environments, protección, deploy)

Guía para el repo [BarberiaJuniorRojas](https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas).

## About del repositorio (ya aplicado)

- **Descripción:** Reservas y pagos (Culqi) para Barberia Junior Rojas - tema WordPress + plugin Core.
- **Topics:** `wordpress`, `culqi`, `peru`, `reservas`, `barberia`, `php`, `ftp-deploy`, `dark-mode`, `admin-ux`
- **Homepage:** actualiza a la URL real del salón cuando exista
- **Default branch:** `main`
- **Tags / releases:** solo **`v1.0.0`** (versión final 1.0) — ver [CHANGELOG](../CHANGELOG.md) y [Releases](https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/releases)

## Flujo de ramas

```text
feature → developer → PR → main
              ↓                      ↓
     Deploy Staging (FTP auto)   Deploy Production (manual en plan free)
```

- Trabaja en `developer` (o feature) → **Pull Request a `main`**.
- No subas secretos ni `wp-config` al repo.

## Branch protection (`main`)

Repo **público**: protection básica ya aplicada (sin force-push, sin borrar `main`, resolución de conversaciones).  
Aprobaciones obligatorias y status checks **no** están forzados todavía (flujo solo + CI con runners a veces inestable).

**Cuando el equipo > 1 y CI esté verde**, en Settings → Branches → `main`:

| Regla | Valor |
|-------|--------|
| Require a pull request before merging | Sí |
| Require approvals | **1** |
| Require review from Code Owners | Sí |
| Require status checks to pass | `Theme smoke / php-lint` |
| Require branches to be up to date | Sí |
| Allow force pushes | No |
| Allow deletions | No |

> Check exacto: **`Theme smoke / php-lint`**.

Deploy production sigue **manual** por defecto: Actions → **Deploy Production** → *Run workflow*.  
Opcional: variable `DEPLOY_ON_PUSH_MAIN` = `true`.

## Environments (ya creados: `staging`, `production`)

Settings → Environments. Secretos **por environment** (no en Actions genéricos del repo).

### Ambos: secrets

| Secret | Ejemplo |
|--------|---------|
| `FTP_SERVER` | `ftp.tudominio.com` |
| `FTP_USERNAME` | usuario FTP |
| `FTP_PASSWORD` | contraseña FTP |
| `FTP_SERVER_DIR_THEME` | `/public_html/wp-content/themes/yuniorrojastheme/` |
| `FTP_SERVER_DIR_PLUGIN` | `/public_html/wp-content/plugins/juniorrojas-core/` |

### Ambos: variables

| Variable | Uso |
|----------|-----|
| `SITE_URL` | URL del sitio (badge del deploy) |

Sin secretos FTP, el deploy falla con un mensaje claro (no sube a ciegas).

### Production en free

- Deploy **manual** por defecto (`workflow_dispatch`).
- Con Pro/público puedes añadir *Required reviewers* al environment.

### Culqi / SMTP

**Nunca** en GitHub Actions. Solo en el WP del hosting (`wp-config` o admin).

## Workflows

| Workflow | Trigger | Environment |
|----------|---------|---------------|
| Theme smoke | push/PR `main` y `developer` | — |
| Deploy Staging | push `developer` (paths) o manual | `staging` |
| Deploy Production | **manual** (o push main si `DEPLOY_ON_PUSH_MAIN=true`) | `production` |

## Issues y labels (ya)

Plantillas al crear issue:

- **Bug**
- **Feature**
- **Go-live checklist**

Labels: `bug`, `enhancement`, `go-live`, `deploy`, `pagos`, `security`.

PR template con checklist de CI y deploy.

## Checklist que te queda a ti (5 min)

1. [x] About + topics  
2. [x] Environments `staging` y `production`  
3. [ ] Secretos FTP en cada environment  
4. [ ] Variable `SITE_URL` en cada one  
5. [ ] (Opcional) Pro/público → branch protection + reviewers  
6. [ ] Primer deploy: *Actions → Deploy Staging / Production → Run*  

## Prioridad media (código + ops)

| Item | Estado |
|------|--------|
| PR template (Culqi, reservas, SMTP, backfill) | En repo |
| CODEOWNERS `@lincoleulogiohuanca` | En repo |
| Labels bug/pagos/frontend/deploy/security/… | Creados |
| Dependabot (GitHub Actions → `developer`) | `.github/dependabot.yml` |
| Dependabot alerts + security updates | Activados vía API |
| Secret scanning | No disponible en privado free (sí si se hace público o Pro) |
| CHANGELOG.md | Versionado + enlace a Releases |
| SECURITY.md | Política de secretos |

### Project kanban (1 minuto manual)

El token de Git del PC no tiene scope `project`. Créal o así:

1. Abre: https://github.com/users/lincoleulogiohuanca/projects  
2. **New project** → Template **Board**  
3. Nombre: `Barbería Junior Rojas`  
4. Renombra columnas Status a: **Backlog** → **En curso** → **Hecho**  
5. Settings → **Link a repository** → `BarberiaJuniorRojas`  
6. (Opcional) Workflow: issues nuevas van a Backlog  

URL típica: `https://github.com/users/lincoleulogiohuanca/projects/1` (el número puede variar).

## Troubleshooting

| Síntoma | Causa |
|---------|--------|
| Deploy “missing secrets” | Secretos vacíos en ese environment |
| Protection 403 | Plan free privado — ver sección arriba |
| FTP 550 | Ruta `FTP_SERVER_DIR_*` incorrecta |
| CI no corre en PR | Workflow debe estar en la rama destino (ya en main) |
