# Fase de Analítica y Peticiones (Fase 9)

Este plan detalla la implementación del Dashboard Visual de KPIs utilizando Chart.js en `public/index.php`, alimentado por la API `public/api/stats.php`, y la integración de la Mesa de Ayuda con alertas y acciones de aprobación para los coordinadores.

## User Review Required

> [!IMPORTANT]
> **Esquema de Costos de Trámites**
> Debido a que no existe una columna en las tablas de la base de datos para almacenar el costo cobrado por trámite, definiremos en el código PHP una tarifa estándar por trámite para calcular la **Recaudación Proyectada**:
> - Nacimientos: $120.00 MXN
> - Defunciones: $180.00 MXN
> - Matrimonios: $420.00 MXN
> - Divorcios: $650.00 MXN
> - Reconocimientos: $190.00 MXN
> - Inscripciones: $580.00 MXN
> - Inexistencias: $220.00 MXN
> - Foráneas: $290.00 MXN
> - Trámites CURP: $80.00 MXN
> *Si deseas cambiar alguna de estas tarifas estándar, avísame en tu respuesta.*

## Open Questions

> [!NOTE]
> Las alertas de aprobación y acciones administrativas de tickets se habilitarán para usuarios con rol `ADMIN` y `SUPERVISOR`, tratándolos como coordinadores dentro del sistema gubernamental.

---

## Proposed Changes

### 1. Backend de Estadísticas y KPIs
Modificar la API de estadísticas para calcular las métricas históricas de los últimos 7 días, recaudación proyectada y carga operativa.

#### [MODIFY] [stats.php](file:///c:/xampp/htdocs/DRC/public/api/stats.php)
- Definir un array asociativo con el costo proyectado de cada trámite.
- Implementar consultas eficientes agrupadas por día para los últimos 7 días de cada uno de los 9 módulos de trámites, unificándolos en PHP para evitar sobrecargar la base de datos.
- Calcular la carga operativa total por módulo (conteo total de registros en cada tabla).
- Calcular la recaudación proyectada por módulo (conteo * costo) y la recaudación total histórica.
- Retornar un JSON estructurado con:
  - `cards`: Añadir `recaudacion_total`.
  - `processed_by_day`: `labels` (fechas formateadas, ej. "22 Jun") y `data` (total de trámites).
  - `recaudacion_proyectada`: `labels` (módulos) y `data` (monto total por módulo).
  - `carga_operativa`: `labels` (módulos) y `data` (conteo de trámites por módulo).

---

### 2. Frontend de Dashboard y Chart.js
Rediseñar la interfaz de `public/index.php` para incorporar los nuevos gráficos de KPIs.

#### [MODIFY] [index.php](file:///c:/xampp/htdocs/DRC/public/index.php)
- **Fila de Tarjetas Superiores**: Cambiar la rejilla a un grid dinámico de 5 columnas (`row-cols-1 row-cols-md-3 row-cols-xl-5 g-4`).
- **Nueva Tarjeta**: Agregar "Recaudación Proyectada" en verde/azul aqua con el icono `fa-money-bill-trend-up`.
- **Distribución de Gráficas**:
  - Reemplazar la gráfica única actual por una fila con la gráfica de tendencia de "Trámites Procesados por Día" (Line Chart) y el bloque de "Accesos Rápidos".
  - Crear una nueva fila con dos columnas de igual tamaño para:
    1. "Recaudación Proyectada por Módulo" (Bar Chart con barras de degradado verde/teal).
    2. "Carga Operativa por Módulo" (Doughnut Chart con una paleta de colores HSL curada).
- **Código Javascript**:
  - Configurar las tres instancias de Chart.js.
  - Aplicar degradados CSS a los fondos de las gráficas de línea y de barra para mejorar la visualización visual.
  - Asegurar la responsividad total en pantallas móviles.

---

### 3. Integración de Alertas de Aprobación en la Mesa de Ayuda
Conectar las peticiones de actas que requieren aprobación con la API de notificaciones para los coordinadores.

