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
wp-content/plugins/juniorrojas-post-types/
```

No reemplaces a ciegas:

- `wp-config.php` de producción
- `wp-content/uploads/`
- la base de datos de producción

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

### C) GitHub Actions + FTP (automático, más adelante)

Cuando quieras que un `push` a `main` actualice el hosting solo, se puede añadir un workflow que publique por FTP/SSH con secretos en GitHub (`FTP_HOST`, `FTP_USER`, `FTP_PASSWORD`).  
Eso se configura cuando tengas los datos del hosting listos.

## 4. Base de datos

| En local | En producción |
|----------|----------------|
| Pruebas de reservas, usuarios test | Clientes reales, pagos reales |
| Servicios de prueba | Catálogo real |

Cambios de **código** → Git.  
Cambios de **contenido** (precios, páginas, menús) → se hacen en el admin del sitio que corresponda, o se exportan controlados (nunca sobrescribir prod con un dump de local sin backup).

## 5. Culqi

- Local / pruebas: llaves `pk_test_` / `sk_test_`
- Dominio público: llaves `pk_live_` / `sk_live_`

Configura en el admin de cada entorno (no en el repo).

## 6. Checklist post-deploy

- [ ] Tema y plugin activos  
- [ ] Página Reservar y permalinks  
- [ ] REST de reservas responde (logueado)  
- [ ] Culqi con llaves del entorno correcto  
- [ ] Medios de pago y QR Plin OK  
- [ ] SSL (https) activo  
