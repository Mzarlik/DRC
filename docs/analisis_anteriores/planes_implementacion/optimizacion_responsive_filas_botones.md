# Plan de Implementación: Optimización Responsive (Filas Compactas y Mejor Distribución de Botones)

Optimizar la experiencia móvil y responsiva en todo el ERP DRC, resolviendo la sobre-extensión vertical de datos (filas/tarjetas muy altas) y mejorando la distribución, jerarquía y usabilidad de botones de acción, filtros, pestañas y barras de navegación móvil.

---

## 1. Diagnóstico de la Situación Actual

Basado en la inspección visual de las capturas móviles (iPhone 12 Pro / 390x844) y el código fuente:

1. **Altura excesiva de filas y tarjetas móviles (`.mobile-record-card`)**:
   - Cada registro en vista de tarjeta consume entre 350px y 420px de altura (casi media pantalla por registro).
   - Espaciados muy holgados: cabecera con padding `p-3` (32px vertical), cuerpo con `p-3`, filas internas (`.card-detail-row`) con `mb-2 pb-2`, etiquetas multilínea sin límite de ancho y sin alineación compacta.
   - Las insignias de estatus (*PENDIENTE*, *FINALIZADO*) y botones de acciones tienen paddings sobredimensionados.
   - En vista de tabla clásica (`.dataTable`), el padding estándar de celdas (`12px` / `0.75rem`) desborda horizontal y verticalmente.

2. **Mala distribución y desalineación de botones en móvil**:
   - **Barra de acciones móvil (`.mobile-action-bar`)**: `global.js` traslada los botones principales del encabezado al final de la página, pero no existía CSS definido para `.mobile-action-bar`, dejando botones como *Exportar a Excel* y *+ Nueva Constancia* apilados sin estilo fijo ni diseño ergonómico.
   - **Botones de filtros (*Filtrar* y *Limpiar*)**: En pantallas pequeñas, ocupan 2 filas completas al 100% de ancho una sobre otra, desperdiciando espacio útil.
   - **Pestañas y Submenús (*Nav-pills* / *Nav-tabs*)**: Pestañas como *Registro de Acciones / Registro de Errores* o *Constancias Emitidas / Peticiones de Ventanilla* se apilan o envuelven toscamente.
   - **Botones de acción dentro de tarjetas (`.card-actions-row`)**: Separación y márgenes excesivos (`mt-3 pt-3`) que alargan innecesariamente cada tarjeta.
   - **Controles de DataTables (Paginador / Selector de cantidad / Buscador)**: Apilados verticalmente con márgenes amplios.

---

## 2. Decisiones de Diseño y Propuesta

```
┌──────────────────────────────────────────────────────────┐
│                   VISTA MÓVIL OPTIMIZADA                 │
├──────────────────────────────────────────────────────────┤
│ [ ☰ ] [ 🌙 ] [ 🔔 ]                        (AD) Admin ▾ │
│                                                          │
│ Módulo de Constancias                                    │
│ [ Constancias Emitidas (12) ] [ Peticiones Ventanilla (1)│ <- Segmented Control (50%/50%)
│                                                          │
│ ┌─ Filtros ────────────────────────────────────────────┐ │
│ │ Tipo: [ Todas las constancias                      ▾]│ │
│ │ [ 🔍 Filtrar (50%) ]   [ 🔄 Limpiar (50%) ]          │ │ <- Botones 50/50 compactos
│ └──────────────────────────────────────────────────────┘ │
│                                                          │
│ [Mostrar: 10 ▾] [Buscar: ____________]   [▦ Tarjetas|▤]  │ <- Controles alineados y compactos
│                                                          │
│ ┌─ BEATRIZ ADRIANA CORDERO ──────────── [ 002608... ] ─┐ │
│ │ Tipo: CONSTANCIA DE DESCENDENCIA...                  │ │ <- Fila compacta (py-1, 13px)
│ │ Línea Pago: 00260819001289120037                     │ │
│ │ Fecha Trámite: 2026-08-12  |  Llegada: 2026-08-22    │ │ <- Fila combinada / compacta
│ │ Estatus: [ PENDIENTE ]      [ 👁 Ver ] [ ✏ Editar ]  │ │ <- Acciones alineadas al estatus
│ └──────────────────────────────────────────────────────┘ │ (~140px alto vs 380px actual)
│                                                          │
│ ┌─ RICARDO SALGADO RÍOS ──────────────── [ 002608... ] ─┐│
│ │ Tipo: CONSTANCIA DE INEXISTENCIA DE NACIMIENTO       │ │
│ │ Línea Pago: 00260819001289120034                     │ │
│ │ Fecha Trámite: 2026-08-05  |  Llegada: 2026-08-15    │ │
│ │ Estatus: [ FINALIZADO ]     [ 👁 Ver ] [ 🖨 Imprimir]│ │
│ └──────────────────────────────────────────────────────┘ │
│                                                          │
├──────────────────────────────────────────────────────────┤
│ [ 📊 Exportar a Excel (45%) ]  [ ➕ Nueva Constancia (55%)│ <- Barra Flotante Inferior Fija
└──────────────────────────────────────────────────────────┘
```

---

## 3. Cambios Propuestos

### A. Capa de Estilos Globales (`assets/css/style.css`)

