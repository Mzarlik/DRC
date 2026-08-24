# Plan de Implementación — Fase 2: UI, Assets Locales (Desacople de CDN), TomSelect y Reactividad CSP-Friendly

Este plan detalla los pasos para ejecutar la **Fase 2** del primer despliegue del ERP DRC, permitiendo la operación 100% offline del frontend, la estandarización de TomSelect (Vanilla JS) y la integración de reactividad declarativa con Alpine.js CSP-Friendly.

---

## User Review Required

> [!IMPORTANT]
> **Descarga y Alojamiento Local de Assets (`assets/vendor/`):**
> Se descargarán y alojarán localmente todas las librerías frontend (Bootstrap 5.3.2, FontAwesome 6.4.2 con webfonts, DataTables 1.13.7, TomSelect 2.3.1, SweetAlert2 11.10.0, Chart.js 4.4.1 y Alpine.js CSP-friendly).
> Esto garantizará que el sistema funcione en redes gubernamentales aisladas o sin conexión a internet.

> [!NOTE]
> **Sustitución de Rutas de CDN a Rutas Locales:**
> Se actualizarán las referencias en las plantillas de `public/` y `modules/*/` para apuntar a `assets/vendor/`.

---

## Proposed Changes

### 1. Descarga y Empaquetado de Assets Locales

#### [NEW] [scripts/download_assets.php](file:///C:/xampp/htdocs/DRC/scripts/download_assets.php)
- Script multiplataforma en PHP CLI para descargar y estructurar automáticamente todos los paquetes en `assets/vendor/`:
  - `assets/vendor/bootstrap/` (css y js bundle)
  - `assets/vendor/fontawesome/` (css y webfonts .woff2 / .ttf)
  - `assets/vendor/datatables/` (css y js para Bootstrap 5)
  - `assets/vendor/tom-select/` (css y js completo)
  - `assets/vendor/sweetalert2/` (css y js all)
  - `assets/vendor/chartjs/` (chart.umd.min.js)
  - `assets/vendor/alpine/` (alpine-csp.min.js)

---

### 2. Componentes Reactivos y Helpers de Interfaz

#### [NEW] [assets/js/components-alpine.js](file:///C:/xampp/htdocs/DRC/assets/js/components-alpine.js)
- Definición de componentes reactivos compatibles con CSP mediante `Alpine.data()`:
  - `formMatrimonios`: Lógica condicional de régimen patrimonial y capitulaciones.
  - `formInexistencias`: Cálculo dinámico de costos y fechas estimadas de entrega.
  - `formVentanillaTurnos`: Gestión de ventanilla y selección de trámite rápido.

#### [MODIFY] [assets/js/global.js](file:///C:/xampp/htdocs/DRC/assets/js/global.js)
- Incorporar la función global `initCiudadanoSelect()` para instanciación estandarizada de TomSelect en cualquier modal o vista.

---

### 3. Actualización de Plantillas Maestras y Módulos

#### [MODIFY] [public/index.php](file:///C:/xampp/htdocs/DRC/public/index.php)
- Reemplazar CDNs de Bootstrap, FontAwesome, Chart.js y SweetAlert2 por rutas relativas locales hacia `../assets/vendor/`.

#### [MODIFY] [public/usuarios.php](file:///C:/xampp/htdocs/DRC/public/usuarios.php)
- Reemplazar CDNs por rutas locales.

#### [MODIFY] [public/login.php](file:///C:/xampp/htdocs/DRC/public/login.php)
- Reemplazar CDNs por rutas locales.

#### [MODIFY] [modules/nacimientos/index.php](file:///C:/xampp/htdocs/DRC/modules/nacimientos/index.php) y demás módulos
- Actualizar encabezados y pies de página a `../../assets/vendor/`.

---

## Verification Plan

### Automated Tests
- Ejecutar el script de descarga y validación de integridad:
  ```bash
  C:\xampp\php\php.exe scripts/download_assets.php
  ```
- Ejecutar suite de pruebas unitarias:
  ```bash
  C:\xampp\php\php.exe vendor/bin/phpunit tests/Unit/EncryptionTest.php tests/Unit/UtilsTest.php
  ```

### Manual Verification
1. Abrir `public/index.php` y `public/login.php` en el navegador con la red desconectada o inspeccionando la pestaña Network de DevTools:
   - Validar que **cero peticiones** salgan a dominios externos (`jsdelivr.net`, `cdnjs`, etc.).
   - Validar que los iconos de FontAwesome se visualicen correctamente (fuentes locales `.woff2`).
2. Abrir un modal de registro de ciudadanos o matrimonios:
   - Probar que `TomSelect` realice la búsqueda y renderizado de resultados.
   - Probar la reactividad de `Alpine.js` al alternar el régimen matrimonial.
