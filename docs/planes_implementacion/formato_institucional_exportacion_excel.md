# Formato y Diseño Institucional de Exportaciones Excel en el ERP DRC

## 1. Contexto y Objetivos
Mejora visual y ejecutiva integral para todas las hojas de cálculo generadas por los módulos del ERP (`core/Worker.php`), resolviendo espaciado, alturas de filas, alineación lógica de izquierda a derecha, colores de estados y nombres descriptivos oficiales.

## 2. Componentes Implementados

### A. Servicio Centralizado `Core\Services\ExcelReportFormatter`
- **Encabezados Institucionales:**
  - Color de Fondo: Guinda Oficial (`#5C1D24`)
  - Tipografía: Segoe UI, 10.5pt, Negrita, Color Blanco (`#FFFFFF`)
  - Altura de Fila 1: `28pt` con alineación vertical centrada.
  - Borde inferior institucional (`#3D1015`).
- **Filas de Datos:**
  - Altura de Filas: `22pt` con alineación vertical centrada.
  - Alternancia de color (Zebra Striping) en filas impares (`#F8F9FA`).
  - Bordes finos y limpios (`#E5E7EB`).
  - Auto-ajuste de ancho de columnas (`setAutoSize(true)`).
- **Insignias de Colores para Estados (`Estatus`):**
  - **`FINALIZADO` / `VALIDADA` / `ENTREGADO` / `COMPLETADO` / `ACTIVO` / `CERRADA`:** Fondo verde suave (`#D1E7DD`) + texto verde oscuro (`#0F5132`) en negrita.
  - **`PENDIENTE` / `EN_PROCESO` / `EN_PROGRESO` / `EN_ESPERA` / `ABIERTA`:** Fondo ámbar suave (`#FFF3CD`) + texto ámbar oscuro (`#664D03`) en negrita.
  - **`CANCELADO` / `RECHAZADO` / `INACTIVO` / `FINADO`:** Fondo rojo suave (`#F8D7DA`) + texto rojo oscuro (`#842029`) en negrita.
  - **`ATENDIENDO` / `VIVO`:** Fondo azul suave (`#CFE2FF`) + texto azul oscuro (`#084298`) en negrita.
- **Alineación Lógica de Columnas (Izquierda a Derecha):**
  - IDs, Folios, Fechas y Estatus: Centrados (`center`).
  - Nombres, Tipos, Descripciones y Observaciones: Alineados a la izquierda (`left`).
  - Líneas de Pago, CURPs y Números de Acta: Strings explícitos sin truncamiento ni notación científica.
- **Mapeo Oficial de Constancias:**
  - Transforma las claves internas (`INEXISTENCIA_MATRIMONIO`, `NO_DEUDOR`, etc.) en sus denominaciones oficiales completas legibles.

### B. Módulos y Generadores Actualizados en `core/Worker.php`
- Inexistencias y Constancias
- Reportes Cruzados Generales
- Padrón de Ciudadanos
- Nacimientos
- Matrimonios
- Divorcios
- Defunciones
- Inscripciones
- Reconocimientos
- Actas Locales
- Actas Foráneas
- Usuarios del Sistema
- Bitácora de Auditoría
- Registro de Errores
