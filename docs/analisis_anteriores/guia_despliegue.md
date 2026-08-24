# Guía de Despliegue en Producción — ERP DRC

Documento dirigido a TI/DevOps. Complementa el análisis en [`docs/analisis/ARQUITECTURA_ESCALABILIDAD.md`](analisis/ARQUITECTURA_ESCALABILIDAD.md) y el [`docs/analisis/CHECKLIST_PRE_DEPLOY.md`](analisis/CHECKLIST_PRE_DEPLOY.md).

---

## 1. Ajustes previos obligatorios (seguridad)

| # | Ajuste | Cómo |
|---|---|---|
| 1 | Generar llaves secretas nuevas | `ENCRYPTION_KEY` y `CRON_SECRET` en `.env` (NO usar las de `.env.example`). |
| 2 | Cambiar contraseña del admin | Entrar con `admin@drc.gob.mx` / `Admin123!` y cambiarla en **Mi perfil** inmediatamente. |
| 3 | Parametrizar la ruta de PHP del worker | `core/Worker.php` usa `c:\xampp\php\php.exe` hardcodeado. En Linux: `/usr/bin/php` (o leer de `.env`). |
| 4 | HTTPS obligatorio | Redirigir todo el tráfico a HTTPS (apache: `Redirect permanent / https://...` o `RewriteCond %{HTTPS} off`). |
| 5 | Endurecer `.htaccess` | Revisar que bloquea `.env`, `core/`, `docs/`, `composer.*`, `.git*`. Si el docroot apunta a `public/`, el riesgo se reduce. |
| 6 | Rotar sesiones | El sistema ya rota IDs de sesión en login/cambio de contraseña/cambio de permisos; verificar `session.cookie_secure=1` y `session.cookie_httponly=1`. |
| 7 | CDN | El frontend carga Bootstrap/jQuery/DataTables/Chart.js desde jsDelivr. En redes aisladas del gobierno, **hostear estos assets localmente** (ver `docs/analisis/STACK.md` §4). |

---

## 2. Servidor web (Apache vs Nginx)

### Opción A — Docroot = raíz del proyecto (como en XAMPP)

- Funciona tal cual: `.htaccess` ya bloquea `core/`, `docs/`, `.env`, etc.

### Opción B — Docroot = `public/` (recomendada en producción)

La más segura: solo `public/`, `modules/`, `assets/` son alcanzables. Ajustes:

```
# Apache vhost
DocumentRoot "C:\...\DRC\public"
<Directory "C:\...\DRC\public">
    AllowOverride All
    Require all granted
</Directory>
Alias /modules "C:\...\DRC\modules"
Alias /assets  "C:\...\DRC\assets"
```

> **Atención:** los módulos viven en `modules/` y referencian `../core/` y `../assets/`. Validar rutas relativas antes de mover el docroot; en el despliegue actual se recomienda mantener la **Opción A** salvo pruebas exhaustivas.

---

## 3. Base de datos

- **Charset:** crear la base con `utf8mb4` / `utf8mb4_unicode_ci` (como en `docs/database.sql`).
- **Usuario dedicado** (no `root`):
  ```sql
  CREATE USER 'drc_app'@'localhost' IDENTIFIED BY '<password-fuerte>';
  GRANT SELECT, INSERT, UPDATE, DELETE ON drc_erp.* TO 'drc_app'@'localhost';
  ```
- **Réplica de lectura (opcional):** configurar `DB_READ_HOST`, `DB_READ_NAME`, `DB_READ_USER`, `DB_READ_PASS` en `.env`. Si no se declaran, el sistema usa la conexión principal.
- **Índices:** confirmar los de la [Fase 10](planes_implementacion/fase_10_indices_borrado_env.md) (`ciudadanos.curp` UNIQUE, búsquedas por nombre).

---

## 4. Caché (Redis / Memcached / archivos)

`core/Cache.php` intenta en orden: **Redis → Memcached → archivos** (`cache/`).

- **Redis:** instalar el servicio y la extensión de PHP (`php_redis.dll` en Windows / `php-redis` en Linux).
- **Memcached:** igual que Redis.
- Sin ninguno, funciona con archivos; garantizar que `cache/` sea escribible y esté respaldado (no crítico: se regenera).

---

## 5. Procesos en segundo plano (Worker y reportes)

La cola de exportaciones (`jobs`) y el reporte semanal requieren ejecución periódica del CLI.

### Windows (Programador de tareas)

| Tarea | Programa | Argumentos | Disparador |
|---|---|---|---|
| Worker | `C:\PHP\php.exe` (o XAMPP) | `C:\...\DRC\core\Worker.php` | Cada minuto |
| Reporte semanal | ídem | `C:\...\DRC\core\CronReport.php` | Lunes 07:00 |

> `CronReport.php` genera el PDF en `public/reports/` y escribe `logs/cron_email.log` (simula envío de correo; integrar un MTA real para producción).

### Linux (cron)

```cron
* * * * * /usr/bin/php /var/www/DRC/core/Worker.php >> /var/log/drc_worker.log 2>&1
0 7 * * 1 /usr/bin/php /var/www/DRC/core/CronReport.php >> /var/log/drc_cron.log 2>&1
```

**Permisos de escritura requeridos (web + CLI):**

```
cache/            (caché de archivos)
logs/             (logs y simulación de correo)
public/exports/   (archivos .xlsx de la cola)
public/reports/   (PDF del reporte semanal)
```

---

## 6. Seguridad perimetral recomendada

1. **WAF / reglas de módulo:** bloquear `public/validate.php` de bots (tiene rate limiting propio por diseño del flujo), o proteger con reCAPTCHA si es público.
2. **Headers HTTP** (apache `Header set ...`):
   - `X-Content-Type-Options: nosniff`
   - `X-Frame-Options: DENY`
   - `Referrer-Policy: strict-origin-when-cross-origin`
   - `Content-Security-Policy` básica (recordando que el frontend usa CDN).
3. **Backups:** ver [`respaldo_recuperacion.md`](respaldo_recuperacion.md).
4. **Monitoreo:** revisar `logs/` y la pestaña **Auditoría → Errores** semanalmente (roadmap contempla Sentry/Monolog).

---

## 7. Verificación post-despliegue

Ejecutar [`docs/analisis/CHECKLIST_PRE_DEPLOY.md`](analisis/CHECKLIST_PRE_DEPLOY.md) y además:

1. Login + cambio de contraseña del admin.
2. Crear un ciudadano y un nacimiento; verificar auditoría (`auditoria_logs`).
3. Disparar una exportación de Excel y confirmar notificación + archivo en `public/exports/`.
4. Buscar un ciudadano por CURP (prueba de descifrado determinista).
5. Acceso directo por URL a `core/Database.php` → debe dar 403.
6. `composer dump-autoload -o` tras el despliegue.

---

## 8. Rollback

1. Restaurar respaldo previo de BD (`mysqldump`).
2. Restaurar el código anterior (git tag/branch o copia).
3. Revisar migraciones aplicadas (`docs/migration_*.php` validan idempotencia; si el rollback quita tablas, ejecutar los scripts de la versión restaurada).
4. Limpiar `cache/`.