# Despliegue: Local → GitHub → Hosting

## 1. Publicar código a GitHub (una vez)

Si aún no existe el remoto:

1. Crea un repositorio en GitHub (privado recomendado), por ejemplo: `barberia-junior-rojas`.
2. **No** marques “Add README” si ya tienes commits locales (o haz `pull --rebase` después).
3. En esta carpeta del proyecto:

```bash
git remote add origin https://github.com/TU_USUARIO/barberia-junior-rojas.git
git branch -M main
git push -u origin main
```

Si usas SSH:

```bash
git remote add origin git@github.com:TU_USUARIO/barberia-junior-rojas.git
git push -u origin main
```

Cada cambio posterior:

```bash
git add .
git status
git commit -m "Mensaje claro del cambio"
git push
```

## 2. Qué copiar al hosting

En el servidor WordPress, reemplaza/actualiza **solo**:

```text
wp-content/themes/yuniorrojastheme/
wp-content/plugins/juniorrojas-domain/
wp-content/plugins/juniorrojas-reservas/
wp-content/plugins/juniorrojas-pagos/
wp-content/plugins/juniorrojas-core/
wp-content/plugins/juniorrojas-post-types/   (opcional / legacy)
```

No reemplaces a ciegas:

- `wp-config.php` de producción
- `wp-content/uploads/`
- la base de datos de producción
- **`app/public/local-xdebuginfo.php`** (no existe en el tema; no lo subas)
## 3. Opciones de deploy según hosting

### A) Hostinger / cPanel — Git Version Control

1. En el panel: Git → Create / Clone el repo.
2. Ruta de destino: o bien clonas en una carpeta y copias el tema, o usas un deploy script.
3. Tras cada `git push`, en el panel pulsas **Deploy** (o “Pull”).

Flujo:

```text
Local → push → GitHub → Deploy en panel → archivos en el servidor
```

### B) FTP / SFTP (simple)

1. `git push` a GitHub (backup y historial).
2. Con FileZilla u otro cliente, subes la carpeta del tema y del plugin.

### C) GitHub Actions + FTP (automático)

Workflows en `.github/workflows/`:

| Branch | Workflow | Environment |
|--------|----------|-------------|
| `developer` | Deploy Staging | `staging` |
| `main` | Deploy Production | `production` (requiere **aprobación** manual) |

Configura secretos y aprobación en [`.github/GITHUB_SETUP.md`](./.github/GITHUB_SETUP.md).

Resumen de secrets **por environment** (Settings → Environments):

- `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`
- `FTP_SERVER_DIR_THEME` → p.ej. `/public_html/wp-content/themes/yuniorrojastheme/`
- `FTP_SERVER_DIR_PLUGIN` → p.ej. `/public_html/wp-content/plugins/juniorrojas-domain/
wp-content/plugins/juniorrojas-reservas/
wp-content/plugins/juniorrojas-pagos/
wp-content/plugins/juniorrojas-core/`

Culqi/SMTP **no** van en GitHub Actions (solo en el WP del servidor).

Flujo:

```text
push developer → CI smoke → FTP staging
PR a main → CI smoke obligatorio
merge main → CI + deploy production (espera tu Approve en GitHub)
```

## 4. Base de datos

| En local | En producción |
|----------|----------------|
| Pruebas de reservas, usuarios test | Clientes reales, pagos reales |
| Servicios de prueba | Catálogo real |

Cambios de **código** → Git.  
Cambios de **contenido** (precios, páginas, menús) → se hacen en el admin del sitio que corresponda, o se exportan controlados (nunca sobrescribir prod con un dump de local sin backup).

## 5. Culqi

