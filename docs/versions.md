# Historial de Versiones (Changelog)

Este documento registra todos los cambios notables, actualizaciones y correciones del sistema ERP para la Dirección de Registro Civil.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

## [1.5.8] - 2026-08-24
### Añadido
- **Seguimiento de Constancias Emitidas (`modules/inexistencias/index.php`):** nueva columna "Acciones" en la tabla de constancias emitidas con botón "Seguimiento" que abre un modal flotante con el detalle completo del registro (tipo, línea de pago, fechas, observaciones) y acciones contextuales según estatus, alineando la experiencia con la pestaña de Peticiones de Ventanilla.
- **Corrección de errores operativos (reactivación):** nuevas acciones sobre constancias: FINALIZAR (PENDIENTE → FINALIZADO), CANCELAR (PENDIENTE → CANCELADO) y REACTIVAR (FINALIZADO/CANCELADO → PENDIENTE) para deshacer equivocaciones de ventanilla. Cancelar y reactivar exigen motivo (mín. 5 caracteres) que se anexa a observaciones en MAYÚSCULAS y se registra en auditoría.
- **Servicio `GestorInexistencias::actualizarEstatus()` (`core/Services/GestorInexistencias.php`):** valida transiciones de estatus permitidas, motivo obligatorio según acción, anexa trazabilidad a observaciones y audita con usuario, estatus previo/posterior y motivo.
- **Endpoint `modules/inexistencias/update_status.php`:** POST con `Auth::checkPermission('permiso_constancias')`, `Auth::check()` y validación CSRF; delega en el servicio y responde JSON estándar.
- **`modules/inexistencias/data.php`:** ahora expone `observaciones` y `creado_en` (sanitizados) para alimentar el modal de seguimiento.

## [1.5.7] - 2026-08-23
### Seguridad (Área 1: Endurecimiento — defensa en profundidad)
- **Guardia CLI-only en migraciones web (`docs/migration_extra.php`, `docs/migration_queue_reportes.php`, `docs/migration_encrypt.php`):** las tres migraciones que carecían de protección propia ahora responden 403 si se invocan vía navegador; antes dependían exclusivamente de la regla `mod_rewrite` del `.htaccess` raíz (ineficaz con `AllowOverride` off o bajo Nginx/IIS). Permitían `ALTER TABLE` y re-encriptación masiva de CURPs sin autenticación.
- **`.htaccess` propio en `cache/` y `public/exports/`:** bloqueo directo por Apache (`Require all denied`) de los archivos de caché serializada y de los 21 reportes `.xlsx` con PII; la descarga legítima sigue operando vía `public/api/download_export.php` (sesión + propietario/ADMIN).
- **Invalidación de cookie de sesión en logout (`public/logout.php`):` ahora elimina la cookie del cliente (`setcookie` con parámetros de sesión) además de `session_unset()`/`session_destroy()`, y usa `Auth::initSession()` con cookies `HttpOnly/SameSite/Secure`.

### Verificación
- Re-auditoría de los 10 hallazgos de `SEGURIDAD_AUDITORIA.md`: los hallazgos 1 (SQLi `ORDER BY`), 2 (clave AES fallback), 4 (CSRF), 5 (bypass `cron_token`), 6 (XSS reportes), 7 (rate-limit login), 8 (token `validate.php`), 10 (cookies) ya estaban remediados en el código vigente; se cerró la brecha residual de defensa en profundidad (hallazgos 3 y 9).
- Suite PHPUnit: 22 tests, 65 aserciones OK (3 errores pre-existentes por `ext-gd` no cargada en CLI, ajenos a esta versión).

## [1.5.6] - 2026-08-23
### Añadido
- **Clases Centralizadas en Design System (`assets/css/style.css`):** incorporación de `.stack-trace`, `.is-valid-curp` y `.is-invalid-curp` a las hojas de estilo globales con soporte para modo oscuro, eliminando bloques `<style>` embebidos en `public/auditoria.php` y `modules/peticion_rapida/create.php`.
- **Enrutamiento AJAX Configurable (`assets/js/global.js`):** soporte para `window.DRC_BASE_URL` en la resolución de endpoints de APIs con fallback automático a rutas relativas seguras.

### Cambiado
- **Limpieza de Código y Comentarios Huérfanos:** eliminación de comentarios repetitivos `<!-- Sidebar -->` y stubs vacíos de JavaScript (`// Cargar Notificaciones`) en las vistas de `ciudadanos`, `defunciones`, `foraneas`, `inexistencias`, `nacimientos`, `reportes`, `auditoria` y `catalogos`.

