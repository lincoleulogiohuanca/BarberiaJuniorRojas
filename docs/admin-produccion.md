---
layout: default
title: Producción
---

# Producción y operación del servidor

Detalle técnico ampliado: [DEPLOY.md](https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/blob/main/DEPLOY.md).

## WordPress

1. Tema **yuniorrojastheme** activo  
2. Plugin **Junior Rojas Core** activo  
3. Permalinks y REST API funcionando  
4. Después del primer deploy del Core: **Herramientas → JR DB Backfill**

## Reservas → Producción

- Forzar HTTPS (si el hosting ya tiene SSL)
- SMTP: host, puerto, usuario, from  
- Probar con **Enviar correo de prueba**
- Preferible: cron real del sistema + `DISABLE_WP_CRON` en `wp-config`

## Secretos (solo servidor)

| Secreto | Dónde |
|---------|--------|
| Culqi secret | Ajustes Culqi o `YUNIORROJAS_CULQI_*` en wp-config |
| SMTP pass | Ajustes Producción o `YUNIORROJAS_SMTP_PASS` (no se seedéa a BD) |
| FTP deploy | GitHub Environments `staging` / `production` |

## Deploy desde GitHub

- **Staging:** push a `developer` (si hay secretos FTP)
- **Production:** *Actions → Deploy Production → Run workflow* (manual en plan free)

## ZIPs manuales

En cada [Release](https://github.com/lincoleulogiohuanca/BarberiaJuniorRojas/releases) se adjuntan:

- `yuniorrojastheme.zip`
- `juniorrojas-core.zip`

Sube por File Manager / FTP a `wp-content/themes` y `wp-content/plugins`.