- Local / pruebas: llaves `pk_test_` / `sk_test_` en **Reservas → Ajustes Culqi** (o constantes `YUNIORROJAS_CULQI_*` en `wp-config.php`).
- Dominio público: llaves `pk_live_` / `sk_live_` **obligatorias**.
- Tras un cobro fallido al guardar la cita, el tema intenta **reembolso automático** del cargo Culqi.
- Cargos usan **idempotency key** (no doble cobro en retry/timeout).
- Webhook (plugin Core): `POST /wp-json/yuniorrojas/v1/culqi/webhook` + secreto opcional.
- Rechazo de pago en admin (y cancelación admin) también dispara reembolso si hay `culqi_charge_id`.
- Gold/Platinum aplican **descuento online** en el monto Culqi (5% / 10% por defecto; editable en Fidelidad).

## 5b. BarberFlow Pro (plugin Core)

1. Activa **BarberFlow Pro** (y el resto del stack: Base, Book, Pay) en el admin.
2. Crea tablas `wp_jr_reservas`, `wp_jr_slot_locks`, `wp_jr_culqi_idempotency`.
3. Si ya había reservas: **Herramientas → BarberFlow DB Backfill**.
4. Los locks de horario y la disponibilidad usan la tabla indexada.
## 6. Producción (HTTPS, SMTP, cron, backups)

Pantalla: **Reservas → Producción**

| Ítem | Acción |
|-------|--------|
| HTTPS | Certificado en hosting + opcional “Forzar HTTPS” en el panel |
| SMTP | Activar en Producción **o** constantes `YUNIORROJAS_SMTP_*` en `wp-config.php` (mismo patrón que Culqi) |
| Cron real | `define('DISABLE_WP_CRON', true);` en wp-config + cron del servidor a `wp-cron.php` cada 5–15 min |
| Backups | cPanel/Hostinger o plugin (UpdraftPlus) — no lo hace el tema |
| Smoke tests | `wp eval-file wp-content/themes/yuniorrojastheme/tests/smoke-prod.php` |

### SMTP (prioridad)

1. Lo guardado en **Reservas → Producción**
2. Constantes en `wp-config.php` (fallback / seed automático si el campo está vacío)

Ejemplo en `wp-config.php` (antes de `/* That's all, stop editing! */`):

```php
// SMTP (emails de reserva, contacto, lista de espera)
define('YUNIORROJAS_SMTP_ENABLED', true);
define('YUNIORROJAS_SMTP_HOST', 'smtp.tuservidor.com');
define('YUNIORROJAS_SMTP_PORT', 587);
define('YUNIORROJAS_SMTP_ENCRYPTION', 'tls'); // tls | ssl | none
define('YUNIORROJAS_SMTP_USER', 'noreply@tudominio.com');
define('YUNIORROJAS_SMTP_PASS', 'tu-password-o-app-password');
define('YUNIORROJAS_SMTP_FROM', 'noreply@tudominio.com');
define('YUNIORROJAS_SMTP_FROM_NAME', 'Junior Rojas Barbería');
```

- En el panel puedes sobrescribir cualquier campo; al guardar, el admin gana.
- `YUNIORROJAS_SMTP_PASS` no se copia a la base de datos: se usa solo en runtime (más seguro).
- Host/user/from sí pueden seedearse a la option si estaban vacíos.
- Botón **Enviar correo de prueba** en Producción para validar host/credenciales.

Cron ejemplo (Linux):

```bash
*/10 * * * * curl -s https://TU-DOMINIO.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

## 7. Checklist post-deploy

- [ ] Tema y plugin activos  
- [ ] Página Reservar y permalinks  
- [ ] REST de reservas responde (logueado)  
- [ ] Culqi con llaves del entorno correcto (live en producción)  
- [ ] Medios de pago y QR Plin OK  
- [ ] SSL (https) activo  
- [ ] SMTP probado (confirmación de reserva llega al correo)  
- [ ] Cron del sistema activo (`DISABLE_WP_CRON` + crontab)  
- [ ] Backup de BD configurado  
- [ ] QA: tarjeta Culqi, Plin, estudio, cancelar, reprogramar, doble clic en pagar  
- [ ] Productos en checkout (si hay publicados en CPT Productos)  
- [ ] Reseñas: llegan como “pendiente” hasta publicar en admin  
- [ ] Smoke: `wp eval-file .../tests/smoke-prod.php`  
