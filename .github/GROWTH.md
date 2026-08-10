# Política y roadmap “cuando crezca el equipo / producto”
# (Prioridad baja). Estado aplicado al repo + pasos manuales.

## Discussions

Habilitadas en el repo (ideas, Q&A, anuncios del equipo).

- Pestaña **Discussions** en GitHub  
- Categorías útiles: Ideas, Q&A, Anuncios  
- No uses Discussions para bugs urgentes → **Issues** con plantilla Bug  

Si no ves la pestaña: Settings → General → Features → **Discussions**.

## Documentación (`docs/`)

Preferimos **`docs/` versionada** a la Wiki de GitHub (la Wiki no pasa por PR/review).

| Archivo | Contenido |
|---------|-----------|
| `docs/index.md` | Índice del manual |
| `docs/admin-agenda.md` | Agenda y estados |
| `docs/admin-pagos.md` | Culqi / Plin / verificación |
| `docs/admin-fidelidad.md` | Niveles y descuentos |
| `docs/admin-produccion.md` | SMTP, Core, deploy, ZIPs |
| `docs/go-live.md` | Checklist |

### GitHub Pages

Workflow: **Docs Pages** (`.github/workflows/docs-pages.yml`).

1. Settings → Pages → Source: **GitHub Actions**  
2. Tras el primer run en `main`:  
   `https://lincoleulogiohuanca.github.io/BarberiaJuniorRojas/`  

**Nota plan free + repo privado:** Pages puede no publicarse hasta hacer el repo **público** o subir de plan. El contenido de `docs/` sigue disponible en el repo.

## Milestones

| Milestone | Uso |
|-----------|-----|
| **v1.1.0** | Mejoras menores post-lanzamiento (SMTP/FTP, UX, bugs) |
| **v1.2.0** | Features del siguiente ciclo (antes de un 2.0) |

Asigna issues a un milestone: Issue → Milestone → v1.1.0 / v1.2.0.

## Release assets (ZIP)

Workflow **Release assets**:

- Al **publicar** un Release, o  
- Manual: Actions → Release assets → Run workflow (tag)

Adjuntos típicos:

- `yuniorrojastheme.zip`  
- `juniorrojas-core.zip`  

Para `v1.0.0` ya publicado: corre el workflow con tag `v1.0.0` (o re-publica assets con *Run workflow*).

## Status checks + required reviews (equipo > 1)

Cuando haya **otro revisor** y el plan lo permita (Pro o público):

Settings → Branches → `main`:

| Regla | Valor |
|-------|--------|
| Require a pull request before merging | Sí |
| Require approvals | **1** (o más) |
| Require review from Code Owners | Sí (usa `.github/CODEOWNERS`) |
| Require status checks | `Theme smoke / php-lint` |
| Require conversation resolution | Sí |
| Allow force pushes | No |

Mientras el repo sea **privado free**, la API de branch protection devuelve 403: el flujo recomendado es PR + no pushear a `main` a mano.

## Wiki

**Desaconsejada** para este proyecto: no versiona bien. Usa `docs/` + Pages.
