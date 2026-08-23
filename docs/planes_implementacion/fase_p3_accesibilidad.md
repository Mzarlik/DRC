# Plan de Implementación — Fase P3: Accesibilidad Universal (WCAG 2.1 AA)

Este plan aborda las mejoras de accesibilidad, navegación inclusiva y cumplimiento de estándares **WCAG 2.1 Nivel AA** en todo el sistema ERP DRC.

---

## 1. Alcance de los Cambios

### A. Estilos Globales de Navegación por Teclado y Contraste (`assets/css/style.css`)
- **Foco Visible de Alto Contraste (`:focus-visible`):**
  - Implementar anillo de foco institucional de 2px con desplazamiento (`outline: 2px solid var(--secondary-color); outline-offset: 2px;`) en botones, enlaces, selectores y campos de texto cuando se navega con teclado (Tab).
  - Eliminar los outlines genéricos o invisibles asegurando una indicación visual clara del elemento activo.
- **Enlace de Salto al Contenido Principal (`.skip-link`):**
  - Definir la clase `.skip-link` accesible (oculta visualmente hasta recibir foco por teclado) para permitir a usuarios de lectores de pantalla saltar la barra lateral directamente al contenido principal (`#content`).
- **Preferencia de Movimiento Reducido (`@media (prefers-reduced-motion: reduce)`):**
  - Desactivar o suavizar transiciones de sidebar, efectos de elevación y animaciones para usuarios con sensibilidad vestibular o mareo por movimiento.
- **Ajuste de Contraste y Placeholders:**
  - Optimizar el color de los marcadores de posición (`::placeholder`) a `#64748B` en modo claro y `#94A3B8` en modo oscuro para cumplir con el ratio de contraste mínimo 4.5:1.
  - Asegurar contraste de badges y estados en tablas.

### B. Inyección y Gestión de Accesibilidad en JavaScript (`assets/js/global.js`)
- **Skip Link Dinámico:**
  - Inserción automática del enlace accesible `Saltar al contenido principal` al inicio del `<body>` en todas las páginas.
- **Gestión de Foco en Modales (Focus Trap & Restoration):**
  - Al abrir cualquier modal de Bootstrap, enfocar automáticamente el primer campo o botón interactivo.
  - Al cerrarse el modal, restaurar el foco al botón disparador original.
- **Atributos ARIA en Notificaciones y Contadores:**
  - Asignar `aria-live="polite"` y `role="status"` al menú de notificaciones y toasts dinámicos.
  - Añadir descripción textual accesible a badges numéricos (`aria-label="X notificaciones pendientes"`).

### C. Semántica y Atributos ARIA en Vistas Principales
- Revisar y agregar `aria-label`, `aria-controls`, y `role` en componentes clave de:
  - `public/index.php` (Dashboard, KPIs y gráficas con descripciones alternativas `aria-label`).
  - `public/login.php` (Mensajes de error y ayudas de formulario).
  - Menús desplegables y toggles en los módulos.

---

## 2. Plan de Verificación

### Pruebas Automatizadas
- `C:\xampp\php\php.exe scratch/lint_all.php`: Verificar 0 errores de sintaxis en todos los archivos PHP.
- `node -c assets/js/global.js`: Validar sintaxis JavaScript de las utilidades de accesibilidad.
- `C:\xampp\php\php.exe vendor/bin/phpunit`: Ejecutar pruebas unitarias del backend.

### Verificación Manual de Accesibilidad
- Probar navegación completa usando únicamente la tecla **Tab** y **Shift+Tab** comprobando el anillo de foco dorado visible.
- Probar el **Skip Link** al presionar Tab como primera acción en la página.
- Verificar el comportamiento de modales (foco automático y cierre con **Escape**).
- Validar contraste tipográfico en tema claro y oscuro con herramientas de inspección.
