# Plan de Implementación — Fase P2: Mejoras de UX, Feedback de Carga y Refinamiento de Modo Oscuro

Este plan aborda las mejoras de experiencia de usuario (UX), reactividad visual y corrección de inconsistencias en Modo Oscuro identificadas en la auditoría P2 del ERP DRC.

---

## 1. Alcance de los Cambios

### A. Adaptación de la Pantalla de Login (`public/login.php`)
- Cambiar `height: 100vh` por `min-height: 100vh; padding: 20px;` para prevenir desbordamientos y recortes cuando se despliega el teclado virtual en pantallas móviles.
- Adaptar `.login-card` para que herede las variables de superficie en modo oscuro (`var(--bg-surface)` y `var(--text-main)`) evitando el destello blanco sobre fondo oscuro.
- Extraer los estilos embebidos a clases en `style.css` manteniendo compatibilidad.

### B. Corrección de Parches Blancos en Modo Oscuro (`assets/css/style.css`)
- Añadir reglas para que en `body.dark-mode`:
  - Los encabezados de tarjetas con `.card-header.bg-white` adopten el color de superficie oscuro (`var(--bg-surface)` / `#1E293B`) y texto claro.
  - El encabezado del centro de notificaciones (`.notif-dropdown li.bg-light`) use el fondo de superficie oscuro.
  - Los badges `.badge.bg-light.text-dark` se adapten a fondo gris tenue (`rgba(255,255,255,0.1)`) con texto legible.
  - Definir las clases de utilidad `.text-emerald` (`#0F766E`) y `.text-slate` (`#64748B`) en el design system.

### C. Feedback de Carga Universal en Formularios (`assets/js/global.js`)
- Añadir un interceptor global para formularios AJAX que:
  - Al enviar (`submit`), deshabilite automáticamente el botón primario (`button[type="submit"]`), preserve su texto original en `data-original-text` y muestre un spinner de carga (`<i class="fa-solid fa-circle-notch fa-spin me-1"></i> Procesando...`).
  - Al recibir respuesta (éxito o error), restaure automáticamente el botón y su estado activo.
  - Previene clics duplicados y envíos dobles en conexiones lentas.

### D. Refinamiento de Dashboard y Gráficas (`public/index.php`)
- Sustituir los botones de "Accesos Rápidos" por el componente enriquecido `.quick-action-btn` con acento dorado institucional y animaciones hover.
- Adaptar las opciones de **Chart.js** para detectar el tema activo (`body.classList.contains('dark-mode')`) y aplicar:
  - Bordes de dona con el color de fondo oscuro (`#1E293B`) en lugar de `#fff`.
  - Color de rejilla y fuentes de los ejes acordes al tema activo.

---

## 2. Plan de Verificación

### Pruebas Automatizadas
- `C:\xampp\php\php.exe scratch/lint_all.php`: Verificar 0 errores de sintaxis en todos los archivos PHP.
- `node -c assets/js/global.js`: Validar sintaxis JavaScript del interceptor de carga.
- `C:\xampp\php\php.exe vendor/bin/phpunit`: Ejecutar pruebas unitarias del backend.

### Verificación Visual
- Probar el Login en viewport móvil y alternar entre tema claro y oscuro.
- Verificar que los formularios de captura muestran el spinner al guardar y se deshabilitan para evitar doble clic.
- Comprobar que en modo oscuro el Dashboard, las gráficas y los encabezados no presentan parches blancos.
