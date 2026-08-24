# Arquitectura, Servicios y Réplicas (Fase 11)

Este plan detalla la implementación de patrones de arquitectura empresarial en el ERP DRC para garantizar la escalabilidad a nivel de lógica de negocio (Capa de Servicios), rendimiento (Capa de Caché en Memoria con Redis/Memcached) y escalabilidad de base de datos (División de Conexiones Master/Slave para réplicas).

## User Review Required

> [!IMPORTANT]
> **Autocarga de Clases (Autoloading)**
> El proyecto no utiliza un autoloader dinámico PSR-4 para la carpeta `core/Services/`. Para incluir de forma limpia los servicios y la caché, utilizaremos el autoload actual de Composer (añadiendo la regla PSR-4 en `composer.json` y ejecutando `composer dump-autoload`), o incluiremos manualmente los archivos requeridos mediante `require_once` en los controladores y archivos correspondientes. Optaremos por **definir la regla de autocarga de clases en Composer** para una arquitectura limpia de producción.

---

## Proposed Changes

### 1. Capa de Servicios (Service Layer)
Desacoplar la lógica de negocio y las transacciones de los controladores de guardado (`save.php`).

#### [NEW] [GestorDefunciones.php](file:///c:/xampp/htdocs/DRC/core/Services/GestorDefunciones.php)
- Clase `GestorDefunciones` dentro del espacio de nombres `Core\Services`.
- Método `registrarDefuncion($numero_acta, $ciudadano_id, $fecha_defuncion, $fecha_registro, $causa_muerte)`.
- Maneja la transacción, inserción en la tabla `defunciones`, actualización del estado del ciudadano a `FINADO` y el registro en la bitácora de auditoría.

#### [NEW] [GestorNacimientos.php](file:///c:/xampp/htdocs/DRC/core/Services/GestorNacimientos.php)
- Clase `GestorNacimientos`.
- Método `registrarNacimiento($numero_acta, $ciudadano_id, $padre_id, $madre_id, $lugar_nacimiento, $fecha_registro)`.
- Maneja la inserción de registros de nacimiento y la auditoría.

#### [NEW] [GestorInexistencias.php](file:///c:/xampp/htdocs/DRC/core/Services/GestorInexistencias.php)
- Clase `GestorInexistencias`.
- Método `registrarInexistencia($tipo_constancia, $linea_pago, $fecha_tramite, $fecha_llegada, $nombre_completo, $observaciones)`.
- Maneja las validaciones de tipo de constancia, la longitud de la línea de pago, la **Validación Cruzada** contra el catálogo de ciudadanos, la inserción y la auditoría.

#### [MODIFY] [save.php](file:///c:/xampp/htdocs/DRC/modules/defunciones/save.php)
- Eliminar la lógica SQL y transaccional.
- Limitar el archivo a recibir la petición POST, sanitizar datos y llamar a `Core\Services\GestorDefunciones::registrarDefuncion()`.

#### [MODIFY] [save.php](file:///c:/xampp/htdocs/DRC/modules/nacimientos/save.php)
- Limitar el controlador a recibir y delegar la inserción de nacimientos a `Core\Services\GestorNacimientos::registrarNacimiento()`.

#### [MODIFY] [save.php](file:///c:/xampp/htdocs/DRC/modules/inexistencias/save.php)
- Limitar el controlador a sanitizar y delegar la lógica a `Core\Services\GestorInexistencias::registrarInexistencia()`.

---

### 2. Capa de Caché en RAM (Redis / Memcached con Fallback)
Optimizar la carga de catálogos e información estática mediante caché en memoria RAM.

#### [NEW] [Cache.php](file:///c:/xampp/htdocs/DRC/core/Cache.php)
- Clase `Cache` en `Core`.
- Detecta dinámicamente si la extensión `Redis` está cargada en PHP y si hay conexión con el servidor Redis local (127.0.0.1:6379).
- Si no está disponible Redis, realiza un fallback automático a una caché basada en archivos serializados en el directorio local `cache/` del proyecto para evitar errores de ejecución en servidores de desarrollo.
- Proporciona métodos estáticos `get($key)`, `set($key, $value, $ttl)` y `delete($key)`.

