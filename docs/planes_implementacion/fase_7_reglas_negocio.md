# Fase de Reglas de Negocio Estrictas (Lógica Central)

Este plan detalla la implementación de reglas de negocio cruzadas, consistencia del catálogo maestro de ciudadanos y la generación segura de folios para evitar colisiones por concurrencia.

## User Review Required

> [!IMPORTANT]
> **Cambios en la Base de Datos**
> Para la generación segura de folios, crearé una nueva tabla auxiliar `folios_secuencia` (`modulo` VARCHAR, `ultimo_folio` INT) que permitirá llevar un control transaccional preciso y bloqueos de fila (`FOR UPDATE`) para evitar duplicidad de folios en el mismo milisegundo. Este cambio requerirá ejecutar un pequeño script SQL en tu base local.

## Open Questions

> [!WARNING]
> Respecto al bloqueo por defunción (`estado_vital = 'FINADO'`), implementaré el rechazo en los módulos de **Matrimonios**, **Divorcios** y **Reconocimientos**. ¿Consideras que también se debe bloquear en Trámites CURP e Inscripciones (Actas Extranjeras), o estos últimos pueden aplicar a ciudadanos finados (por trámites póstumos)? Por ahora el bloqueo aplicará a Matrimonios, Divorcios y Reconocimientos.

## Proposed Changes

### 1. Sincronización del Estatus Vital y Bloqueos Lógicos
El cambio a `FINADO` al capturar una defunción ya se encuentra parcialmente implementado en la base de datos (`defunciones/save.php`), pero la barrera que previene trámites futuros no existe.

#### [MODIFY] Archivos de guardado (`save.php`)
- **Módulos:** `matrimonios`, `divorcios`, `reconocimientos`
- Inyectar una consulta inicial que verifique el `estado_vital` de los ciudadanos involucrados (`contrayente_1_id`, `contrayente_2_id`, `reconocedor_id`, `reconocido_id`).
- Si alguno se encuentra como `FINADO`, abortar la transacción y devolver un `status: error` detallando que el ciudadano está registrado como fallecido.

---

### 2. Validaciones Cruzadas (Inexistencias)
Evitar emitir una constancia de inexistencia si existe un registro local previo.

#### [MODIFY] [modules/inexistencias/save.php](file:///c:/xampp/htdocs/DRC/modules/inexistencias/save.php)
- Antes del `INSERT`, realizar una consulta a la tabla `ciudadanos` buscando coincidencias (exactas o `LIKE`) entre el `nombre_completo` introducido y la concatenación de `nombre + apellido_paterno + apellido_materno` del catálogo.
- Si se encuentran coincidencias y la constancia solicitada es `INEXISTENCIA_NACIMIENTO`, devolver un error advirtiendo al operador de la existencia del registro local.

---

### 3. Gestión de Folios Únicos Transaccionales
Sustituir la generación actual (basada en `uniqid()` que puede chocar en milisegundos idénticos) por un motor robusto y secuencial.

#### [MODIFY] [docs/database.sql](file:///c:/xampp/htdocs/DRC/docs/database.sql)
- Añadir la creación de la tabla `folios_secuencia`.

#### [MODIFY] [core/Database.php](file:///c:/xampp/htdocs/DRC/core/Database.php)
- Agregar un método estático `generateFolio($modulo, $prefix)`:
  - Inicia una transacción.
  - Ejecuta `SELECT ultimo_folio ... FOR UPDATE` (Bloquea la fila para otros hilos de PHP de manera atómica).
  - Incrementa el número y hace el `UPDATE`.
  - Retorna el folio con el prefijo y los ceros a la izquierda (ej. `TK-2026-00001`).

#### [MODIFY] [modules/peticiones/save.php](file:///c:/xampp/htdocs/DRC/modules/peticiones/save.php)
- Reemplazar la asignación manual `$folio = 'TK-' . date('Y') . '-' ...` por el llamado transaccional a la clase `Database`.

---

### 4. Actualización del Histórico
- Tal como acordamos, este plan de implementación será copiado a la carpeta `docs/planes_implementacion/fase_7_reglas_negocio.md` en cuanto inicie la ejecución para su resguardo histórico.

## Verification Plan
### Manual Verification
1. Generar una defunción para un ciudadano de prueba. Su estado cambiará a "FINADO".
2. Intentar registrar un matrimonio vinculando a ese ciudadano; el sistema debe impedirlo mostrando la alerta correspondiente.
3. Solicitar una Constancia de Inexistencia capturando un nombre que coincida con el Catálogo de Ciudadanos; el sistema debe advertir la colisión.
4. Crear un ticket en Peticiones y verificar que el folio generado siga la secuencia estricta basada en base de datos.