#### [MODIFY] [notifications.php](file:///c:/xampp/htdocs/DRC/public/api/notifications.php)
- Validar si el usuario en sesión posee el rol de coordinador (`ADMIN` o `SUPERVISOR`).
- Si cumple el rol, consultar las peticiones en la base de datos de tipo `CORRECCION_ACTA` cuyo estatus sea `ABIERTA`.
- Formatear estas peticiones pendientes como notificaciones críticas de color rojo con el icono de escudo `fa-shield-halved` y anteponerlas al historial de actividades habituales para que siempre aparezcan arriba.

---

### 4. Bandeja de Tickets Interactiva
Habilitar a los coordinadores para aprobar o rechazar peticiones desde su bandeja en tiempo real.

#### [MODIFY] [data.php](file:///c:/xampp/htdocs/DRC/modules/peticiones/data.php)
- Modificar la consulta SQL para seleccionar también la columna `p.id` del ticket.
- Devolver el `id` en el objeto JSON de cada fila.

#### [NEW] [update_status.php](file:///c:/xampp/htdocs/DRC/modules/peticiones/update_status.php)
- Validar que el usuario sea coordinador (`ADMIN` o `SUPERVISOR`).
- Validar token CSRF para prevenir ataques.
- Recibir `id` del ticket y la `accion` ('APROBAR' o 'RECHAZAR' o 'CERRAR').
- Si la acción es 'APROBAR', establecer `estatus = 'CERRADA'`, `usuario_asignado = ID_SESION`, `fecha_cierre = NOW()`, y registrar en auditoría: "SE APROBÓ LA CORRECCIÓN DE ACTA PARA EL TICKET FOLIO: XXX".
- Si la acción es 'RECHAZAR', establecer `estatus = 'CERRADA'`, y registrar en auditoría: "SE RECHAZÓ LA CORRECCIÓN DE ACTA PARA EL TICKET FOLIO: XXX".
- Retornar JSON de éxito o error.

#### [MODIFY] [index.php](file:///c:/xampp/htdocs/DRC/modules/peticiones/index.php)
- Incluir estilos y scripts de SweetAlert2 para diálogos elegantes de confirmación.
- Inyectar variables de sesión en Javascript para determinar si el usuario es coordinador (`isCoordinator`) y el token CSRF (`csrfToken`).
- Agregar la columna "Acciones" a la tabla HTML y a la definición de DataTables.
- Si el usuario es coordinador, renderizar botones de "Aprobar" (verde) y "Rechazar" (rojo) si el ticket está `ABIERTA`, o un botón de "Cerrar" si está `EN_PROGRESO`.
- Añadir el código AJAX para enviar la solicitud de actualización de estatus a `update_status.php` de forma segura.

---

## Verification Plan

### Automated Verification
- No aplica, ya que son interacciones visuales y flujos manuales de tickets, pero verificaremos que no haya errores de lint o sintaxis PHP ejecutando peticiones de prueba a los endpoints.

### Manual Verification
1. **Gráficas en Dashboard**:
   - Iniciar sesión como `admin@drc.gob.mx`.
   - Navegar al dashboard principal (`public/index.php`).
   - Confirmar la presencia de las 5 tarjetas con datos cargados, incluyendo "Recaudación Proyectada".
   - Verificar que se rendericen correctamente los tres gráficos interactivos.
2. **Alertas en Tiempo Real**:
   - Iniciar sesión como un operador normal, e ir a la Mesa de Ayuda (`modules/peticiones/create.php`).
   - Crear un ticket seleccionando "Corrección de Acta" como tipo.
   - Cerrar sesión e iniciar sesión como Administrador o Supervisor.
   - Comprobar que en la campana de notificaciones de la barra superior aparezca una alerta roja destacada solicitando aprobación para el ticket recién creado.
3. **Flujo de Aprobación**:
   - Ir a la bandeja de tickets (`modules/peticiones/index.php`).
   - Verificar la columna de "Acciones" y hacer clic en el botón "Aprobar" del ticket recién creado.
   - Aceptar el diálogo de confirmación de SweetAlert2.
   - Confirmar que el estatus cambie a `CERRADA`, que desaparezca de la lista de pendientes de aprobación y que se registre la acción en la bitácora de auditoría.
