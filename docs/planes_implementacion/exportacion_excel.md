# Plan de Implementación: Exportación a Excel en Todos los Módulos

Este plan detalla el diseño y la estrategia para implementar un botón de **"Exportar consulta a Excel"** en cada uno de los módulos del sistema, respetando las búsquedas y filtros activos en las tablas (DataTables).

---

## Observaciones y Arquitectura Propuesta

1. **Uso del Sistema Asíncrono Existente (Recomendado)**:
   * **Problema**: Algunos catálogos o registros (como Ciudadanos, Nacimientos o Logs de Auditoría) pueden llegar a tener miles de registros. Generar archivos Excel de forma síncrona en la misma solicitud web puede causar problemas de consumo de memoria y bloqueos por tiempo de ejecución límite (*timeouts*) del servidor web Apache en XAMPP.
   * **Solución**: Proponemos reutilizar el motor de exportación en segundo plano que el sistema ya posee (usado actualmente en *Inexistencias* y *Reportes*). Al presionar el botón de exportar, se registrará un trabajo en la tabla `jobs` en estado `pending`, se levantará el CLI Worker (`core/Worker.php`) de forma silenciosa en Windows y se le notificará al usuario vía SweetAlert. Una vez terminado, aparecerá un enlace en la barra de notificaciones del ERP para descargar el archivo Excel inmediatamente.

2. **Respeto a Filtros y Búsquedas Activas**:
   * Las tablas del ERP usan paginación y búsqueda en el servidor. Al presionar el botón de exportación, el JavaScript capturará el valor actual de búsqueda de la tabla (por ejemplo, `$('#ciudadanosTable').DataTable().search()`) y los filtros seleccionados (como tipo de acta, fecha, etc.) y los enviará al backend para generar un Excel que contenga **únicamente** los registros coincidentes con la consulta actual del usuario (sin paginar).

3. **Formateo y Estilo de Excel**:
   * Mantendremos la consistencia de estilos de los Excel generados: cabeceras en negrita con fondo gris claro (`FFE9ECEF`), autoajuste de columnas automático para prevenir cortes de texto y formateo explícito de números largos (como CURPs, líneas de pago y actas) como texto para evitar notación científica o pérdidas de ceros a la izquierda.

---

## Decisiones Aprobadas
- **Uso de exportación asíncrona:** Aprobado para todos los módulos.
- **Autolimpieza:** Se programará la eliminación automática de reportes de más de 48 horas de antigüedad.

---

## Proposed Changes

### 1. Interfaz de Usuario (HTML/JS)
Añadir el botón `<button id="btnExportExcel" class="btn btn-success me-2"><i class="fa-solid fa-file-excel"></i> Exportar consulta a Excel</button>` en la cabecera/filtros de las siguientes vistas y añadir el script jQuery correspondiente para invocar el endpoint de exportación:

* [MODIFY] `modules/ciudadanos/index.php`
* [MODIFY] `modules/nacimientos/index.php`
* [MODIFY] `modules/matrimonios/index.php`
* [MODIFY] `modules/divorcios/index.php`
* [MODIFY] `modules/defunciones/index.php`
* [MODIFY] `modules/inscripciones/index.php`
* [MODIFY] `modules/reconocimientos/index.php`
* [MODIFY] `modules/actas_locales/index.php`
* [MODIFY] `modules/foraneas/index.php`
* [MODIFY] `public/usuarios.php`
* [MODIFY] `public/auditoria.php` (Botones separados para exportar logs de Auditoría y de Errores)

---

### 2. Endpoints de Registro de Trabajo (PHP Controllers)
Crear scripts individuales en los módulos para validar permisos específicos, recibir los parámetros de búsqueda/filtros, registrar el `job` en la base de datos y levantar el Worker en Windows de forma asíncrona:

* [NEW] `modules/ciudadanos/export_excel.php` (Requiere `permiso_registro_ciudadanos` o lectura general)
* [NEW] `modules/nacimientos/export_excel.php` (Requiere `permiso_registro_nacimientos`)
* [NEW] `modules/matrimonios/export_excel.php` (Requiere `permiso_registro_matrimonios`)
* [NEW] `modules/divorcios/export_excel.php` (Requiere `permiso_registro_divorcios`)
* [NEW] `modules/defunciones/export_excel.php` (Requiere `permiso_registro_defunciones`)
* [NEW] `modules/inscripciones/export_excel.php` (Requiere `permiso_registro_inscripciones`)
* [NEW] `modules/reconocimientos/export_excel.php` (Requiere `permiso_registro_reconocimientos`)
* [NEW] `modules/actas_locales/export_excel.php` (Requiere `permiso_actas_locales`)
* [NEW] `modules/foraneas/export_excel.php` (Requiere `permiso_actas_foraneas`)
* [NEW] `public/api/export_usuarios.php` (Requiere rol `ADMIN`)
* [NEW] `public/api/export_auditoria.php` (Requiere rol `ADMIN`)
* [NEW] `public/api/export_errores.php` (Requiere rol `ADMIN`)

---

### 3. CLI Worker (Motor de Generación)
* [MODIFY] `core/Worker.php`
  * Añadir el procesamiento de las nuevas tareas en la cola (`export_ciudadanos`, `export_nacimientos`, `export_matrimonios`, `export_divorcios`, `export_defunciones`, `export_inscripciones`, `export_reconocimientos`, `export_actas_locales`, `export_foraneas`, `export_usuarios`, `export_auditoria`, `export_errores`).
  * Implementar las funciones de consulta a base de datos aplicando los filtros de búsqueda recibidos en el payload y estructurando el archivo Excel mediante `PhpSpreadsheet`.

---

### 4. Notificaciones de Descarga
* [MODIFY] `public/api/notifications.php`
  * Mapear de forma amigable los nombres de los tipos de trabajo en el panel de notificaciones para que el usuario visualice el mensaje correcto (ej. *"El reporte de Catálogo de Ciudadanos ya está listo para descargar"*).

---

## Verification Plan

### Manual Verification
1. **Validación de Filtros**:
   * Buscar un término específico en el Catálogo de Ciudadanos (ej. "Gomez").
   * Presionar el botón de exportación.
   * Abrir el Excel generado desde las notificaciones y validar que **solo** aparezcan los ciudadanos con apellido o nombre "Gomez".
2. **Validación de Permisos**:
   * Iniciar sesión como operador y verificar que no se pueda acceder directamente a los endpoints de exportación de administración (`api/export_usuarios.php`, etc.), arrojando un error de no autorizado.
3. **Validación de Datos Largos**:
   * Validar que la columna CURP o Número de Acta en el Excel final se visualice correctamente como texto (sin notación científica como `1.23E+14` ni eliminación de ceros).
