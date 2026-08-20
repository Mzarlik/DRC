# Historial de Versiones (Changelog)

Este documento registra todos los cambios notables, actualizaciones y correciones del sistema ERP para la Dirección de Registro Civil.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

## [1.5.0] - 2026-08-20
### Añadido
- **Diseño Móvil y Responsive Ultra-Compacto:** Optimización global en `assets/css/style.css` y `assets/js/global.js` reduciendo la altura vertical de las tarjetas móviles (`.mobile-record-card`) en más de un 55% (~150px vs ~380px previo), permitiendo visualizar de 2 a 3 registros simultáneos por pantalla.
- **Barra de Acciones Flotante Inferior Ergonómica (`.mobile-action-bar`):** Fijación inferior moderna con desenfoque de cristal (*backdrop blur*), área segura móvil (*safe area inset*), sombra elevada y distribución proporcional de botones de acción rápida (*Exportar* y *+ Nuevo / Registrar*).
- **Segmented Controls y Pestañas Responsivas:** Pestañas dobles (`.nav-pills`, `.nav-tabs`) con distribución automática 50%/50% en celulares y scroll horizontal táctil suave para menús multivariables.
- **Distribución de Botones de Filtro 50/50:** Formularios de búsqueda y filtrado de auditoría y módulos con botones de *Filtrar* y *Limpiar* alineados en la misma fila en dispositivos móviles.
- **Densidad Optimizada de Tablas:** Reducción del padding estándar de DataTables a `7px 12px` en celdas y `8px 12px` en encabezados para un aprovechamiento superior del espacio en escritorio y móvil.

## [1.4.0] - 2026-08-18
### Añadido
- **Rol COORDINADOR y banderas de ventanilla/exportación:** migración `docs/migration_turnos_ventanilla.php` agrega el rol `COORDINADOR` y las banderas `permiso_exportar`, `permiso_peticiones_rapidas`, `permiso_turnos`; `update_usuario.php`/`usuarios.php` incorporan el nuevo rol y las banderas (ADMIN fuerza todas a 1; COORDINADOR/SUPERVISOR siempre exportan).
- **Módulo Petición Rápida de Ventanilla (`modules/peticion_rapida`):** registro de peticiones de acta foránea o constancia vinculadas al ciudadano (FK + búsqueda), folio `VP-AAAA-NNNNNN` con `Database::generateFolio()`, catálogo `tipo_peticion_ventanilla`, DataTables server-side, estatus PENDIENTE/ENTREGADO/CANCELADO y ticket imprimible con CURP desencriptada.
- **Sistema de Turnos (`modules/turnos` + `public/turnos.php`):** generación de turnos con folio `VT-AAAA-NNNNNN`, tablero de atención (en espera / en atención / atendidos hoy, polling 15 s), transiciones EN_ESPERA→ATENDIENDO→COMPLETADO/CANCELADO registrando ventanilla y usuario, ticket imprimible y pantalla pública de exhibición con auto-refresco (`public/api/turnos_pantalla.php`).
- **Exportación restringida a coordinadores:** `Auth::checkExport()` protege los 14 endpoints de exportación (11 módulos + usuarios/auditoría/errores): solo `canExportar()` (COORDINADOR/SUPERVISOR/ADMIN o bandera `permiso_exportar`) y deja registro en auditoría (`Exportaciones / EXPORTAR`); los botones de "Exportar consulta a Excel" solo se renderizan para usuarios autorizados.
- **Errores legibles para el usuario/administrador:** `Core\Services\ErrorMessages` traduce errores comunes de BD (conexión 2002, credenciales 1045, claves duplicadas 23000, columnas/consultas 42S22/42S02, deadlock 1205, overflow 22001/22003) a mensajes entendibles; `Auditoria::logError()` guarda el mensaje humano en `error_logs.mensaje` y el detalle técnico en `stack_trace` con marcador `[MENSAJE ORIGINAL]`.
- **Filtros de auditoría:** selectores de módulo y acción (server-side) en `public/auditoria.php`; el modal de errores muestra el mensaje técnico original por separado del mensaje entendible.
- **Sidebar de Ventanilla** (Petición Rápida y Turnos) agregado a las 28 vistas, visible según banderas; el grupo Administración ahora incluye al rol COORDINADOR.
- **Modal Universal de Registro Rápido de Ciudadano:** Integración en `assets/js/global.js`, `assets/css/style.css` y `modules/ciudadanos/save.php` del botón `[ + Registrar Ciudadano ]` y modal AJAX para registrar a cualquier persona en el padrón con cifrado y validación RENAPO en tiempo real, autoseleccionándola al instante en el trámite activo (CURP, Nacimientos, Matrimonios, Divorcios, Defunciones, Inscripciones, Reconocimientos, Foráneas, Inexistencias y Tickets) sin recargar ni perder datos.
- **Pulido Integral de la Barra Lateral (Sidebar):** Implementación de modo compacto real en escritorio (70px) con iconos centrados, tooltips flotantes (*Bootstrap Tooltip*) y persistencia de preferencia en `localStorage`; fijación `position: sticky` con scroll independiente (*slim scrollbar*); optimizaciones táctiles para móviles (soporte *swipe left to dismiss*, botón de cierre ergonómico de 44px, auto-cierre al navegar y rotación fluida de chevrons en submenús).
- **Descarga instantánea de Excel:** Integración del helper global `exportToExcelAsync` en `assets/js/global.js` con polling activo sobre `public/api/export_status.php` para disparar la descarga automática directa al navegador al completarse el archivo.

