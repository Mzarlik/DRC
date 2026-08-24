# Fase de Seguridad y Auditoría (Prioridad Alta)

El objetivo de este plan es implementar los controles de seguridad necesarios para garantizar la integridad, trazabilidad y protección del sistema de la Dirección de Registro Civil, conforme a los estándares solicitados.

## User Review Required

> [!IMPORTANT]
> **Cambios en la Base de Datos**
> Renombraré la tabla `logs` (definida originalmente) a `bitacora_auditoria` para ajustarnos a la nomenclatura solicitada. Aplicaré un script SQL directamente en la base de datos local para que los cambios surtan efecto inmediatamente sin que pierdas información.

## Open Questions

> [!WARNING]
> ¿Deseas que el inicio de sesión (Login) y los cierres de sesión también se registren en la `bitacora_auditoria`, o estrictamente solo las operaciones de inserción/modificación de los módulos? El plan actual contempla registrar todas las inserciones (`INSERT`) que ocurren en los archivos `save.php` de cada módulo.

## Proposed Changes

### 1. Base de Datos & Auditoría Core
Se creará la clase encargada de insertar los registros de trazabilidad y se actualizará el esquema.

#### [NEW] [Audit.php](file:///c:/xampp/htdocs/DRC/core/Audit.php)
- Clase estática `Audit` con el método `log($accion, $modulo, $detalles)`.
- Insertará en la tabla `bitacora_auditoria` el ID del usuario en sesión (`$_SESSION['user_id']`), la acción, el módulo, y la IP.

#### [MODIFY] [database.sql](file:///c:/xampp/htdocs/DRC/docs/database.sql)
- Renombrar la tabla `logs` a `bitacora_auditoria`.
- Asegurar que cuenta con los campos solicitados (`usuario_id`, `accion`, `modulo`, `detalles`, `fecha`).

---

### 2. Prevención de Falsificación (CSRF)
Se implementará un manejador central de tokens vinculados a la sesión del usuario.

#### [MODIFY] [Auth.php](file:///c:/xampp/htdocs/DRC/core/Auth.php)
- Añadir métodos `generateCSRF()` y `validateCSRF($token)` para generar de manera segura (`random_bytes`) y validar contra `$_SESSION['csrf_token']`.

#### [MODIFY] Archivos de Módulos (create.php & save.php)
Se actualizarán los 12 módulos para implementar los nuevos métodos CSRF (reemplazando los "dummy" y generadores estáticos sin estado).
- **Módulos afectados:** `ciudadanos`, `curp`, `defunciones`, `divorcios`, `foraneas`, `inexistencias`, `inscripciones`, `matrimonios`, `nacimientos`, `peticiones`, `reconocimientos`, `actas_locales`.
- **En `create.php`:** Cambiar `<input type="hidden" name="csrf_token" value="...">` por `\Core\Auth::generateCSRF()`.
- **En `save.php`:** Validar usando `\Core\Auth::validateCSRF($_POST['csrf_token'])`. Si falla, rechazar con status error.
- **En `save.php`:** Añadir `\Core\Audit::log('INSERT', 'nombre_modulo', 'Se registró un nuevo trámite con ID: ...')` después de guardar exitosamente.

---

### 3. Roles y Permisos Granulares (Corrección de Faltantes)
Aunque la interfaz (`usuarios.php`) y el Core (`Auth.php`) ya manejan el guardado de permisos granulares por módulo, varios de los archivos core de ciertos módulos no los están validando en el Backend, lo que permitiría el acceso a través de la URL directa.

#### [MODIFY] Módulos Faltantes (index, create, save, data)
- Inyectar `\Core\Auth::checkPermission('permiso_correspondiente');` al inicio de cada archivo.
- **Afectados:**
  - `nacimientos/` (Requiere `permiso_registro_nacimientos`)
  - `defunciones/` (Requiere `permiso_registro_defunciones`)
  - `foraneas/` (Requiere `permiso_actas_foraneas`)
  - `inexistencias/` (Requiere `permiso_constancias`)
  - `peticiones/` (Requiere `permiso_tickets`)

---

### 4. Documentación y Versiones

#### [MODIFY] [CONTEXTO.md](file:///c:/xampp/htdocs/DRC/CONTEXTO.md)
- Actualizar para reflejar la implementación de la "Fase 6: Seguridad y Auditoría" (CSRF central, bitácora de auditoría, permisos consolidados).

#### [MODIFY] [versions.md](file:///c:/xampp/htdocs/DRC/docs/versions.md)
- Registrar la versión `1.3.0` detallando estas implementaciones de alta prioridad.

## Verification Plan
### Automated Tests
- No hay pruebas automatizadas configuradas en el proyecto actual.

### Manual Verification
1. Ingresar al sistema con un rol de "OPERADOR" que no tenga permiso a "Nacimientos" e intentar navegar manualmente a `/modules/nacimientos/index.php`. Se debe mostrar la pantalla de "Acceso Denegado 403".
2. Realizar un alta en cualquier módulo (ej. Ciudadanos).
3. Revisar la base de datos `drc_erp` tabla `bitacora_auditoria` para confirmar que se ha insertado el registro de trazabilidad con la acción "INSERT".
4. Intentar hacer un POST a `save.php` sin el `csrf_token` adecuado (vía Postman o inspeccionar elemento) y verificar que sea rechazado.
