# Módulo de Auditoría y Registro de Errores

Este plan detalla la creación de un nuevo módulo exclusivo para administradores, que permitirá visualizar un registro de las acciones (auditoría) realizadas por los usuarios en el sistema, así como un registro de los errores del sistema.

## Decisiones Aprobadas
1. **Auditoría a nivel de código PHP**: En lugar de Triggers de MySQL, se usará código PHP para tener descripciones más legibles por humanos.
2. **Registro Automático de Errores**: Se implementará un manejador global de errores en PHP para capturar excepciones y errores fatales y enviarlos automáticamente a la tabla `error_logs`, para visualizarlos en detalle en el módulo.

## Cambios Propuestos

### 1. Base de Datos
Creación de dos nuevas tablas en la base de datos `drc_erp`:
- `auditoria_logs`: Almacenará `id`, `usuario_id`, `modulo`, `accion` (Ej. CREAR, EDITAR, ELIMINAR), `detalles`, `ip_address`, `fecha_hora`.
- `error_logs`: Almacenará `id`, `usuario_id` (si aplica), `mensaje`, `archivo`, `linea`, `stack_trace`, `url`, `fecha_hora`.

### 2. Core (Clases base)
- Nueva clase `core/Auditoria.php` para centralizar métodos `logAccion` y `logError`.
- Actualizar el flujo principal (`Database.php` u otro archivo global) para atrapar errores y excepciones.

### 3. Vistas de Administración
- `public/auditoria.php`: Interfaz gráfica con dos pestañas (Acciones de Usuarios y Registro de Errores).
- Endpoints AJAX (`api/auditoria_data.php`, `api/errores_data.php`).

### 4. Actualización de Navegación (Sidebar)
Actualización de los menús laterales para tener el submenú de "Administración".

* **Corrección de Navegación (Fase Post-Implementación)**:
  Se identificó que al entrar al módulo de Usuarios (`public/usuarios.php`) o Perfil de usuario (`public/perfil.php`), la barra de navegación lateral mostraba la versión anterior de "Administración" (sin el submenú de Auditoría y Errores), provocando que desapareciera la pestaña. Se actualizaron ambos archivos para incluir el componente colapsable `#adminSubmenu` de manera persistente.
