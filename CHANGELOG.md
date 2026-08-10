# Changelog

Todas las notas de versión relevantes de **Barbería Junior Rojas**.  
Formato inspirado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/)  
y versionado [SemVer](https://semver.org/lang/es/).

Los releases de GitHub enlazan a estas secciones:
https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/releases

---

## [Unreleased]

Cambios en `developer` / `main` aún no etiquetados.

### Planned / en curso
- Secrets FTP en environments de GitHub para deploy real
- Branch protection cuando el plan lo permita (Pro o repo público)

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

[Unreleased]: https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/releases/tag/v1.0.0
