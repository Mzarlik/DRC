# Plan de Implementación: Esqueleto Responsive (Offcanvas) y Tema Corporativo Guinda

Este plan detalla los cambios para lograr que la interfaz del Registro Civil (ERP DRC) sea 100% responsiva (Fase 1: Layout Base) y cuente con una paleta de colores corporativa basada en tonos guinda (burgundy/maroon).

---

## User Review Required

> [!IMPORTANT]
> Se utilizará la funcionalidad de **Responsive Offcanvas (`offcanvas-lg`)** de Bootstrap 5. Esto significa que:
> * En pantallas grandes (>= 992px), el menú lateral (sidebar) se mantendrá estático al lado izquierdo en el flujo normal, permitiendo además el colapso compacto tradicional.
> * En pantallas medianas y móviles (< 992px), el menú lateral se ocultará automáticamente a la izquierda y se convertirá en un Offcanvas nativo (deslizable) con su propio fondo oscuro translúcido.
> * Al hacer clic fuera del Offcanvas en móvil, este se cerrará automáticamente de forma nativa.
>
> Para los colores, sustituiremos la paleta azul/verde actual por una paleta premium en tonos **Guinda Corporativo** y **Vino Tinto**.

---

## Proposed Changes

### Estilos y Diseño (CSS)

#### [MODIFY] [style.css](file:///c:/xampp/htdocs/DRC/assets/css/style.css)
* Cambiar las variables CSS globales para definir la paleta de tonos guinda en modo claro y oscuro:
  * `--primary-color`: `#6b1d2f` (Guinda profundo).
  * `--secondary-color`: `#8c1d33` (Guinda medio / acentos).
  * `--sidebar-bg`: `#4a101d` (Vino/guinda oscuro para el menú).
  * `--sidebar-header-bg`: `#2d0a11` (Vino/guinda aún más oscuro).
  * En modo oscuro, se usarán equivalentes oscurecidos (`#1e050b` y `#280a11`) para garantizar el contraste y la estética.
* Ajustar las reglas CSS de `.wrapper` y `#sidebar` para acoplarse con la clase `.offcanvas-lg` de Bootstrap 5.
* Definir un `@media (min-width: 992px)` para mantener las dimensiones fijas del sidebar en escritorio, y un `@media (max-width: 991.98px)` para forzar que el sidebar tome 100% de la altura de pantalla y no colisione con el estilo Offcanvas de Bootstrap.
* Sobrescribir las clases principales de Bootstrap como `.btn-primary` y `.btn-outline-primary` para que las alertas y botones usen los nuevos acentos guinda de forma generalizada en todo el sistema.

---

### Central JS Behavior (Comportamiento Global)

#### [MODIFY] [global.js](file:///c:/xampp/htdocs/DRC/assets/js/global.js)
* Modificar la lógica del botón `#sidebarCollapse`:
  * Si la pantalla es `>= 992px`, alternar la clase `.compact` para colapsar/expandir el sidebar lateral (escritorio).
  * Si la pantalla es `< 992px`, obtener la instancia de Bootstrap Offcanvas de `#sidebar` y hacer un `toggle()` para mostrarlo u ocultarlo (móviles/tabletas).
* Modificar el comportamiento de `#sidebarCloseMobile` para llamar a la función `hide()` del Offcanvas en pantallas pequeñas.
* Añadir un escuchador de cambio de tamaño (`resize`) para ocultar automáticamente la instancia de Offcanvas si la ventana se amplía más allá de 992px, limpiando fondos o backdrops colgados.

---

### Vistas HTML / PHP

#### [MODIFY] Todos los archivos PHP en `public/` y `modules/*/index.php` / `create.php`
* Inyectar la clase `.offcanvas-lg` y `.offcanvas-start` en la etiqueta del sidebar:
  ```html
  <!-- DE: -->
  <nav id="sidebar">
  <!-- A: -->
  <nav id="sidebar" class="offcanvas-lg offcanvas-start" tabindex="-1">
  ```
  *(Se utilizará un script PHP de utilidad para automatizar este reemplazo de forma masiva y segura en los 27 archivos del sistema).*

---

## Plan de Verificación

### Pruebas Funcionales:
1. **Comportamiento en Escritorio (>= 992px)**:
   * El sidebar debe cargarse visible al lado izquierdo en tonos guinda.
   * Al presionar el botón de hamburguesa, el menú debe colapsarse en su versión compacta (solo iconos) y el contenido expandirse.
2. **Comportamiento en Celular (< 992px)**:
   * El sidebar debe estar oculto al cargar la página.
   * Al presionar el botón de hamburguesa en la barra superior, el sidebar debe deslizarse suavemente desde la izquierda sobre un fondo translúcido (backdrop).
   * Al presionar la "X" de cierre o tocar fuera del menú, el sidebar debe deslizarse de vuelta y ocultarse.
3. **Cambio de Colores**:
   * Verificar que todos los botones principales (`btn-primary`), los enlaces activos y el menú lateral adopten la nueva identidad guinda/vino corporativo.
   * Probar el cambio a Modo Oscuro y validar que el fondo y los acentos se adapten manteniendo la misma temática.
