# Diseño UI/UX (Fase 14)

Este plan detalla el diseño e implementación de las mejoras de UI/UX en el ERP DRC para optimizar la velocidad y comodidad del operador. Se implementará navegación por teclado, esqueletos de carga (Skeleton Screens) y un modo oscuro persistente para la salud ocupacional de los capturistas.

## User Review Required

> [!IMPORTANT]
> **Modificaciones en Archivos de Vista**
> Dado que el ERP no utiliza un motor de plantillas o un layout PHP compartido para todas sus páginas de vista (cada una es un archivo HTML/PHP independiente), se automatizará la inyección del script global (`global.js`) y del script de prevención de FOUC mediante un script PHP de utilidad en el servidor. Esto asegura coherencia completa sin alterar manualmente 27 archivos distintos.
>
> **Comportamiento del Teclado**
> La tecla `Enter` moverá el foco al siguiente campo visible y editable del formulario. Se exceptúan los campos de tipo `textarea` (para permitir saltos de línea) y los botones de acción/submit. El atajo `Ctrl + Enter` (o `Cmd + Enter` en macOS) enviará el formulario de manera segura desde cualquier input o control activo.

---

## Proposed Changes

### 1. Estilos Globales y Modo Oscuro
Modificar la hoja de estilos principal para soportar variables CSS y animaciones de carga.

#### [MODIFY] [style.css](file:///c:/xampp/htdocs/DRC/assets/css/style.css)
- Declarar variables de color en `:root` (esquema claro) y sobreescribirlas bajo la clase `.dark-mode` (esquema oscuro):
  - `--bg-light`: Color de fondo de la aplicación.
  - `--text-color`: Color principal de los textos.
  - `--card-bg`: Fondo de las tarjetas.
  - `--navbar-bg`: Fondo de la barra de navegación.
  - `--sidebar-bg` y `--sidebar-text`: Fondo y textos de la barra lateral.
  - `--border-color`: Bordes de tablas, cards e inputs.
  - `--input-bg`, `--input-border` y `--input-text`: Estilos para campos de formulario.
- Reemplazar colores estáticos hexadecimales en las clases existentes por las nuevas variables de color.
- Agregar soporte y estilos CSS adaptados a modo oscuro para librerías de terceros:
  - **Tom Select**: Desplegables, opciones y cajas de búsqueda adaptadas a modo oscuro.
  - **SweetAlert2**: Modales y textos adaptados.
  - **DataTables**: Controles de paginación, longitudes, filtros y filas alternadas adaptadas.
- Agregar las clases para **Skeleton Loaders** con animación shimmer (brillo en movimiento):
  - `.skeleton`: Clase base con gradiente lineal y animación `@keyframes loading-shimmer`.
  - `.skeleton-text`: Bloque simulando un texto.
  - `.skeleton-title`: Bloque simulando un encabezado.
  - `.skeleton-rect`: Bloque simulando un elemento gráfico o tarjeta.

---

### 2. Script de Control Global
Crear el archivo encargado de controlar el comportamiento interactivo.

#### [NEW] [global.js](file:///c:/xampp/htdocs/DRC/assets/js/global.js)
- **Modo Oscuro**:
  - Inyectar dinámicamente un botón/toggle de modo oscuro (icono de Luna/Sol) en la barra de navegación (`.navbar .ms-auto`).
  - Escuchar clics en el toggle para alternar la clase `.dark-mode` en `<body>` e inyectar/quitar en `<html>` si es necesario.
  - Almacenar la preferencia del usuario en `localStorage.setItem('theme', 'dark' | 'light')`.
  - Aplicar el tema correspondiente al iniciar, sincronizando el estado visual del icono.
- **Navegación por Teclado**:
  - Escuchar eventos `keydown` globales.
  - Al presionar `Enter` en un input o select visible que no sea `textarea`, botón, o control Tom Select nativo, prevenir el submit por defecto y realizar `focus()` sobre el siguiente elemento editable.
  - Al presionar `Ctrl + Enter` (o `Cmd + Enter` en macOS), disparar `.submit()` en el formulario activo para guardar el registro.
- **Sincronización del Sidebar**:
  - Mover la lógica de colapso y expansión del sidebar (que estaba repetida en múltiples archivos) a este archivo global para evitar redundancias de JavaScript.

---

### 3. Vistas del ERP y Esqueletos de Carga
Actualizar las vistas para incorporar los nuevos comportamientos.

#### [MODIFY] [public/index.php](file:///c:/xampp/htdocs/DRC/public/index.php) (Dashboard)
- Reemplazar los marcadores de texto `...` en las tarjetas de estadísticas con esqueletos visuales (`<span class="skeleton skeleton-text" style="width:60px; height:24px;"></span>`).
- Envolver las áreas de las gráficas (`#diarioChart`, `#recaudacionChart`, `#cargaChart`) con contenedores de esqueleto que se ocultan en la llamada `success` de AJAX, haciendo aparecer la gráfica con una transición suave.

#### [MODIFY] Archivos de Vista (*.php en `public/` y `modules/`)
- Inyectar en el `<head>` de cada página un script ultra-pequeño inline para prevenir FOUC (destello de luz blanca al cargar páginas en modo oscuro):
  ```html
  <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark-mode');}</script>
  ```
- Inyectar en la sección de scripts el archivo `global.js` (con la ruta relativa adecuada).
- En las tablas de listados (archivos `index.php` en `modules/`), añadir filas estáticas con estructura `.skeleton` dentro de `<tbody>` para que simulen datos cargando antes de que la inicialización de DataTables los limpie y dibuje los registros reales.

---

## Verification Plan

### Automated Tests
- Ejecutar suite de pruebas unitarias para validar que no existan regresiones de backend:
  `c:\xampp\php\php.exe vendor/bin/phpunit`
- Correr pruebas sintácticas de PHP en los archivos modificados:
  `php -l [archivo]`

### Manual Verification
1. **Modo Oscuro**:
   - Abrir el ERP en el navegador y verificar que el toggle aparezca en el navbar.
   - Alternar a modo oscuro y comprobar que todos los componentes (sidebar, navbar, inputs, dropdowns de Tom Select, modales SweetAlert2 y tablas) cambien a colores oscuros de alto contraste.
   - Recargar la página y verificar que el modo oscuro se mantenga activo (persistencia en `localStorage`).
2. **Navegación por Teclado**:
   - Acceder al registro de ciudadano (`modules/ciudadanos/create.php`).
   - Llenar los campos y presionar `Enter` para avanzar al siguiente input.
   - Presionar `Ctrl + Enter` y validar que se guarde el formulario de manera automática sin tocar el ratón.
3. **Skeleton Screens**:
   - Cargar el dashboard y constatar que aparezcan los bloques grises shimmer en lugar de los textos de carga e iconos de carga mientras la API de estadísticas responde.