#### [NEW] [Catalogs.php](file:///c:/xampp/htdocs/DRC/core/Catalogs.php)
- Clase `Catalogs` en `Core` que actúa como servicio de catálogos.
- Método `getEstados()`: Devuelve un arreglo estático de estados de México, almacenándolo y cargándolo de la caché para reducir accesos futuros.
- Método `getTiposTramite()`: Devuelve el listado de tipos de trámite, utilizando almacenamiento en caché.

#### [MODIFY] [create.php](file:///c:/xampp/htdocs/DRC/modules/foraneas/create.php)
- Reemplazar el campo de texto libre `Estado de Origen` por un menú desplegable `<select>` alimentado por `\Core\Catalogs::getEstados()`, demostrando el uso del catálogo cacheado en memoria RAM.

---

### 3. Preparación de Réplicas (Read/Write Split)
Configurar la clase de base de datos para separar operaciones de escritura (Master) y operaciones de lectura (Slave/Replica).

#### [MODIFY] [Database.php](file:///c:/xampp/htdocs/DRC/core/Database.php)
- Configurar lectura de variables `.env` para la réplica: `DB_READ_HOST`, `DB_READ_USER`, `DB_READ_PASS`, y `DB_READ_NAME`.
- Implementar `Database::getWriteConnection()`: Devuelve siempre la conexión a la base de datos principal (Master) para escrituras.
- Implementar `Database::getReadConnection()`: Devuelve la conexión a la base de datos réplica (Slave). Si los parámetros de réplica coinciden con el Master o no están configurados, devuelve de forma eficiente la misma conexión de escritura (evitando duplicar sockets).
- Mantener `Database::getConnection()` apuntando a la escritura por retrocompatibilidad.

#### [MODIFY] [data.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/data.php)
- Modificar la consulta para obtener la conexión a través de `Database::getReadConnection()`.

#### [MODIFY] [search.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/search.php)
- Utilizar `Database::getReadConnection()` para la búsqueda de ciudadanos.

#### [MODIFY] [stats.php](file:///c:/xampp/htdocs/DRC/public/api/stats.php)
- Utilizar `Database::getReadConnection()` para todas las consultas de KPI del dashboard principal.

#### [MODIFY] [.env.example](file:///c:/xampp/htdocs/DRC/.env.example)
- Declarar las variables comentadas de réplica de lectura para documentación:
  ```ini
  # Configuración de Base de Datos Réplica (Lectura - Opcional)
  # DB_READ_HOST=127.0.0.1
  # DB_READ_NAME=drc_erp
  # DB_READ_USER=root
  # DB_READ_PASS=
  ```

---

## Verification Plan

### Automated Verification
- Validar la sintaxis de todos los archivos con `php -l`.
- Validar la autocarga de clases ejecutando `composer dump-autoload` en la raíz.

### Manual Verification
1. **Verificación de Servicios y Guardado**:
   - Registrar un nuevo nacimiento y una defunción. Verificar que el flujo transaccional se realice correctamente (sincronizando el estatus vital en ciudadanos) a través de la capa de servicios.
2. **Caché de Catálogos**:
   - Ir al formulario de actas foráneas (`modules/foraneas/create.php`).
   - Validar que el select de "Estado de Origen" se cargue correctamente.
   - Modificar temporalmente el catálogo en el código y verificar si se lee el valor cacheado (o borrar la caché en la carpeta `cache/` para ver la actualización).
3. **Réplicas de Lectura**:
   - Configurar en `.env` un host alternativo de réplica inexistente (ej. `127.0.0.99`) en `DB_READ_HOST` y comprobar que la carga de tablas de lectura falle por timeout de conexión, demostrando que efectivamente está intentando conectar a la réplica para lecturas.
