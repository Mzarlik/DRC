# Reportes Periódicos y Filtros (Fase 15)

Este plan detalla el diseño e implementación del sistema de procesamiento asíncrono para la exportación de reportes (para evitar congelar el navegador), el panel de reportes con filtros visuales apilables y el script de ejecución cron para informes semanales automáticos por correo en formato PDF.

## User Review Required

> [!IMPORTANT]
> **Cola de Trabajos en Base de Datos (Jobs Table)**
> Para dar soporte a operaciones asíncronas en entornos PHP sin requerir daemons avanzados como RabbitMQ (los cuales no siempre están preinstalados en servidores de dependencias públicas), implementaremos una cola estructurada basada en la tabla `jobs` en MySQL. El procesamiento se ejecutará en segundo plano mediante un CLI script (`core/Worker.php`) que el servidor web activará de manera asíncrona ("fire-and-forget") al crearse un trabajo.
>
> **Simulación de Envío de Correos en Cron**
> La tarea cron (`core/CronReport.php`) generará un PDF oficial mediante la librería `TCPDF` utilizando las métricas de `api/stats.php`. Al no contar con un servidor SMTP real garantizado en el entorno local, el script simulará el envío de correo registrando detalladamente la cabecera, destinatarios directivos y archivo adjunto en `logs/cron_email.log`, a la par que guarda el PDF generado en `public/reports/`.

---

## Proposed Changes

### 1. Base de Datos y Configuración
Garantizar la estructura de datos para la gestión de tareas de fondo y el token seguro para cron jobs.

#### [NEW] [migration_queue_reportes.php](file:///c:/xampp/htdocs/DRC/docs/migration_queue_reportes.php)
- Crear la tabla `jobs`:
  - `id` (INT AUTO_INCREMENT PRIMARY KEY)
  - `user_id` (INT)
  - `type` (VARCHAR(50)) - ej. `export_inexistencias` o `export_general_report`.
  - `payload` (TEXT) - Parámetros de filtros en formato JSON.
  - `status` (VARCHAR(20)) - `pending`, `processing`, `completed`, `failed`.
  - `file_path` (VARCHAR(255)) - Ruta web para la descarga del archivo generado.
  - `error_message` (TEXT) - Detalles de error si el job falla.
  - `created_at` y `updated_at` (TIMESTAMPS).

#### [MODIFY] [.env](file:///c:/xampp/htdocs/DRC/.env) & [.env.example](file:///c:/xampp/htdocs/DRC/.env.example)
- Añadir variable `CRON_SECRET=drc_erp_weekly_cron_secret_key_2026` para autenticar de forma segura peticiones externas de tareas programadas (como extracción de estadísticas de `api/stats.php`).

#### [MODIFY] [Auth.php](file:///c:/xampp/htdocs/DRC/core/Auth.php)
- Modificar el método `check()` para permitir el bypass de la redirección de login si el parámetro GET `cron_token` coincide con la llave `CRON_SECRET` definida en el archivo `.env`.

---

### 2. Cola de Trabajo Asíncrona (Queue & Worker)
Implementar el orquestador y ejecutor de tareas asíncronas.

#### [NEW] [Worker.php](file:///c:/xampp/htdocs/DRC/core/Worker.php)
- Script ejecutable vía CLI que:
  - Recupera de la base de datos trabajos en estatus `pending`.
  - Modifica su estatus a `processing`.
  - Deserializa el `payload` (filtros).
  - Procesa la generación de la hoja de cálculo de Excel mediante `PhpSpreadsheet` (escribiendo celdas y protegiendo tipos numéricos o alfanuméricos como string).
  - Guarda el archivo `.xlsx` en `public/exports/` con nombres únicos.
  - Actualiza el estatus del job a `completed` y guarda su ruta en `file_path`.
  - Registra el suceso en la `bitacora_auditoria` imitando el ID del usuario creador del job.
  - Maneja excepciones actualizando el job a `failed` con su respectivo mensaje.

---

### 3. Integración de Notificaciones para Jobs
Actualizar la API de notificaciones para que el usuario sea alertado al finalizar su reporte.

#### [MODIFY] [notifications.php](file:///c:/xampp/htdocs/DRC/public/api/notifications.php)
- Consultar los jobs del usuario activo en estatus `completed` o `failed` actualizados en los últimos minutos.
- Añadir dinámicamente notificaciones al listado con un botón / link directo de descarga:
  `<a href="../../public/exports/archivo.xlsx" download>Descargar aquí</a>`.

