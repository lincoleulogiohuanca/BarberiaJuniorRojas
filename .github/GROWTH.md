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

- **Source:** GitHub Actions (habilitado en el repo público).
- Workflow: **Docs Pages** (`.github/workflows/docs-pages.yml`).
- URL esperada: https://lincoleulogiohuanca.github.io/BarberiaJuniorRojas/

Si el sitio no aparece aún: Actions → **Docs Pages** → *Run workflow*.  
Si el job falla al **asignar runner** (`startup_failure` / 0 steps), es un fallo de la plataforma GitHub; reintenta más tarde. El manual sigue en `docs/` del repo.

## Milestones

| Milestone | Uso | Estado |
|-----------|-----|--------|
| **v1.0.0** | Release final consolidada (BarberFlow) | **Release** — ver [CHANGELOG 1.0.0](../CHANGELOG.md#100--2026-08-10) |
| **v1.1.0** | Siguiente ciclo (features post-1.0) | Abierto |

Asigna issues a un milestone: Issue → Milestone → v1.0.0 / v1.1.0.

### Issues / labels sugeridos post-1.0.0

| Label | Uso |
|-------|-----|
| `enhancement` | Mejoras de producto |
| `bug` | Defectos |
| `frontend` | Tema público |
| `deploy` | FTP / staging / production |

## Release assets (ZIP)

Workflow **Release assets** (al publicar un Release o *Run workflow* con tag).

Adjuntos esperados por release:

- `yuniorrojastheme.zip`
- `juniorrojas-domain.zip`
- `juniorrojas-reservas.zip`
- `juniorrojas-pagos.zip`
- `juniorrojas-core.zip`

| Tag | Notas |
|-----|--------|
| **v1.0.0** | Versión final 1.0 — [release](https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/releases/tag/v1.0.0) |

(si Actions falla al subir, se pueden adjuntar a mano: Release → Edit → Attach binaries).

## Status checks + required reviews (equipo > 1)

**Hoy (solo / 1 persona):** protection en `main` sin forzar 1 aprobación ni status check (evita bloquear merges si CI no arranca). Sí: sin force-push, sin borrar la rama, resolución de conversaciones.

**Cuando haya otro revisor y CI estable**, subir la protection:

| Regla | Valor |
|-------|--------|
| Require a pull request before merging | Sí |
| Require approvals | **1** (o más) |
| Require review from Code Owners | Sí (`.github/CODEOWNERS`) |
| Require status checks | `Theme smoke / php-lint` |
| Require conversation resolution | Sí |
| Allow force pushes | No |

## Wiki

**Desaconsejada** para este proyecto: no versiona bien. Usa `docs/` + Pages.
