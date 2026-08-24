# Análisis del Proyecto — Stack Tecnológico

**Proyecto:** ERP DRC (Dirección de Registro Civil)
**Fecha de revisión:** 2026-08-17
**Estado:** Pre-deploy

---

## 1. Stack tecnológico

| Capa | Tecnología | Detalle |
|---|---|---|
| **Backend** | PHP 8.2+ | Procedural + clases `Core\` (POO ligera); sin framework |
| **Base de datos** | MySQL / MariaDB | InnoDB, utf8mb4, ~15 tablas con llaves foráneas |
| **Acceso a datos** | PDO | Prepared statements reales (`ATTR_EMULATE_PREPARES = false`) |
| **Frontend** | Bootstrap 5 + FontAwesome 6 | Interfaz responsiva, tema claro/oscuro |
| **Tablas dinámicas** | DataTables.net | Server-side processing (paginación y búsqueda en servidor) |
| **Componentes** | Tom Select / Select2 | Búsquedas dinámicas en catálogos |
| **Alertas** | SweetAlert2 | Confirmaciones y errores |
| **Gráficas** | Chart.js | Dashboard estadístico |
| **PDF** | TCPDF | Actas y constancias |
| **QR** | chillerlan/php-qrcode | Códigos QR en actas |
| **Excel** | PhpSpreadsheet | Exportación con formato estricto de celdas |
| **Caché** | Redis → Memcached → archivos | Fallback automático (`core/Cache.php`) |
| **Jobs / cola** | Tabla `jobs` + worker CLI | Exportes pesados en segundo plano (`core/Worker.php`) |
| **Réplica de lectura** | Opcional | `DB_READ_*` en `.env` |
| **Tests** | PHPUnit (unit) + Playwright (E2E) | `tests/Unit/`, `tests/e2e/` |
| **Servidor** | Apache (XAMPP) | Protección vía `.htaccess` |

## 2. Organización del código

```
/DRC
├── core/          Clases base (Database, Auth, Cache, Encryption, RateLimiter,
│                  Auditoria, Catalogo, Worker, CronReport, Services/)
├── modules/       Módulos de negocio (13): nacimientos, matrimonios, divorcios,
│                  defunciones, inscripciones, reconocimientos, inexistencias,
│                  foraneas, peticiones, ciudadanos, curp, actas_locales, reportes
├── public/        Docroot web (index, login, auth, api/, exports/)
├── assets/        CSS/JS/frontend
├── docs/          Documentación y migraciones SQL
├── tests/         PHPUnit + Playwright
├── cache/         Caché de archivos (fallback)
├── logs/          Logs
└── vendor/        Dependencias Composer
```

## 3. Características

- **Sin framework**: PHP puro con PSR-4 para `Core\` (`composer.json`).
- **Dependencias Composer mínimas**: tcpdf, php-qrcode, phpspreadsheet (+ phpunit dev).
- **Configuración** vía `.env` (fuera del repositorio); credenciales desacopladas del código.
- **Frontend con CDN**: bootstrap, jquery, fontawesome, datatables desde jsdelivr — requiere internet en el cliente.
- **Windows/XAMPP specific**: los workers usan `popen("start /B c:\xampp\php\php.exe ...")` — no portable a Linux sin cambios.

## 4. Observaciones

1. PHP puro implica más código manual (no hay router, validador ni ORM) — el costo de mantenimiento crece con los módulos.
2. Las dependencias de CDN son un punto único de falla en redes aisladas de gobierno.
3. La ruta hardcodeada `c:\xampp\php\php.exe` en `core/Worker.php` debe parametrizarse para producción.