---

### 4. Módulo de Reportes con Filtros Apilables (Stackable Filters)
Crear la interfaz interactiva para reportes cruzados complejos.

#### [NEW] [index.php (Reportes)](file:///c:/xampp/htdocs/DRC/modules/reportes/index.php)
- Panel visual de filtros: *Fecha Inicio*, *Fecha Fin*, *Módulo*, *Estatus* y *Operador*.
- Sección `#activeFilters` que genera "badges" visuales (ej. `[Módulo: Defunciones (x)]`). Al hacer clic en la `(x)`, se limpia el filtro correspondiente, se remueve el badge y se refresca la tabla asíncronamente.
- Botón **Exportar a Excel**: Dispara un AJAX seguro que registra el job en base de datos en segundo plano y muestra SweetAlert2 con el mensaje interactivo.
- Tabla DataTables conectada a `data.php` con esqueleto shimmer predeterminado.

#### [NEW] [data.php (Reportes)](file:///c:/xampp/htdocs/DRC/modules/reportes/data.php)
- Endpoint que realiza consultas cruzadas mediante uniones (`UNION ALL`) de los módulos (nacimientos, defunciones, inexistencias, foráneas, etc.) o consultas específicas si se selecciona un único módulo.
- Aplica los filtros apilables (`fecha_inicio`, `fecha_fin`, `estatus`, `usuario_registro`) de forma dinámica en la base de datos de réplica.
- Soporta paginación del servidor (ServerSide) compatible con DataTables.

#### [NEW] [export_excel.php (Reportes)](file:///c:/xampp/htdocs/DRC/modules/reportes/export_excel.php)
- Endpoint que inserta el job `export_general_report` y arranca asíncronamente al Worker mediante `popen` de Windows.

---

### 5. Adaptación en Inexistencias
Migrar la exportación de inexistencias para ser asíncrona.

#### [MODIFY] [export_excel.php (Inexistencias)](file:///c:/xampp/htdocs/DRC/modules/inexistencias/export_excel.php)
- Modificar para que registre el job `export_inexistencias`, lance el Worker asíncronamente y responda con JSON.

#### [MODIFY] [index.php (Inexistencias)](file:///c:/xampp/htdocs/DRC/modules/inexistencias/index.php)
- Cambiar la acción del botón de exportar de redirección directa a petición AJAX con SweetAlert2 de notificación.

---

### 6. Tarea Programada para Reportes Semanales (Cron Job)
Automatizar la consolidación semanal en PDF.

#### [NEW] [CronReport.php](file:///c:/xampp/htdocs/DRC/core/CronReport.php)
- Script ejecutable vía CLI (para ser llamado por el Cron de Linux o Programador de Tareas):
  - Consume la API local de estadísticas llamando a `public/api/stats.php?cron_token=CRON_SECRET` vía cURL/HTTP o archivo local.
  - Compila los resultados (indicadores clave y tendencias semanales).
  - Diseña un documento PDF formal con `TCPDF` que incluye membrete del Registro Civil, fecha de generación y tablas estadísticas.
  - Guarda el PDF en `public/reports/`.
  - Simula el envío por correo escribiendo la cabecera y el log de éxito en `logs/cron_email.log`.

---

## Verification Plan

### Automated Tests
- Correr pruebas unitarias backend: `c:\xampp\php\php.exe vendor/bin/phpunit`
- Validar sintaxis con `php -l`.

### Manual Verification
1. **Cola de Reportes (Asíncrono)**:
   - Ingresar a Constancias e intentar exportar a Excel.
   - Validar que aparezca inmediatamente una alerta SweetAlert2 indicando que el reporte se procesa en segundo plano y la pantalla NO se congele.
   - Abrir el menú de notificaciones y confirmar que aparezca el enlace de descarga ("Descargar aquí") una vez completado el job.
2. **Filtros Apilables**:
   - Ingresar al módulo de reportes cruzados y seleccionar múltiples filtros.
   - Confirmar que aparezcan los badges de filtros activos debajo de las entradas de selección.
   - Hacer clic en la `(x)` de un badge y verificar que el control vuelva a su estado vacío y la tabla se actualice dinámicamente.
3. **Cron Semanal**:
   - Ejecutar el script por CLI manualmente: `c:\xampp\php\php.exe core/CronReport.php`.
   - Validar la creación del archivo PDF en `public/reports/` y el registro de simulación de correo en `logs/cron_email.log`.
