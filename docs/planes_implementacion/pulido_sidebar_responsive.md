# Plan de Implementación: Pulido Integral del Sidebar (Modo Compacto & Responsive)

Este plan detalla las mejoras estéticas, de rendimiento, accesibilidad y experiencia de usuario (UI/UX) para la barra lateral (Sidebar) del ERP DRC, tanto en su versión móvil/tablet (*Offcanvas Drawer*) como en su versión de escritorio (*Icon-Rail Compact Sidebar*).

---

## 1. Alcance y Objetivos

1. **Modo Compacto Real en Escritorio (>= 992px):**
   - Transición fluida de 260px a 70px al hacer clic en `#sidebarCollapse`.
   - Ocultamiento de textos (`.sidebar-text`) y flechas de submenú (`.dropdown-toggle::after`), centrando los iconos.
   - Activación de tooltips flotantes (*Bootstrap Tooltip API*) a la derecha de cada icono para mantener la claridad de navegación.
   - Si el usuario hace clic en un menú con submenú mientras está en modo compacto, la barra se expande suavemente.
   - **Persistencia en `localStorage`:** Recordar el estado compacto al navegar entre módulos.
   - Prevención de FOUC (*Flash of Uncollapsed Layout*) al cargar la página.

2. **Fijación y Scroll Propio (*Sticky Sidebar*):**
   - `position: sticky; top: 0; height: 100vh; overflow-y: auto;` con *slim scrollbar* elegante.
   - Asegura que los más de 13 módulos del menú sean siempre accesibles sin importar el scroll del contenido principal ni la resolución de la pantalla.

3. **Optimización Responsive en Móvil y Tablet (< 992px):**
   - **Cierre automático al navegar:** Al tocar un enlace final (`a:not(.dropdown-toggle)`), el drawer móvil se cierra suavemente.
   - **Gesto táctil (*Swipe Left to Dismiss*):** Soporte nativo para cerrar el menú deslizando el dedo hacia la izquierda.
   - **Botón de Cierre Ergonómico (`#sidebarCloseMobile`):** Área táctil mínima de 44×44 px adaptada a dedos en pantallas táctiles.

4. **Acordeones y Animaciones de Flechas:**
   - Animación de rotación de 180° en la flecha desplegable (`.dropdown-toggle::after`) al abrir/cerrar submenús.
   - Auto-expansión consistente del módulo en el que se encuentra el usuario.

5. **Limpieza de Scripts Duplicados y Ajuste de DataTables:**
   - Eliminar los fragmentos `<script>` inline heredados con breakpoints desfasados (`768px`) que competían con `global.js`.
   - Disparar `table.columns.adjust()` al colapsar/expandir el sidebar para redimensionar dinámicamente las tablas de datos.

---

## 2. Archivos a Modificar

### Estilos y Comportamiento Global
- [assets/css/style.css](file:///c:/xampp/htdocs/DRC/assets/css/style.css): Reglas para `#sidebar.compact`, *slim scrollbar*, rotación de chevrons, *touch target* de 44px y estados *sticky*.
- [assets/js/global.js](file:///c:/xampp/htdocs/DRC/assets/js/global.js): Lógica unificada de persistencia, tooltips flotantes, gestos *swipe*, auto-cierre en móvil y redimensionamiento de DataTables.

### Limpieza de Scripts Inline Duplicados
- [modules/matrimonios/index.php](file:///c:/xampp/htdocs/DRC/modules/matrimonios/index.php) y `create.php`
- [modules/divorcios/index.php](file:///c:/xampp/htdocs/DRC/modules/divorcios/index.php) y `create.php`
- [modules/inscripciones/index.php](file:///c:/xampp/htdocs/DRC/modules/inscripciones/index.php) y `create.php`
- [modules/reconocimientos/index.php](file:///c:/xampp/htdocs/DRC/modules/reconocimientos/index.php) y `create.php`
- [modules/curp/index.php](file:///c:/xampp/htdocs/DRC/modules/curp/index.php) y `create.php`
- [public/catalogos.php](file:///c:/xampp/htdocs/DRC/public/catalogos.php)

---

## 3. Plan de Verificación

1. **Prueba de Modo Compacto en Escritorio:**
   - Clic en el botón hamburguesa `#sidebarCollapse`: el sidebar se reduce a 70px, los textos desaparecen y los iconos quedan centrados.
   - Hover sobre los iconos: aparecen tooltips de Bootstrap con el nombre del módulo.
   - Clic en un elemento con submenú: se expande automáticamente a 260px.
   - Recargar la página o cambiar de módulo: la barra recuerda su estado colapsado sin parpadeos.
   - Tablas DataTables: las columnas se auto-ajustan al nuevo ancho disponible sin desbordar.

2. **Prueba Responsive en Móvil/Tablet (<992px):**
   - Abrir el menú lateral con `#sidebarCollapse`: se abre el drawer offcanvas.
   - Tocar el botón `✕`: se cierra con área táctil cómoda.
   - Tocar un enlace de módulo (ej. *"Ciudadanos"*): el drawer se cierra inmediatamente.
   - Deslizar el dedo hacia la izquierda (*Swipe*): el drawer se cierra.

3. **Pruebas Automatizadas:**
   - Ejecutar la suite unitaria de PHPUnit (`vendor/bin/phpunit`) para validar que ninguna regla de backend se haya alterado.
