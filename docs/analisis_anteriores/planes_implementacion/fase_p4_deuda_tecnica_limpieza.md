# Plan de Implementación — Fase P4: Deuda Técnica y Limpieza de Código

Este plan aborda la resolución de deuda técnica en la capa visual y de interfaz, saneamiento de comentarios huérfanos, extracción de CSS embebido a hojas de estilo centralizadas y robustecimiento de la arquitectura front-end.

---

## 1. Alcance de los Cambios

### A. Consolidación de Estilos Embebidos hacia `assets/css/style.css`
- **Auditoría (`public/auditoria.php`):**
  - Mover las reglas de visualización de excepciones y trazas (`.stack-trace`, `.stack-trace-header`, `.stack-trace-body`) a `style.css` con soporte completo para modo claro y oscuro.
- **Petición Rápida (`modules/peticion_rapida/create.php`):**
  - Mover las reglas de retroalimentación de validación de CURP (`.is-valid-curp`, `.is-invalid-curp`) a `style.css`.
- **Limpieza de Bloques `<style>`:**
  - Eliminar los bloques `<style>` locales en los encabezados `<head>` de los archivos afectados, manteniendo las páginas 100% dependientes del design system.

### B. Limpieza de Comentarios Huérfanos y Bloques Duplicados
- Remover comentarios repetidos `<!-- Sidebar -->` redundantes acumulados en:
  - `modules/nacimientos/create.php`
  - `modules/ciudadanos/index.php` y `create.php`
  - `modules/defunciones/create.php`
  - `modules/foraneas/create.php`
  - `modules/inexistencias/create.php`
  - `public/perfil.php`
  - `public/usuarios.php`
- Limpiar stubs de comentarios vacíos de JavaScript (`// Cargar Notificaciones`, `// Sidebar Collapse`).

### C. Robustecimiento del Enrutamiento Front-end (`assets/js/global.js`)
- Estandarizar la detección de rutas relativas para llamadas AJAX (`api/notifications.php`, `api/stats.php`, etc.) asegurando resiliencia ante cualquier estructura de directorios o subcarpetas.

---

## 2. Plan de Verificación

### Pruebas Automatizadas
- `C:\xampp\php\php.exe scratch/lint_all.php`: Verificar 0 errores de sintaxis en todos los archivos PHP.
- `node -c assets/js/global.js`: Validar sintaxis JavaScript de utilidades actualizadas.
- `C:\xampp\php\php.exe vendor/bin/phpunit`: Ejecutar pruebas unitarias de backend.

### Verificación Manual
- Comprobar que el modal de trazas de auditoría (`auditoria.php`) se visualiza correctamente en tema claro y oscuro.
- Verificar que la validación visual de CURP en Petición Rápida sigue coloreando los bordes en verde/rojo.
- Inspeccionar que el código fuente HTML esté limpio de comentarios repetitivos.
