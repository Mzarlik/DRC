# ERP DRC — Dirección de Registro Civil

Sistema ERP modular en PHP para la gestión, control y automatización de trámites de la Dirección de Registro Civil.

- **Backend:** PHP 8.2+ (sin framework, PSR-4 para `Core\`)
- **Base de datos:** MySQL / MariaDB (InnoDB, utf8mb4)
- **Frontend:** Bootstrap 5 + FontAwesome 6 + DataTables (server-side) + SweetAlert2 + Chart.js
- **Documentos:** TCPDF (PDF) · PhpSpreadsheet (Excel) · chillerlan/php-qrcode (QR)
- **Caché:** Redis → Memcached → archivos (fallback automático)
- **Tests:** PHPUnit (unit) + Playwright (E2E)

---

## Requisitos

| Requisito | Versión mínima |
|---|---|
| PHP | 8.2+ (con extensiones `pdo_mysql`, `openssl`, `mbstring`, `zip`, `gd`) |
| MySQL / MariaDB | 5.7 / 10.4+ |
| Apache | 2.4 (con `mod_rewrite`) |
| Composer | 2.x |
| Node.js | 18+ (solo para tests E2E) |
| Redis / Memcached | Opcional (caché en RAM; sin ellos se usa caché de archivos) |

---

## Instalación (XAMPP / Windows)

1. **Copiar el proyecto** dentro del docroot de Apache:

   ```
   C:\xampp\htdocs\DRC
   ```

2. **Crear la base de datos** importando el script SQL inicial en phpMyAdmin o por CLI:

   ```bash
   mysql -u root -p < docs/database.sql
   mysql -u root -p < database_auditoria.sql
   ```

   El script crea la base `drc_erp`, las tablas base y el usuario administrador por defecto.

3. **Configurar el entorno**: copiar `.env.example` como `.env` en la raíz del proyecto y ajustar credenciales:

   ```ini
   DB_HOST=127.0.0.1
   DB_NAME=drc_erp
   DB_USER=root
   DB_PASS=
   DB_CHARSET=utf8mb4
   ENCRYPTION_KEY=reemplace-con-una-clave-secreta-larga-y-unica
   CRON_SECRET=reemplace-con-un-token-secreto
   ```

   > **Importante:** cambiar `ENCRYPTION_KEY` y `CRON_SECRET` en producción. Si se cambia la llave de cifrado después de haber registrado CURPs, estos no podrán desencriptarse. `DB_READ_*` es opcional (réplica de solo lectura).

4. **Instalar dependencias de PHP** (desde la raíz del proyecto):

   ```bash
   composer install
   ```

5. **Permisos de escritura** (Windows): asegurar que Apache/IIS pueda escribir en:

   ```
   cache/
   logs/
   public/exports/
   public/reports/
   ```

6. **Acceder al sistema**:

   ```
   http://localhost/DRC/public/login.php
   ```

   Credenciales por defecto (¡cambiar el password tras el primer inicio!):

   | Correo | Password | Rol |
   |---|---|---|
   | `admin@drc.gob.mx` | `Admin123!` | ADMIN |

---

## Comandos útiles

| Tarea | Comando |
|---|---|
| Procesar cola de exportaciones (jobs) | `php core/Worker.php` |
| Reporte semanal (PDF) | `php core/CronReport.php` |
| Tests unitarios | `vendor\bin\phpunit` |
| Tests E2E | `npm install && npm run test:e2e` |

Para programar el worker cada minuto y el reporte semanal en Windows puede usarse el Programador de tareas; en Linux, `cron`:

```cron
* * * * * /usr/bin/php /ruta/al/proyecto/core/Worker.php
0 7 * * 1  /usr/bin/php /ruta/al/proyecto/core/CronReport.php
```

---

## Rutas principales

| Ruta | Descripción |
|---|---|
| `public/login.php` | Inicio de sesión |
| `public/index.php` | Dashboard con estadísticas |
| `public/usuarios.php` | Administración de usuarios y permisos (ADMIN) |
| `public/auditoria.php` | Bitácora de auditoría y errores (ADMIN) |
| `public/catalogos.php` | Catálogos dinámicos (ADMIN/SUPERVISOR) |
| `public/perfil.php` | Mi perfil (cambio de datos y contraseña) |
| `modules/<modulo>/` | Módulos de negocio (13) |
| `public/validate.php` | Validación pública de actas vía token |

---

## Documentación

| Documento | Contenido |
|---|---|
| [`CONTEXTO.md`](CONTEXTO.md) | Contexto del proyecto y reglas de negocio |
| [`ROADMAP.md`](ROADMAP.md) | Blueprint, arquitectura y fases |
| [`docs/manual_usuario.md`](docs/manual_usuario.md) | Manual de uso para operadores y administradores |
| [`docs/api_referencia.md`](docs/api_referencia.md) | Referencia de endpoints |
| [`docs/esquema_bd.md`](docs/esquema_bd.md) | Esquema de base de datos |
| [`docs/guia_despliegue.md`](docs/guia_despliegue.md) | Despliegue en producción |
| [`docs/respaldo_recuperacion.md`](docs/respaldo_recuperacion.md) | Respaldos y recuperación |
| [`docs/versions.md`](docs/versions.md) | Historial de versiones (Changelog) |
| [`TESTING_SECURITY.md`](TESTING_SECURITY.md) | Seguridad y pruebas |

---

## Seguridad (resumen)

- Credenciales fuera del repositorio (`.env`, ignorado por git).
- Acceso perimetral vía `.htaccess` (bloquea `.env`, `core/`, `docs/`, `composer.*`).
- Sesiones PHP con rotación de ID (`session_regenerate_id(true)`).
- Tokens CSRF en formularios de guardado.
- Rate limiting anti-scraping en búsquedas y login.
- Cifrado determinista AES-256-CBC para CURP.
- Bitácora de auditoría de todas las operaciones CRUD.

Ver [`TESTING_SECURITY.md`](TESTING_SECURITY.md) y [`docs/analisis/SEGURIDAD_AUDITORIA.md`](docs/analisis/SEGURIDAD_AUDITORIA.md) para el detalle.