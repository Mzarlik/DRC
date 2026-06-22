# Contexto del Proyecto: ERP - Dirección de Registro Civil

## 1. Descripción General
Este sistema es un ERP modular diseñado en PHP para la gestión, control y automatización de trámites de la Dirección de Registro Civil. El sistema debe ser altamente eficiente, seguro, con una interfaz limpia (estilo administrativo/ERP) y preparado para escalar dinámicamente a través de múltiples módulos de registro.

## 2. Stack Tecnológico y Librerías (Alta Calidad)
Para garantizar un acabado profesional, pulido y de alto rendimiento, se utilizará el siguiente stack:

* **Backend:** PHP 8.2+ (Arquitectura Limpia / MVC Modular) utilizando PDO para interacciones seguras con la Base de Datos.
* **Frontend UI:** Bootstrap 5 + FontAwesome 6 (Interfaz limpia, responsiva y oscura/clara corporativa).
* **Tablas Dinámicas:** DataTables.net (Server-side processing) para manejar grandes volúmenes de datos con paginación y búsqueda instantánea.
* **Componentes de Selección:** Tom Select o Select2 (Para búsquedas dinámicas en listados de estados, municipios y ciudadanos).
* **Alertas y Notificaciones:** SweetAlert2 (Para confirmaciones de guardado, errores y validaciones estéticas).
* **Exportación:** PhpSpreadsheet (Para la generación nativa y exacta de reportes en Excel con formatos de celda estrictos).

---

## 3. Hoja de Ruta del Desarrollo (Fases)

### Fase 1: Arquitectura Base y Módulo de Inexistencias (Core Actual)
* Configuración de la estructura de carpetas modular (`/core`, `/modules`, `/public`, `/assets`).
* Base de datos inicial (Tablas de usuarios, logs y configuración).
* **Submódulo:** Control de Constancia de Inexistencia Registral de Nacimiento.
    * Campos: ID, Línea de Pago (17 dígitos), Fecha de Trámite, Fecha de Llegada (Cálculo automático a 15 días), Nombre Completo, Estatus, Observaciones.
    * Exportación idéntica a la plantilla institucional en Excel (Formato de colores, títulos combinados y fuentes).

### Fase 2: Módulos de Registro Esenciales (Expansión Core)
* **Submódulo: Nacimientos:** Registro formal de nuevos ciudadanos, vinculación de padres (llaves foráneas a tabla de ciudadanos).
* **Submódulo: Defunciones:** Control de actas de fallecimiento, causas, y baja automática del estado activo en el sistema.
* Manejo de Llaves Foráneas unificadas: Creación de una tabla maestra de `personas` o `ciudadanos` para evitar la duplicidad de nombres entre módulos.

### Fase 3: Trámites Especiales y Peticiones
* **Submódulo: Foráneas:** Gestión de trámites y validaciones de actas provenientes de otros estados de la república.
* **Submódulo: Peticiones / Mesa de Ayuda:** Sistema de tickets internos para correcciones de actas, digitalizaciones pendientes o solicitudes ciudadanas especiales.
* **Dashboard ERP:** Gráficas estadísticas de trámites diarios/mensuales utilizando Chart.js.

### Fase 5: Expansión de Módulos y Roles Granulares (Versión 1.2.0)
* **Nuevos Módulos de Oficialía:** Matrimonios, Divorcios, Reconocimientos, Inscripciones y Trámites de CURP.
* **Módulo de Actas Locales:** Un buscador centralizado de actas locales (nacimientos, matrimonios, divorcios, defunciones, reconocimientos) con consulta detallada vía modal.
* **Constancias Expandidas:** Inclusión de Constancia de No Deudor Alimentario y constancias de inexistencia de matrimonio, nacimiento y descendencia.
* **Control de Acceso Granular:** Banderas booleanas individuales (11 permisos) por usuario para controlar minuciosamente el acceso a cada área.
* **Panel de Administración:** Gestión de usuarios y asignación de permisos desde la interfaz de administración.

### Fase 6: Seguridad y Auditoría (Versión 1.3.0)
* **Trazabilidad y Logs de Operaciones:** Creación de una bitácora de auditoría estandarizada para registrar cada inserción, modificación y eliminación, especificando qué usuario y en qué módulo lo realizó.
* **Prevención de Falsificación (CSRF):** Implementación de tokens vinculados a la sesión de usuario en cada uno de los formularios de guardado para mitigar inyecciones maliciosas.
* **Protección Granular Completa:** Implementación de barreras estrictas en el backend (`Auth::checkPermission`) para impedir accesos directos por URL a los archivos PHP si no se cuenta con los privilegios correspondientes.

---

## 4. Reglas de Negocio Estrictas (Data Rules)

1.  **Tratamiento de Cadenas:** Todos los nombres de personas, observaciones y estados deben ser almacenados y mostrados estrictamente en **MAYÚSCULAS** (`strtoupper` en PHP / `text-transform: uppercase` en CSS).
2.  **Precisión Numérica (Crucial):** Las líneas de pago o números de control son cadenas largas (Strings) de hasta 17-20 dígitos. **Nunca** deben tratarse como enteros (INT) ni en la base de datos (usar VARCHAR) ni en la exportación a Excel, para evitar que se corrompan con notación científica o redondeos.
3.  **Fechas Dinámicas:** La "Fecha de Llegada" se calcula sumando automáticamente 15 días hábiles/naturales a la "Fecha de Trámite", a menos que el módulo específico requiera edición manual.
4.  **Modularidad:** Cada nuevo registro (Nacimiento, Defunción, etc.) debe heredar la estructura de la interfaz principal para mantener la consistencia visual en todo el ERP.

---

## 5. Estado Actual del Proyecto (En Progreso / Producción)
El proyecto ha concluido el desarrollo de sus **6 fases principales** alcanzando la versión **1.3.0**. Durante la construcción, se adoptó la arquitectura de "Catálogo Maestro", donde existe una tabla centralizada de `ciudadanos` (con CURP, Nombre y Estado Vital). Todos los módulos se vinculan dinámicamente a esta tabla mediante búsquedas AJAX, eliminando la duplicidad de datos. Asimismo, se integró seguridad perimetral de sesiones, optimización responsiva, control avanzado de permisos granulares y trazabilidad de operaciones (Auditoría).