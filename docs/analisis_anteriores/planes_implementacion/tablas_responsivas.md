# Plan de Implementación: Adaptabilidad de Tablas de Datos (Fase 2)

Este plan detalla el rediseño responsivo para las tablas de datos del ERP, las cuales contienen alta densidad de información (hasta 8 columnas o más, como Inexistencias, Nacimientos, etc.) y suelen desbordar en dispositivos móviles.

Proponemos dos estrategias de adaptabilidad que se pueden combinar o seleccionar:

---

## Estrategias Propuestas

### Opción A: Extensión Responsive de DataTables (Ocultación de Columnas)
* **Descripción**: Se integran los estilos y scripts de la extensión oficial `Responsive` de DataTables.
* **Comportamiento**: Las columnas que no quepan en el ancho de la pantalla se ocultan automáticamente de forma dinámica. En su lugar, se añade un botón verde con un símbolo `+` al inicio de la fila. Al hacer clic, se despliega un panel secundario debajo de la fila con los datos de las columnas ocultas (como Observaciones o Fecha de Llegada).
* **Pros**: Nativo de DataTables, muy limpio para pantallas medianas (tabletas) y mantiene la estructura de filas.

### Opción B: Transmutación CSS a Tarjetas (Cards Móviles)
* **Descripción**: Transformamos la tabla HTML a un formato de tarjetas mediante CSS Grid/Flexbox y una media query.
* **Comportamiento**: En pantallas móviles (< 768px), la tabla completa (`<table>`, `<thead>`, `<tbody>`, `<tr>`, `<td>`) se reestructura para comportarse como bloques (`display: block`). Cada fila (`<tr>`) se convierte en una tarjeta física (`.card`) y cada celda (`<td>`) se alinea en un renglón, mostrando el nombre de la columna a la izquierda (en negrita) y el valor a la derecha.
* **Pros**: Diseño UI/UX moderno, se consume igual que en una app móvil nativa, y no añade dependencias JS externas pesadas.

> [!TIP]
> **Propuesta de Integración Inteligente**:
> Implementaremos la **Opción B (Transmutación a Tarjetas)** como comportamiento responsivo por defecto agregando una regla CSS general y un inyector jQuery dinámico en [`global.js`](file:///c:/xampp/htdocs/DRC/assets/js/global.js). 
> El inyector leerá automáticamente los encabezados de la tabla (`<th>`) y los aplicará como atributos `data-label` en las celdas cada vez que la tabla se dibuje (paginación, búsquedas, filtros). De este modo, todas las tablas del sistema se convertirán en tarjetas en celulares de manera automática y sin cambiar el HTML de cada archivo.

---

## Cambios Propuestos

### CSS y Estilos

#### [MODIFY] [style.css](file:///c:/xampp/htdocs/DRC/assets/css/style.css)
Añadir la regla de transmutación responsiva a tarjetas para tablas de datos:
```css
@media (max-width: 767.98px) {
    /* Forzar elementos a comportarse como bloques */
    .table-responsive-cards table.dataTable,
    .table-responsive-cards table.dataTable thead,
    .table-responsive-cards table.dataTable tbody,
    .table-responsive-cards table.dataTable th,
    .table-responsive-cards table.dataTable td,
    .table-responsive-cards table.dataTable tr {
        display: block !important;
    }

    /* Ocultar encabezados superiores de forma accesible */
    .table-responsive-cards table.dataTable thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
    }

    /* Cada fila se transforma en una tarjeta con sombra y bordes redondeados */
    .table-responsive-cards table.dataTable tr {
        border: 1px solid var(--border-color) !important;
        border-radius: 8px !important;
        margin-bottom: 15px !important;
        padding: 10px !important;
        background-color: var(--card-bg) !important;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05) !important;
    }

    /* Estilo de cada celda individual */
    .table-responsive-cards table.dataTable td {
        border: none !important;
        position: relative;
        padding-left: 50% !important;
        text-align: right !important;
        white-space: normal !important;
        padding-top: 8px !important;
        padding-bottom: 8px !important;
    }

    /* Inyección de etiquetas de columna en el pseudo-elemento ::before */
    .table-responsive-cards table.dataTable td::before {
        content: attr(data-label);
        position: absolute;
        left: 10px;
        width: 45%;
        padding-right: 10px;
        text-align: left;
        font-weight: bold;
        color: var(--text-color);
    }
}
```

---

## Plan de Verificación

### Pruebas Visuales y de Flujo:
1. **Verificación en Móvil (< 768px)**:
   * Al reducir la pantalla a móvil, las tablas de Inexistencias, Defunciones, etc. deben desaparecer como tablas anchas y estructurarse como un listado vertical de tarjetas.
   * Cada tarjeta debe contener la información de la fila con su etiqueta en negrita a la izquierda (ej: *Línea de Pago*, *Fecha Trámite*, *Estatus*) y el valor correspondiente a la derecha.
2. **Paginación y Filtros**:
   * Cambiar de página o realizar una búsqueda en móvil. Validar que la inyección de `data-label` se ejecute en el evento `draw` y las nuevas tarjetas muestren las etiquetas correctas de inmediato.
