# Índices, Borrado Lógico y Seguridad .env (Fase 10)

Este plan detalla la implementación de índices de rendimiento en la base de datos, la adición del campo `estado` para borrados lógicos (Soft Deletes) en el catálogo de ciudadanos, y la protección de archivos sensibles `.env` y de configuración mediante `.htaccess`.

## User Review Required

> [!IMPORTANT]
> **Migración de Base de Datos**
> Para crear los índices de base de datos y la nueva columna `estado` en la tabla `ciudadanos`, crearé un script temporal en PHP `docs/migration_extra.php` y lo ejecutaré. Este script se encargará de realizar las operaciones de forma segura, verificando que los índices y la columna no existan previamente.

---

## Proposed Changes

### 1. Migración y Rendimiento de Base de Datos
Creación de índices y campos para borrado lógico en MariaDB/MySQL.

#### [NEW] [migration_extra.php](file:///c:/xampp/htdocs/DRC/docs/migration_extra.php)
- Script en PHP que se conecta a la base de datos usando `core/Database.php`.
- Agrega la columna `estado TINYINT(1) DEFAULT 1` a la tabla `ciudadanos` si no existe.
- Crea el índice `idx_ciudadanos_nombre` en la columna `nombre` de `ciudadanos` para búsquedas rápidas si no existe.
- Crea el índice `idx_ciudadanos_curp` en la columna `curp` de `ciudadanos` si no existe (aunque MySQL crea uno automáticamente por ser `UNIQUE`, esto garantiza la declaración explícita).

---

### 2. Borrado Lógico (Soft Deletes) en Ciudadanos
Asegurar que la información de los ciudadanos no se elimine físicamente y se filtre según su estado activo.

#### [MODIFY] [data.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/data.php)
- Filtrar la consulta principal y la consulta de conteo para devolver únicamente ciudadanos con `estado = 1`.
- Devolver la columna `id` de forma explícita para poder realizar la acción de borrado en el cliente.

#### [MODIFY] [search.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/search.php)
- Modificar la consulta AJAX de búsqueda para que valide que `estado = 1`, previniendo que ciudadanos inactivos (borrados lógicos) puedan ser seleccionados para nuevos trámites.

#### [NEW] [delete.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/delete.php)
- Endpoint seguro que recibe el `id` de un ciudadano y el token `csrf_token`.
- Valida la sesión de usuario y el token CSRF.
- Ejecuta una consulta `UPDATE ciudadanos SET estado = 0 WHERE id = :id`.
- Registra la acción de baja en la bitácora de auditoría (`Core\Audit::log('DELETE', 'ciudadanos', ...)`).
- Retorna una respuesta JSON.

#### [MODIFY] [index.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/index.php)
- Añadir la columna de "Acciones" en el encabezado de la tabla HTML.
- Incluir SweetAlert2 en el scripts/head (si no estuviese).
- Inyectar el token CSRF global.
- Configurar la columna de "Acciones" en DataTables para renderizar un botón rojo de "Eliminar" con el icono de basurero.
- Implementar el manejador AJAX en Javascript que envía la solicitud de baja a `delete.php` y recarga la tabla de ciudadanos asíncronamente tras la confirmación de SweetAlert2.

---

### 3. Seguridad y Protección de Archivos .env
Bloquear el acceso de los navegadores web a los archivos sensibles de configuración del proyecto.

#### [MODIFY] [.htaccess](file:///c:/xampp/htdocs/DRC/.htaccess)
- Agregar directivas `<FilesMatch>` para denegar todo acceso HTTP a archivos que inicien con `.env` (como `.env` and `.env.example`).
- Añadir protección para otros archivos del sistema como `composer.json`, `composer.lock`, `.gitignore`, `.gitattributes`, y el plan de implementación.
- Configurar una regla de redirección o bloqueo para que los usuarios no puedan listar o navegar el directorio `core/`.

---

## Verification Plan

### Automated Verification
- Ejecutar el script `docs/migration_extra.php` desde la consola para validar que las alteraciones a la base de datos se apliquen correctamente.
- Validar la sintaxis de los archivos modificados con `php -l`.

### Manual Verification
1. **Protección .env**:
   - Intentar acceder desde el navegador a `http://localhost/DRC/.env`. Debería devolver un error **403 Forbidden**.
   - Intentar acceder a `http://localhost/DRC/core/Database.php`. Debería denegarse el acceso.
2. **Borrado Lógico**:
   - Entrar al catálogo de ciudadanos (`modules/ciudadanos/index.php`).
   - Seleccionar un ciudadano de prueba y hacer clic en "Eliminar".
   - Confirmar el mensaje de SweetAlert2.
   - Verificar que el ciudadano ya no aparezca en la lista de ciudadanos.
   - Consultar la base de datos para constatar que el registro sigue existiendo con `estado = 0`.
   - Verificar en la Bitácora de Auditoría que se registró el evento `DELETE` para el módulo `ciudadanos`.