- **Compactación de Tarjetas Móviles (`.mobile-record-card`)**:
  - `padding` en cabecera: reducir de `p-3` a `8px 12px`. Título con `font-size: 0.875rem`, peso `600`, color primario institucional.
  - `padding` en cuerpo: reducir de `p-3` a `8px 12px`.
  - `.card-detail-row`: reducir espacio vertical a `padding: 3px 0; margin-bottom: 2px;`.
  - Distribución interna: etiqueta al 35-40% (`font-size: 0.75rem`, texto secundario), valor al 60-65% (`font-size: 0.8125rem`, alineado a la derecha, `line-height: 1.25`).
  - `.card-actions-row`: reducción a `margin-top: 6px; padding-top: 6px; display: flex; gap: 6px; justify-content: flex-end;`.
  - Badges e insignias móviles: `font-size: 0.7rem; padding: 2px 6px; border-radius: 4px;`.
- **Compactación Global de Tablas (`table.dataTable`, `.table-sm`)**:
  - Altura de filas en tablas: `padding: 5px 8px !important; font-size: 0.8125rem !important; vertical-align: middle;`.
  - Botones de celda (`.btn-group-sm > .btn`, `.btn-sm`): `padding: 2px 6px; font-size: 0.75rem;`.
- **Barra de Acciones Flotante Inferior (`.mobile-action-bar`)**:
  - `position: fixed; bottom: 0; left: 0; right: 0; z-index: 1040;`.
  - Fondo con efecto de cristal moderno (`background: var(--bg-surface); backdrop-filter: blur(12px); border-top: 1px solid var(--border-color); box-shadow: 0 -4px 16px rgba(0,0,0,0.08);`).
  - Espaciado ergonómico con soporte para área segura móvil: `padding: 10px 14px calc(10px + env(safe-area-inset-bottom, 0px));`.
  - Distribución en flexbox (`gap: 8px; display: flex; align-items: center; justify-content: stretch;`).
  - Botones secundarios (Excel/Exportar): `flex: 1; padding: 9px 10px; font-size: 0.8125rem; font-weight: 600;`.
  - Botones principales (+ Nuevo/Registrar): `flex: 1.2; padding: 9px 12px; font-size: 0.8125rem; font-weight: 600;`.
  - `body` móvil con compensación de scroll: `padding-bottom: 80px !important;`.
- **Distribución de Botones de Filtro en Móvil**:
  - Regla para `.filter-actions-row` o `.card-body .row` de filtros: en pantallas `< 768px`, los botones de *Filtrar* y *Limpiar* se distribuyen 50% / 50% lado a lado en lugar de apilarse.
- **Pestañas y Píldoras Móviles (`.nav-pills`, `.nav-tabs`)**:
  - Segmented control de 2 columnas (`display: grid; grid-template-columns: 1fr 1fr; gap: 6px;`) para pestañas dobles.
  - Pestañas múltiples con scroll horizontal táctil suave (`overflow-x: auto; flex-wrap: nowrap;`).
  - Píldoras compactas (`padding: 7px 10px; font-size: 0.8125rem; text-align: center;`).
- **Selector de Vista (*Tarjetas / Tabla*)**:
  - Diseño más compacto (`.btn-group-sm`, padding `3px 8px`, `font-size: 0.75rem;`).
- **Modales y Formularios en Móvil**:
  - Pie de modales (`.modal-footer`): botones *Cancelar* y *Guardar* distribuidos en 2 columnas equilibradas (`50% / 50%`) en móvil.

---

### B. Capa de Lógica Javascript Global (`assets/js/global.js`)

- **Generador de Tarjetas Móviles (`rebuildMobileCards`)**:
  - Refactorizar el marcado HTML de las tarjetas para aplicar clases más compactas (`p-2 px-3` en header y body, `py-1` en filas).
  - Integrar los botones de acción (`card-actions-row`) directamente en la misma línea del Estatus cuando quepan, ahorrando una fila completa.
- **Reubicación Inteligente de Botones de Acción Móvil (`setupMobileActionButtons`)**:
  - Ampliar el selector para capturar tanto botones en el encabezado principal como botones de exportación flotantes (`.d-flex.justify-content-end.mb-3`).
  - Evitar duplicación de botones o barras fantasma al redimensionar la ventana o al interactuar con AJAX.
  - Aplicar las clases y estilos de flexbox para que los botones mantengan su ícono y texto legible sin desbordar la pantalla.
- **Controles de DataTables en Móvil**:
  - Reorganizar el contenedor de DataTables (`.dataTables_length` y `.dataTables_filter`) para que en móvil ocupen una sola fila o un diseño compacto de dos niveles sin márgenes exagerados.

---

### C. Módulos y Vistas Específicas

- **`public/auditoria.php`**: Ajustar el contenedor de botones de filtro (*Filtrar* y *Limpiar*) con clases responsivas (`col-6 col-md-3` y `col-6 col-md-2` o contenedor flex).
- **Módulos de Negocio**: Asegurar que todos los módulos compartan las nuevas clases y comportamiento responsivo uniforme.

---

## 4. Plan de Verificación

### Pruebas Automatizadas
```bash
vendor\bin\phpunit
```

### Pruebas Visuales y Responsivas Manuales
1. **Emulación en resoluciones móviles estándar**:
   - iPhone SE (375x667), iPhone 12/13/14 Pro (390x844), Galaxy S20 (360x800), iPad Mini (768x1024).
2. **Verificación de Densidad y Altura**:
   - En *Módulo de Constancias* (`modules/inexistencias/index.php`): comprobar reducción de altura a ~150px.
   - En *Auditoría* (`public/auditoria.php`): validar compacidad de filas y filtros.
3. **Verificación de Distribución de Botones**:
   - Botones *Filtrar* y *Limpiar* 50%/50% en móvil.
   - Barra flotante fija inferior ergonómica.