## [1.5.5] - 2026-08-23
### Añadido
- **Navegación por Teclado e Indicador de Foco Visible (`assets/css/style.css`):** implementación de anillo de foco institucional `:focus-visible` de 2px con desplazamiento (offset) en botones, enlaces, campos de formulario y controles interactivos en temas claro (`#691C32`) y oscuro (`#B38E5D`), cumpliendo con la pauta WCAG 2.1 AA (Criterio 2.4.7).
- **Enlace de Salto al Contenido Principal (`assets/css/style.css`, `assets/js/global.js`):** inyección automática de `.skip-link` al inicio de cada página, accesible por teclado al presionar Tab para permitir que lectores de pantalla omitan la navegación lateral.
- **Soporte `prefers-reduced-motion` (`assets/css/style.css`):** respeto estricto a las preferencias de reducción de movimiento y animaciones para usuarios con sensibilidad vestibular o mareo visual.
- **Gestión Accesible de Foco en Modales (`assets/js/global.js`):** autoenfoque del primer elemento interactivo (`input`, `select`, `button`) al desplegar modales de Bootstrap.
- **Semántica y Atributos ARIA Universales:** etiquetas `aria-label`, `aria-live="polite"` y `role="region"` en KPIs, gráficas y centro de notificaciones.

## [1.5.4] - 2026-08-23
### Añadido
- **Feedback de Carga Global en Formularios (`assets/js/global.js`):** interceptor automático de envío en formularios (`submit`) que deshabilita el botón primario, muestra un spinner interactivo (`<i class="fa-solid fa-circle-notch fa-spin me-1"></i> Procesando...`) y restaura su estado en `ajaxComplete` o timeout de seguridad, previniendo dobles envíos por clics accidentales.
- **Clases de Utilidad de Color Institucionales (`assets/css/style.css`):** definición de `.text-emerald` (`#0F766E`), `.text-slate` (`#64748B`) y `.text-gold` (`#B38E5D`).

### Cambiado
- **Refinamiento de Pantalla de Login (`public/login.php`):** ajuste de altura fluida con `min-height: 100vh; padding: 20px;` para evitar desbordamientos en teclados móviles virtuales, adaptación visual integral a Modo Oscuro (`.login-card` con variables de superficie) y botón institucional.
- **Corrección de Parches Blancos en Modo Oscuro (`assets/css/style.css`):** remapeo de clases `.card-header.bg-white`, `.bg-light`, `.badge.bg-light`, `.dropdown-menu` y `.modal-content` para que tomen automáticamente las variables de superficie oscura (`#1E293B`) y contraste tipográfico adecuado sin destellos claros.
- **Componente Accesos Rápidos en Dashboard (`public/index.php`):** sustitución de botones de contorno genéricos por tarjetas interactivas enriquecidas `.quick-action-btn` con acento dorado y transición hover.
- **Adaptación Dinámica de Gráficas Chart.js (`public/index.php`):** detección de tema activo (`body.dark-mode`) en las gráficas de Tendencia, Recaudación y Carga Operativa, aplicando bordes de dona `#1E293B`, rejillas tenues y colores de texto legibles.

## [1.5.3] - 2026-08-23
### Añadido
- **DataTables en Administración de Usuarios (`public/usuarios.php`):** inicialización de DataTables Responsive con búsqueda en tiempo real, ordenamiento por columnas, paginación y modo de tarjetas móviles en la vista de usuarios.
- **Historial de Notificaciones universal:** integración del menú `#notificacionesMenu` (campana + badge + lista desplegable) en los módulos de *Petición Rápida* (`index.php`, `create.php`) y *Ventanilla de Seguimiento* (`index.php`, `create.php`).

### Cambiado
- **Estandarización de Botones de Exportar a Excel:** unificación a la clase semántica `.btn-excel` (`#0F766E`) con icono FontAwesome en `ciudadanos`, `defunciones`, `peticion_rapida`, `inexistencias`, `usuarios` y `auditoria`.
- **Estandarización del Botón Toggle de Menú:** adopción homogénea de `.btn-sidebar-toggle` con accesibilidad ARIA (`aria-label="Toggle Sidebar"`) en todas las vistas administrativas (`perfil.php`, `usuarios.php`, `auditoria.php`, `catalogos.php`) y de módulos.
- **Encabezados Institucionales Enriquecidos:** normalización visual de cabeceras (`d-flex justify-content-between`, icono institucional, título `h2` en negrita, subtítulo contextual en `text-muted` y botones de acción rápida homogéneos) en todos los módulos de captura (`create.php`) y listados.
- **Limpieza de Estilos Inline:** eliminación de reglas inline redundantes (`style="background: var(--secondary-color)..."`) en favor de las clases del *Design System* (`.btn-primary`).

