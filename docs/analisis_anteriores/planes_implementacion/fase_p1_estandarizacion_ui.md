# Plan de Implementación — Fase P1: Estandarización de Componentes e Inconsistencias UI

Este plan detalla las modificaciones para resolver todas las inconsistencias visuales y de experiencia de usuario identificadas en la auditoría P1 del ERP DRC.

---

## 1. Alcance de los Cambios

### A. Estandarización de Botones de Exportación a Excel
- Unificar todos los botones de exportar a Excel usando la clase `.btn-excel` con icono `<i class="fa-solid fa-file-excel me-1"></i>` y texto claro.
- Retirar estilos inline con fondos verdes arbitrarios (`#27ae60`, `var(--accent-color)`).
- **Archivos:**
  - `modules/ciudadanos/index.php`
  - `modules/defunciones/index.php`
  - `modules/peticion_rapida/index.php`
  - `modules/inexistencias/index.php`
  - `public/usuarios.php`
  - `public/auditoria.php` (botones de acciones y de errores)
  - `modules/peticiones/index.php` (agregar botón de exportación para coordinadores/supervisores)

### B. Estandarización del Botón Toggle de Sidebar en Topbar
- Reemplazar botones planos o con estilos inline por `.btn-sidebar-toggle` con icono `<i class="fas fa-bars"></i>` y atributo accesible `aria-label="Toggle Sidebar"`.
- **Archivos:**
  - `public/perfil.php`
  - `public/usuarios.php`
  - `public/auditoria.php`
  - `public/catalogos.php`
  - `modules/turnos/index.php`

### C. Integración de la Campana de Notificaciones en Todos los Navbars
- Incorporar el componente `#notificacionesMenu` completo (icono `fa-bell`, badge `#notifBadge`, dropdown `#notifList` y `#notifEmpty`) en los módulos donde fue omitido:
  - `modules/peticion_rapida/index.php`
  - `modules/peticion_rapida/create.php`
  - `modules/peticiones/index.php`
  - `modules/peticiones/create.php`

### D. Estandarización de Encabezados de Página (Header Pattern)
- Aplicar el patrón visual institucional enriquecido a todos los encabezados:
  ```html
  <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
          <h2 class="fw-bold mb-1"><i class="fa-solid [icono] text-primary me-2"></i> [Título]</h2>
          <p class="text-muted small mb-0">[Subtítulo explicativo / contexto]</p>
      </div>
      [Botones de acción]
  </div>
  ```
- Normalizar encabezados en:
  - `modules/ciudadanos/index.php`
  - `modules/nacimientos/create.php`
  - `modules/defunciones/create.php`
  - `modules/foraneas/create.php`
  - `modules/inexistencias/create.php` (agregando además el botón "Volver al listado" faltante)
  - `public/catalogos.php` (retirando `text-gray-800` no soportado)
  - `modules/reportes/index.php` (agregando icono institucional `fa-file-lines` en el título)

### E. Limpieza de Estilos Inline Redundantes en Botones de Guardado / Acción
- Retirar `style="background: var(--secondary-color); border: none;"` y `style="background: var(--primary-color); border: none;"` en botones `.btn-primary` (el Design System en `style.css` ya aplica la paleta institucional automáticamente).

### F. Paridad de DataTables en `public/usuarios.php`
- Inicializar la tabla `#usuariosTable` con DataTables Responsive en español, habilitando búsqueda en tiempo real, ordenamiento por columnas, paginación y modo de tarjetas móviles automático.

---

## 2. Plan de Verificación

### Pruebas Automatizadas
- `C:\xampp\php\php.exe scratch/lint_all.php`: Verificar 0 errores de sintaxis en todos los archivos PHP.
- `C:\xampp\php\php.exe vendor/bin/phpunit`: Ejecutar pruebas unitarias de utilidades, fechas y folios.

### Verificación Visual
- Comprobar que los botones de exportar Excel lucen consistentes con el verde esmeralda institucional (`#0F766E`) en todos los módulos.
- Verificar que el toggle de sidebar y la campana de notificaciones funcionan y se ven idénticos en todas las pantallas.
- Validar la tabla de usuarios en desktop y en vista móvil con el transformador de tarjetas.
