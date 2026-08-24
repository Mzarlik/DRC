# Plan y Actualización: Tipos Oficiales de Constancias e Inexistencias

## 1. Tipos Oficiales Registrados
Se configuraron formalmente los 4 tipos oficiales de constancia solicitados tanto en la base de datos como en los módulos de **Inexistencias** y **Petición Rápida de Ventanilla**:

1. **`CONSTANCIA DE DESCENDENCIA Y/O NO DESCENDENCIA`** (Clave: `INEXISTENCIA_DESCENDENCIA` / `CONSTANCIA_DESCENDENCIA`, Código: `CND`)
2. **`CONSTANCIA DE INEXISTENCIA DE REGISTRO DE DEUDOR ALIMENTARIO MOROSO`** (Clave: `NO_DEUDOR` / `CONSTANCIA_DEUDOR_MOROSO`, Código: `CID`)
3. **`CONSTANCIA DE INEXISTENCIA DE REGISTRO DE MATRIMONIO`** (Clave: `INEXISTENCIA_MATRIMONIO` / `CONSTANCIA_INEXISTENCIA_MATRIMONIO`, Código: `CIM`)
4. **`CONSTANCIA DE INEXISTENCIA DE REGISTRO DE NACIMIENTO`** (Clave: `INEXISTENCIA_NACIMIENTO` / `CONSTANCIA_INEXISTENCIA_NACIMIENTO`, Código: `CIN`)

## 2. Cambios Implementados
- **Estructura de Base de Datos:**
  - Creadas las tablas `catalogos` y `catalogo_opciones` con integridad referencial.
  - Modificada la columna `inexistencias.tipo_constancia` a `VARCHAR(150)` para soportar claves y descripciones completas sin restricciones rígidas de ENUM.
- **Módulo de Inexistencias (`modules/inexistencias/`):**
  - Actualizados los `<select>` de filtro y modal de registro en `index.php` y `create.php` para mostrar los nombres oficiales completos.
  - Integrado un mecanismo de respaldo automático (fallback) que previene dropdowns vacíos en cualquier escenario.
  - Mapeo en `data.php` para mostrar los títulos oficiales en la tabla interactiva de DataTables.
- **Módulo de Petición Rápida (`modules/peticion_rapida/`):**
  - Actualizado `Core\Services\PeticionRapidaService::TRAMITES` con los códigos `CND`, `CID`, `CIM` y `CIN` y sus descripciones oficiales correspondientes.
  - Sincronizados los formularios de nueva petición (`create.php`), edición (`edit.php`) y el reporte diario consolidado (`reporte_diario.php`).
