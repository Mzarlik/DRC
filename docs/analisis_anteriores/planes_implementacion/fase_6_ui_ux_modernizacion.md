# Plan de Implementación — Fase 6: Modernización Integral de UI/UX y Experiencia de Usuario

Este plan describe la transformación visual y de interacción del ERP DRC para elevarlo a un estándar de interfaz gubernamental moderno, limpio y profesional (alineado a la identidad institucional Guinda `#691C32` + Dorado `#B38E5D` + Slate Neutro `#F8FAFC`).

---

## User Review Required

> [!IMPORTANT]
> **Paleta Institucional Armónica:**
> Se sustituyen los colores planos y saturados de las tarjetas KPI (azul/rojo/amarillo brillante) por una paleta coherente basada en degradados ejecutivos, tipografía limpia (Inter / Segoe UI) y bordes redondeados (`border-radius: 12px`).

> [!NOTE]
> **Corrección de Formularios y TomSelect:**
> Se eliminan los bordes toscos de 2px en formularios como *Petición Rápida* y se corrige la integración visual de **TomSelect** para evitar superposiciones de texto con las etiquetas.

---

## Proposed Changes

### 1. Sistema de Diseño Global (`assets/css/style.css`)

#### [MODIFY] [assets/css/style.css](file:///C:/xampp/htdocs/DRC/assets/css/style.css)
- **Variables CSS:** Nuevos tokens semánticos `--color-primary: #691C32`, `--color-accent: #B38E5D`, `--color-surface: #FFFFFF`, `--color-bg: #F8FAFC`, `--color-border: #E2E8F0`, `--radius-card: 12px`.
- **KPI Cards:** Estilos para tarjetas con gradientes sutiles, iconos con fondo translúcido (`backdrop-filter`) y efectos hover suaves (`translateY(-2px)`).
- **Formularios:** Eliminación de bordes duros, campos con bordes `#cbd5e1`, esquinas redondeadas de 8px y anillo de foco institucional `ring-guinda`.
- **DataTables:** Cabeceras en Slate suave (`#f8fafc`), filas con hover limpio (`#f1f5f9`), badges `rounded-pill` y buscador con icono integrado.
- **Sidebar & Header:** Header con sombra suave y sidebar en degradado guinda profundo con acentos dorados en el elemento activo.

---

### 2. Modernización del Dashboard Principal (`public/index.php`)

#### [MODIFY] [public/index.php](file:///C:/xampp/htdocs/DRC/public/index.php)
- Rediseño de las 5 tarjetas superiores (KPIs) con micro-iconos circulares, tipografía de números grandes y etiquetas legibles.
- Ajuste de gráficas de **Chart.js** con colores corporativos armónicos (Guinda, Dorado, Esmeralda, Azul Slate) y tooltips modernos.
- Sección de **Accesos Rápidos** con botones tipo tarjeta interactiva (`quick-action-btn`).

---

### 3. Mejora de Vistas de Módulos y Tablas (`modules/*/*.php`)

#### [MODIFY] [modules/nacimientos/index.php](file:///C:/xampp/htdocs/DRC/modules/nacimientos/index.php) y módulos de Actos
- Tarjeta principal elevada (`shadow-sm`) con cabecera limpia y acciones destacadas.
- Botones de acción estandarizados: Primario en Guinda (`+ Registrar`), Secundario en Outline Esmeralda (`Exportar a Excel`).
- Badges semánticos para estados vitales y tipos de actos.

---

### 4. Corrección de Formularios y Selectores (`modules/*/create.php`)

#### [MODIFY] [modules/peticion_rapida/create.php](file:///C:/xampp/htdocs/DRC/modules/peticion_rapida/create.php) y vistas de captura
- Reemplazo de cajas con bordes oscuros por formularios modernos con tarjeta blanca y sombra sutil.
- Ajuste de estilos de **TomSelect** en `assets/css/style.css` para una búsqueda de ciudadanos fluida y bien alineada.

---

## Verification Plan

### Manual Verification
1. Abrir `http://localhost/DRC/public/index.php` y comprobar:
   - Tarjetas KPI estilizadas con colores armónicos y números claros.
   - Gráficas de Chart.js con paleta corporativa y animaciones fluidas.
   - Barra superior y sidebar responsivo con toggle suave.
2. Abrir `http://localhost/DRC/modules/nacimientos/index.php` y verificar la tabla de datos y botones de acción.
3. Abrir `http://localhost/DRC/modules/peticion_rapida/create.php` y validar que el formulario y el selector TomSelect luzcan limpios y sin solapamientos.

### Automated Tests
- Ejecutar la suite de Smoke Tests y PHPUnit para asegurar que ningún cambio de estilo rompa el backend:
  ```bash
  C:\xampp\php\php.exe scripts/run_smoke_tests.php
  C:\xampp\php\php.exe vendor/bin/phpunit
  ```
