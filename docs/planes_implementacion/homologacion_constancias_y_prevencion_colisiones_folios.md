# Plan y Actualización: Homologación de Constancias y Prevención de Colisiones en Folios

## 1. Problema de Colisión de Folios en Ventanilla de Seguimiento
- **Causa raíz:** Al insertar registros de demostración (`SEG-2026-00001` a `SEG-2026-00004`) en la tabla `peticiones`, el contador en `folios_secuencia` quedó por detrás (`3`). Al intentar aperturar un nuevo expediente, el sistema generaba `SEG-2026-00004` (duplicado) disparando un error 23000 de clave única.
- **Solución implementada:**
  1. En `Core\Database::generateFolio()` se implementó un mecanismo de verificación activa contra las tablas de datos (`peticiones` y `peticiones_ventanilla`). Si el folio candidato ya existe en la BD por algún registro preexistente o sembrado, el algoritmo avanza automáticamente `$next++` hasta garantizar un folio no utilizado y actualiza la secuencia en `folios_secuencia`.
  2. Se sincronizó la secuencia actual de `seguimiento_2026` para iniciar desde el siguiente número disponible (`SEG-2026-00005`).

## 2. Homologación de Catálogo de Constancias e Inexistencias
- Se homogeneizaron las 4 constancias oficiales en todos los componentes del ERP (Base de Datos, Servicios PSR-4, JavaScript y Vistas):
  1. **`CONSTANCIA DE DESCENDENCIA Y/O NO DESCENDENCIA`** (Clave: `INEXISTENCIA_DESCENDENCIA`)
  2. **`CONSTANCIA DE INEXISTENCIA DE REGISTRO DE DEUDOR ALIMENTARIO MOROSO`** (Clave: `NO_DEUDOR`)
  3. **`CONSTANCIA DE INEXISTENCIA DE REGISTRO DE MATRIMONIO`** (Clave: `INEXISTENCIA_MATRIMONIO`)
  4. **`CONSTANCIA DE INEXISTENCIA DE REGISTRO DE NACIMIENTO`** (Clave: `INEXISTENCIA_NACIMIENTO`)
- Se eliminaron las opciones ambiguas o no oficiales como `INEXISTENCIA DE DIVORCIO` del catálogo de constancias.
