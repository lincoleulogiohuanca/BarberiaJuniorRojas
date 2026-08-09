# Barbería Junior Rojas (WordPress)

Sitio de reservas y pagos (Culqi / medios manuales) para **Barbería Junior Rojas**.

## Qué versiona este repositorio

| Incluido en Git | No incluido (no va a GitHub) |
|-----------------|------------------------------|
| Tema `yuniorrojastheme` | Core de WordPress (`wp-admin`, `wp-includes`, …) |
| Plugin `juniorrojas-post-types` | Plugins de terceros (ACF, etc.) |
| Docs de deploy | `wp-config.php` y secretos Culqi |
| | Base de datos (servicios, citas, usuarios) |
| | Medios subidos (`uploads/`) |
| | Carpeta Local (`conf/`, `logs/`) |

## Estructura relevante

```text
app/public/wp-content/
├── themes/yuniorrojastheme/     ← tema (UI, reservas, Culqi)
└── plugins/juniorrojas-post-types/
```

## Flujo de trabajo

```text
  PC (Local WP)  →  git commit / push  →  GitHub  →  Hosting (producción)
       desarrollo           versión del código              barberia.com
```

1. Desarrollas y pruebas en **Local**.
2. Cuando una función está lista: `commit` + `push` a GitHub.
3. En el hosting despliegas **solo el código** del tema/plugin (FTP, Git deploy o Action).
4. Contenido y datos reales (servicios, reservas, usuarios) viven en la **BD de producción**.

## Comandos Git habituales

```bash
# Ver cambios
git status
git diff

# Guardar un cambio
git add app/public/wp-content/themes/yuniorrojastheme
git commit -m "Describe el cambio en una frase"
git push origin main
```

## ¿GitHub actualiza el hosting solo?

**No automáticamente** hasta que conectes el hosting o un deploy:

1. **Manual:** subes el tema por FTP / File Manager después del `push`.
2. **Git en el hosting:** en cPanel/Hostinger “Git Version Control” conectas el repo y haces deploy.
3. **Automático (luego):** GitHub Actions con FTP/SSH al publicar en `main`.

Detalle en [DEPLOY.md](./DEPLOY.md).

## Requisitos en hosting

- WordPress instalado (misma o similar versión mayor que en local)
- Tema activo: `yuniorrojastheme`
- Plugin activo: `juniorrojas-post-types`
- Plugins que uses en local (ej. ACF) instalados también en producción
- Permalink y REST API funcionando
- Llaves Culqi de **producción** en *Reservas → Ajustes pagos* (no uses las de test en el sitio público)

## Desarrollo local

Proyecto pensado para **Local** (Flywheel): `Local Sites/barberia`.

No subas `wp-config.php` ni dumps SQL a GitHub.
