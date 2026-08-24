# Plan y Actualización: Estructura del Menú de Ventanilla y Separación de Módulos

## 1. Requerimiento del Usuario
- Al dar clic en **"Petición Rápida"** dentro del menú de Ventanilla, abrir directamente el formulario de registro (`create.php`) para agilizar la atención en mostrador.
- Separar claramente la tabla de consulta y seguimiento en un ítem dedicado dentro de Ventanilla para brindar un mejor control operativo.

## 2. Nueva Estructura del Menú Lateral (Ventanilla)
En las 27 vistas del sistema se integró la siguiente distribución de 4 opciones:

1. ⚡ **Petición Rápida** (`modules/peticion_rapida/create.php`): Abre directamente el formulario para capturar un trámite de inmediato con generación de folio y ticket.
2. 📋 **Control de Peticiones** (`modules/peticion_rapida/index.php`): Vista de tabla interactiva con búsqueda en tiempo real, filtros, cambios de estado, edición, impresión de tickets y soft-delete.
3. 📊 **Reporte Diario** (`modules/peticion_rapida/reporte_diario.php`): Reporte consolidado de actividades del día y exportación oficial.
4. 📁 **Ventanilla de Seguimiento** (`modules/peticiones/index.php`): Expedientes y dictámenes institucionales de mayor alcance (`SEG-2026-XXXXX`).

## 3. Navegación Cruzada en Vistas
- En `create.php`: Botón directo *"Control de Peticiones"* y *"Reporte Diario"*.
- En `index.php`: Botón directo *"+ Nueva Petición Rápida"* y *"Reporte Diario Oficial"*.