### Corregido
- **Sintaxis en JavaScript Global (`assets/js/global.js`):** se corrigió una llave de cierre faltante en el controlador de eventos `resize` que impedía la ejecución del script e inhabilitaba el botón de despliegue de la barra lateral (offcanvas) en dispositivos móviles y el conmutador de tema oscuro.
- `docs/migration_turnos_ventanilla.php` insertaba el catálogo con columnas inexistentes (`nombre_visible`, `activo`) — ahora usa `descripcion`.
- **Descarga de reportes Excel:** se corrigió `public/api/notifications.php` para consultar las tareas en `jobs` (en lugar de `export_jobs`), habilitando las notificaciones y los enlaces de descarga directa de los reportes generados.
- **Error en DataTables de Auditoría y Errores:** se alinearon `public/api/auditoria_data.php`, `public/api/errores_data.php` y `core/Auditoria.php` con las columnas reales de la base de datos (`fecha_hora`), eliminando las advertencias y restaurando la visualización de bitácoras.

## [1.3.0] - 2026-08-18
### Añadido
- **Seed de datos de prueba (`docs/seed_mockup.php`):** Poblado CLI (`--usuarios`, `--reset`) con 4 usuarios, 38 ciudadanos (incluye 3 finados, 3 bebés y 2 bajas lógicas), trámites en los 11 módulos (folios TK-2026, CURP cifrada de 18 dígitos, llegada de inexistencia = trámite + días de `configuracion`) y catálogos dinámicos.
- **Soft-delete en Ciudadanos:** `delete.php` marca la baja (columna `estado` + `deleted_at`/`deleted_by` si la migración está aplicada), nuevo `restore.php` para reactivar, `data.php` expone `estado` y filtra con `incluir_inactivos=1`, y `index.php` agrega columna Estatus, botón Restaurar y conmutador Ver/Ocultar inactivos.
- **Migración `docs/migration_softdelete_indices.php` (idempotente, CLI):** columnas soft-delete en 11 tablas de negocio + índices complementarios (jobs por status, auditoría/errores, foráneas por folio, inexistencias por fecha).
- **Descarga segura de exportaciones (`public/api/download_export.php`):** valida sesión, propiedad del job (solo propietario o ADMIN) y estado `completed`; la campana de notificaciones apunta aquí.
- **Rate limit de login:** `core/RateLimiter` (caché con fallback a archivos) limita a 10 intentos por 300 s en `public/auth.php`.
- **Endpoints de exportación migrados a POST + CSRF + cola (`core/Jobs`):** 13 `export_excel.php` de módulos y `export_{usuarios,auditoria,errores}.php` ya no son GET mutantes; el worker se lanza con `Jobs::launchWorker()` (Windows/Linux).
- **Índices de rendimiento:** `docs/migration_softdelete_indices.php` agrega índices sobre FK y columnas filtradas de las tablas de negocio.

### Cambiado
- **CSRF estricto en guardados:** los 11 `save.php` de módulos, `update_perfil.php`, `update_usuario.php` y `catalogos_handler.php` validan el token con `Auth::validateCSRF()` (hash_equals) y rechazan peticiones sin token; vistas afectadas incluyen el token oculto.
- **Mensajes de error genéricos:** se eliminó la exposición de `$e->getMessage()` en stats, notifications, Gestores (Nacimientos/Inexistencias/Defunciones), 6 `data.php`, `get_details.php`, `update_status.php`, `delete.php`, `catalogos_handler.php` y `pdf.php` (los detalles van a `error_log`).
- **Token QR firmado en actas (`actas_locales/pdf.php` + `public/validate.php`):** el QR contiene `base64("TIPO_id")."firma HMAC"`; `validate.php` lo verifica con `Encryption::verifySignature()`; fix de compatibilidad con `chillerlan/php-qrcode` v6 (`QRGdImagePNG::class`, `EccLevel::L`, version 10) y TCPDF.
- **`core/RateLimiter`** requiere explícitamente `core/Cache.php`; `composer dump-autoload -o` requiere regenerarse tras agregar clases en `core/`.
- **`.htaccess`:** bloquea `core/`, `public/(exports|reports)/`, `(logs|cache)/` y `*.sql|bak|old`; `.gitignore` excluye `public/exports/` y `public/reports/`.