## [1.5.2] - 2026-08-23
### Corregido
- **JavaScript en Perfil (`public/perfil.php`):** se eliminó bloque huérfano de código en el evento `ready` que causaba un `SyntaxError` e impedía la ejecución de los formularios de actualización de datos y cambio de contraseña.
- **Codificación UTF-8 / Mojibake:** saneamiento integral de caracteres doblemente codificados en 34 archivos PHP de la aplicación (`public/`, `modules/`), restaurando acentos, caracteres especiales (`ñ`, `°`, `¡`, `¿`) y legibilidad en encabezados y menús.
- **Traducción de meses en estadísticas (`public/api/stats.php`):** corrección tipográfica en el array de meses donde `'Sep'` mapeaba erróneamente a `'Ene'`, restaurando la visualización correcta de la serie temporal en el Dashboard.

## [1.5.1] - 2026-08-21
### Seguridad (implementación del plan `docs/analisis_completo/`)
- **CSRF real en login (#5):** `public/login.php` incluye token oculto (`Auth::generateCSRF()`) y `public/auth.php` lo valida con `Auth::validateCSRF()` antes de autenticar; la sesión de login se inicia con `Auth::initSession()` (cookies HttpOnly/SameSite/Secure).
- **CSRF estricto en nacimientos (#4):** `modules/nacimientos/save.php` reemplaza el chequeo por presencia por `validateCSRF()`; era el único módulo que conservaba la validación simulada.
- **Baja/reactivación de ciudadanos restringida a coordinadores (#8):** `modules/ciudadanos/delete.php` y `restore.php` exigen `Auth::esCoordinador()` (ADMIN/COORDINADOR/SUPERVISOR); antes bastaba cualquier sesión válida.
- **`unserialize()` endurecido (#7):** `core/Cache.php` usa `allowed_classes => false` en los 3 puntos de deserialización (Redis, Memcached y archivos), eliminando el riesgo de PHP Object Injection.
- **Claves criptográficas sin fallback público (#11):** `core/Encryption` lanza `RuntimeException` si falta `ENCRYPTION_KEY` en `.env` (se admite clave de prueba únicamente bajo PHPUnit); se elimina la constante pública `'drc_erp_secure_aes256_symmetric_key_2026'`. El blind index conserva su derivación desde la clave maestra para no invalidar índices existentes.
- **Política de contraseñas en backend (#9):** `update_perfil.php` valida coincidencia, mínimo 8 caracteres, al menos una mayúscula y un número; `update_usuario.php` aplica la misma política al crear usuarios.
- **Perímetro `.htaccess` reforzado (#6):** se bloquean `vendor/`, `tests/`, `composer.phar`, archivos `*.md`, exportaciones no-XLSX (`csv|pdf|zip`) en `public/(exports|reports)/`; se añade cabecera `Content-Security-Policy` (app 100% autocontenida).
- **Página 403 autónoma:** `Auth::checkPermission()` ya no carga Bootstrap desde CDN jsdelivr (estilos inline mínimos); requisito previo para la CSP.
- **Fuentes localizadas (#10):** Inter variable (latin) servida desde `assets/vendor/fonts/inter-latin-var.woff2` vía nuevo `assets/css/fonts.css`; reemplazadas las 34 referencias a `fonts.googleapis.com` (login, módulos y vistas públicas operan sin Internet).
- **Exportación diaria autorizada (#12):** `export_diario_excel.php` exige además `Auth::checkExport()`.

### Corregido
- **Bug de mapeo de sexo en ciudadano rápido (#1):** el modal universal (`assets/js/global.js`) enviaba H/M/X mientras `create.php` usa M/F/X: "Masculino" desde `create.php` y "Mujer" desde el modal se guardaban como 'X'. El modal ahora envía M/F/X y `modules/ciudadanos/save.php` normaliza H/HOMBRE/MASCULINO→M, F/FEMENINO/MUJER→F. Los registros históricos con sexo='X' requieren revisión manual (no es posible distinguir corruptos de legítimos de forma automática).
- **CURP del solicitante cifrada en Petición Rápida (#3):** `PeticionRapidaService::validar()` devuelve la CURP cifrada + blind index; `save/update` persisten ambos (con detección opcional de columna); `data.php`, `modulo_peticiones_data.php`, `edit.php`, `ticket.php` y el reporte del Worker descifran para visualización autorizada; la búsqueda por CURP exacta usa el blind index. Migración CLI idempotente: `docs/migration_pv_curp_bindex.php` (agrega columna + índice + cifra CURP legadas). Ejecutar tras el deploy.
- **Manejo de excepciones (#28):** `public/validate.php` y `update_perfil.php` capturan `\Throwable`.
- **Mayúsculas normalizadas (#22):** `numero_acta` en nacimientos/defunciones/foraneas (+Gestores), `tipo_acta` en foraneas y `tipo_peticion` en peticiones pasan por `mb_strtoupper`.

### Cambiado
- **Estadísticas del dashboard (#27):** `public/api/stats.php` usa prepared statements con bindValue para las fechas de los contadores diarios.
- **Turnos homologado (#16):** `modules/turnos/crear.php` renombrado a `create.php` (referencia AJAX actualizada).

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
