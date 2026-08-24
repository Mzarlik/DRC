# Correlación de Peticiones de Ventanilla por Módulo Operativo

## Descripción del Proyecto
Implementar un sistema de correlación bidireccional y bandejas de atención para que cada petición rápida capturada en mostrador (Ventanilla) se canalice automáticamente al módulo correspondiente (ej. Constancias, Actas Foráneas, Actas Locales, CURP, Nacimientos, etc.), manteniendo el **Control de Peticiones de Ventanilla** como la mesa de control general y permitiendo a los operadores de cada área dar seguimiento, precargar datos y finalizar trámites desde su propio módulo.

---

## 1. Mapeo Canónico de Trámites a Módulos

En `Core\Services\PeticionRapidaService` definiremos la matriz de enrutamiento por módulo:

| Módulo del Sistema | Trámites de Ventanilla Asignados |
| :--- | :--- |
| **Constancias** (`modules/inexistencias/`) | `CONSTANCIA_DESCENDENCIA`, `CONSTANCIA_DEUDOR_MOROSO`, `CONSTANCIA_INEXISTENCIA_MATRIMONIO`, `CONSTANCIA_INEXISTENCIA_NACIMIENTO`, `PASES_CAJA_CONSTANCIAS` |
| **Actas Foráneas** (`modules/foraneas/`) | `ACTA_FORANEA`, `ACTAS_LOCALES_FORANEAS_ENTREGADAS` |
| **Actas Locales** (`modules/actas_locales/`) | `COPIA_FIEL`, `COPIAS_CERTIFICADAS`, `ACTAS_ELABORADAS_ENTREGADAS` |
| **Servicios CURP** (`modules/curp/`) | `CORRECCIONES_CURP`, `ACTUALIZACIONES_CURP`, `CURP_BIOMETRICO` |
| **Nacimientos** (`modules/nacimientos/`) | `REGISTRO_NACIMIENTO`, `REGISTRO_NACIMIENTO_HG`, `SOLICITUD_EXTEMPORANEO`, `ACTA_FIRMADA_EXTEMPORANEO`, `EXPEDIENTES_NACIMIENTO` |
| **Matrimonios** (`modules/matrimonios/`) | `EXPEDIENTES_MATRIMONIO`, `BODAS_REALIZADAS` |
| **Divorcios** (`modules/divorcios/`) | `EXPEDIENTES_DIVORCIO`, `CAPTURA_DIVORCIO`, `DIVORCIO_ADMINISTRATIVO`, `DIVORCIO_JUDICIAL` |
| **Defunciones** (`modules/defunciones/`) | `REGISTRO_DEFUNCION`, `ACTA_CERTIFICADA_DEFUNCION` |
| **Inscripciones** (`modules/inscripciones/`) | `INSCRIPCION_NACIMIENTO` |
| **Reconocimientos** (`modules/reconocimientos/`) | `IDENTIDAD_GENERO` |
| **Ventanilla de Seguimiento** (`modules/peticiones/`) | `CORRECCION_OFICIALES`, `CORRECCIONES_REALIZADAS`, `CORRECCIONES_ADMINISTRATIVAS`, `BUSQUEDA_ARCHIVO`, `BUSQUEDA_SISTEMA`, `EXPEDIENTES_PENDIENTES`, `EXPEDICION_PASES_CAJA`, etc. |

---

## 2. Cambios Propuestos

### Componente de Servicio y Backend (`core/Services/`)

#### [MODIFY] [`core/Services/PeticionRapidaService.php`](file:///c:/xampp/htdocs/DRC/core/Services/PeticionRapidaService.php)
- Añadir método `getModuloPorTramite(string $tipoPeticion): string`.
- Añadir método `getTramitesPorModulo(string $modulo): array`.
- Añadir método `getConteoPendientesPorModulo(string $modulo): int`.

#### [NEW] [`modules/peticion_rapida/modulo_peticiones_data.php`](file:///c:/xampp/htdocs/DRC/modules/peticion_rapida/modulo_peticiones_data.php)
- Endpoint AJAX para alimentar las tablas de peticiones de cada módulo por DataTables (`?modulo=inexistencias`, etc.) con soporte de búsqueda, paginación, filtros de estado (`PENDIENTE`, `EN_PROCESO`, `ENTREGADO`) y seguridad CSRF/Auth.

---

### Módulos Operativos (Vistas y Controladores)

Implementar pestañas limpias con Bootstrap 5 en cada módulo:
- **Pestaña 1:** 📋 *Registros Emitidos* (Tabla actual del módulo)
- **Pestaña 2:** ⚡ *Peticiones de Ventanilla* (Con badge con contador en tiempo real de peticiones pendientes)

#### [MODIFY] [`modules/inexistencias/index.php`](file:///c:/xampp/htdocs/DRC/modules/inexistencias/index.php)
- Agregar pestaña "Peticiones de Ventanilla" para atender solicitudes de constancias de inexistencia y deudores morosos con botón de precarga directa en el modal/formulario de expedición.

#### [MODIFY] [`modules/foraneas/index.php`](file:///c:/xampp/htdocs/DRC/modules/foraneas/index.php)
- Agregar pestaña "Peticiones de Ventanilla" para actas foráneas interestatales con botón de atención rápida hacia `create.php`.

#### [MODIFY] [`modules/actas_locales/index.php`](file:///c:/xampp/htdocs/DRC/modules/actas_locales/index.php)
- Agregar pestaña "Peticiones de Ventanilla" para copias fieles y certificadas locales.

#### [MODIFY] [`modules/curp/index.php`](file:///c:/xampp/htdocs/DRC/modules/curp/index.php)
- Agregar pestaña "Peticiones de Ventanilla" para trámites y correcciones CURP.

#### [MODIFY] [`modules/nacimientos/index.php`](file:///c:/xampp/htdocs/DRC/modules/nacimientos/index.php), [`modules/matrimonios/index.php`](file:///c:/xampp/htdocs/DRC/modules/matrimonios/index.php), [`modules/defunciones/index.php`](file:///c:/xampp/htdocs/DRC/modules/defunciones/index.php), [`modules/divorcios/index.php`](file:///c:/xampp/htdocs/DRC/modules/divorcios/index.php)
- Agregar pestaña de peticiones de ventanilla correspondiente a cada registro de estado civil.

---

## 3. Plan de Verificación

### Pruebas Automatizadas y de Backend
1. **Verificación de Mapeo:** Ejecutar script unitario que valide que los 29 trámites de ventanilla tengan su módulo destino asignado sin excepciones.
2. **Prueba de Endpoint AJAX:** Consultar `modulo_peticiones_data.php?modulo=inexistencias` y comprobar que filtre exactamente los registros de constancias.
3. **Smoke Tests DRC:** Ejecutar la suite completa de 14 smoke tests (`scripts/run_smoke_tests.php`).
4. **PHPUnit:** Ejecutar la suite de tests unitarios (`vendor/bin/phpunit`).