### Corregido
- `public/validate.php` tenía llaves desbalanceadas (parse error).
- Exportaciones corrompían respuestas con warnings de encabezados al correr en segundo plano.

## [1.2.0] - 2026-06-19
### Añadido
- **Nuevos Módulos de Registro:** Lanzamiento de los módulos para matrimonios (`modules/matrimonios`), divorcios (`modules/divorcios`), reconocimientos (`modules/reconocimientos`), inscripciones (`modules/inscripciones`) y trámites de CURP (`modules/curp`).
- **Sistema de Permisos Granular:** 11 banderas booleanas para controlar el acceso individual de los operadores a nacimientos, matrimonios, divorcios, defunciones, inscripciones, reconocimientos, actas locales, actas foráneas, constancias, trámites CURP y tickets de soporte.
- **Buscador de Actas Locales:** Módulo `modules/actas_locales` para consultar e inspeccionar los detalles de cualquier acta local (Nacimiento, Matrimonio, Divorcio, Defunción, Reconocimiento) mediante un buscador dinámico y ventana de detalle interactiva (SweetAlert2).
- **Constancias Expandidas:** Soporte para Constancias de Inexistencia de Matrimonio, Nacimiento, Descendencia y Constancias de No Deudor Alimentario en `modules/inexistencias`.
- **Panel de Permisos de Usuarios:** Interfaz de usuario mejorada en `public/usuarios.php` que permite a los administradores activar/desactivar checkboxes para los 11 permisos individuales de cada operador.

## [1.1.0] - 2026-06-19
### Añadido
- **Perfil de Usuario (Mi Perfil):** Página de gestión de perfil (`public/perfil.php` y `public/update_perfil.php`) que permite a los usuarios cambiar su nombre de visualización, correo electrónico y cambiar su contraseña de manera segura tras validar la actual contra la base de datos.
- **Notificaciones (Historial en Header):** Botón de campana en el header de todos los módulos con un contador dinámico de novedades. Carga mediante AJAX los últimos 5 trámites o registros realizados en todo el ERP (Nacimientos, Defunciones, Ciudadanos, Tickets o Foráneas) con formateo visual y tiempo relativo.
- **Seguridad (Autenticación):** Sistema de inicio y cierre de sesión seguro basado en PHP Sessions (`core/Auth.php`, `public/login.php`, `public/auth.php`, `public/logout.php`).
- **Seguridad (Control de Acceso):** Validación de sesión mediante `Auth::check()` en el Dashboard, vistas de creación y endpoints/APIs de todos los módulos (`ciudadanos`, `nacimientos`, `defunciones`, `foraneas`, `inexistencias`, `peticiones`).
- **Usuario Administrador:** Inclusión de credenciales por defecto (`admin@drc.gob.mx` / `Admin123!`) mediante inserción SQL segura (`password_hash()`) en `docs/database.sql`.
- **UI (Sidebar Compacto):** Soporte para contraer la barra lateral en escritorio (ocultando textos de menús y submenús, dejando solo iconos visibles) al hacer clic en el botón de menú para maximizar el espacio de trabajo.
- **Responsividad (Móvil):** Menú lateral tipo cajón (*drawer*) desplegable en pantallas pequeñas (<768px) y redimensionamiento automático de tarjetas estadísticas a ancho completo.

## [1.0.0] - Liberación Inicial
### Añadido
- **Fase 1:** Inicialización de arquitectura MVC-like, base de datos segura (PDO/Prepared Statements) y UI con Bootstrap 5.
- **Fase 1:** Módulo de Constancias de Inexistencias con cálculo dinámico de fechas.
- **Fase 2:** Implementación del Catálogo Maestro de Ciudadanos para control único de identidades.
- **Fase 2:** Módulos de Nacimientos y Defunciones con lógica transaccional de cambio de Estado Vital.
- **Fase 3:** Recepción y validación de Actas Foráneas.
- **Fase 3:** Sistema de Mesa de Ayuda (Tickets/Peticiones) con generación automática de folios alfanuméricos.
- **Fase 3:** Dashboard dinámico con API de estadísticas en tiempo real y gráficas interactivas con Chart.js.
- Documentos normativos de seguridad (`TESTING_SECURITY.md`).
