# Fase A — Extracción de Layout Compartido + Turnos en Sidebar + Consolidación Core

## Contexto

El layout completo (doctype, `<head>`, sidebar con 150+ líneas, navbar, scripts) está **copiado íntegramente en 34 archivos PHP**. Cada cambio de navegación o CSS requiere editar 34 archivos. Esto ya causó que el módulo de Turnos (#2) no aparezca en el sidebar a pesar de estar implementado.

Este plan extrae el layout a 4 componentes compartidos en `core/Views/`, resolviendo simultáneamente:
- **#14** — Extraer layout a componentes
- **#2** — Agregar turnos al sidebar
- **#17** — Consolidar clases Core duplicadas (`Audit.php`, `Catalogo.php` vs `Catalogs.php`)

> [!IMPORTANT]
> Cambio arquitectónico mayor que toca 34+ archivos. El plan está diseñado para ser **atómico y reversible**: si algo falla, cada archivo individual se puede restaurar con git.

---

## Alcance

### 34 archivos con layout duplicado

**Módulos (29 archivos):**
1. `modules/actas_locales/index.php`
2. `modules/ciudadanos/create.php`
3. `modules/ciudadanos/index.php`
4. `modules/curp/create.php`
5. `modules/curp/index.php`
6. `modules/defunciones/create.php`
7. `modules/defunciones/index.php`
8. `modules/divorcios/create.php`
9. `modules/divorcios/index.php`
10. `modules/foraneas/create.php`
11. `modules/foraneas/index.php`
12. `modules/inexistencias/create.php`
13. `modules/inexistencias/index.php`
14. `modules/inscripciones/create.php`
15. `modules/inscripciones/index.php`
16. `modules/matrimonios/create.php`
17. `modules/matrimonios/index.php`
18. `modules/nacimientos/create.php`
19. `modules/nacimientos/index.php`
20. `modules/peticion_rapida/create.php`
21. `modules/peticion_rapida/edit.php`
22. `modules/peticion_rapida/index.php`
23. `modules/peticion_rapida/reporte_diario.php`
24. `modules/peticiones/create.php`
25. `modules/peticiones/index.php`
26. `modules/reconocimientos/create.php`
27. `modules/reconocimientos/index.php`
28. `modules/reportes/index.php`
29. `modules/turnos/index.php`

**Public (5 archivos):**
30. `public/auditoria.php`
31. `public/catalogos.php`
32. `public/index.php`
33. `public/perfil.php`
34. `public/usuarios.php`

**Páginas públicas SIN sidebar (no se tocan):**
- `public/login.php`, `public/turnos.php`, `public/validate.php`
- `modules/peticion_rapida/ticket.php`, `modules/turnos/ticket.php`

---

## Cambios Propuestos

### Componente 1 — Nuevos archivos en `core/Views/`

---

#### [NEW] `core/Views/header.php`

Contendrá el `<!DOCTYPE>`, `<head>` completo, apertura de `<body>` y `<div class="wrapper">`.

**Parámetros de entrada** (variables que el archivo consumidor debe definir antes del `require`):
- `$page_title` (string) — título de la página, ej. `'Actas de Nacimiento - ERP DRC'`
- `$extra_css` (array, opcional) — rutas CSS adicionales específicas de la página (ej. TomSelect)

**Lógica interna:**
- Calcula `$base_path` automáticamente a partir de `$_SERVER['SCRIPT_NAME']` (detecta si estamos en `public/` → `../` o en `modules/xxx/` → `../../`)
- Incluye: `fonts.css`, `bootstrap.min.css`, `fontawesome/all.min.css`, `dataTables.bootstrap5.min.css`, `responsive.bootstrap5.min.css`, `sweetalert2.min.css`, `style.css`
- Anti-FOUC: script inline para dark mode desde localStorage
- Abre `<div class="wrapper">`

```php
<?php
// core/Views/header.php
$current_module = basename(dirname($_SERVER['SCRIPT_NAME']));
$is_public = ($current_module === 'public');
$base_path = $is_public ? '..' : '../..';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'ERP DRC'); ?></title>
    <link href="<?php echo $base_path; ?>/assets/css/fonts.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/vendor/fontawesome/css/all.min.css">
    <link href="<?php echo $base_path; ?>/assets/vendor/datatables/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>/assets/vendor/datatables/css/responsive.bootstrap5.min.css" rel="stylesheet">
    <link href="<?php echo $base_path; ?>/assets/vendor/sweetalert2/sweetalert2.min.css" rel="stylesheet">
    <?php if (!empty($extra_css)): foreach ($extra_css as $css): ?>
    <link href="<?php echo $base_path; ?>/<?php echo $css; ?>" rel="stylesheet">
    <?php endforeach; endif; ?>
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/style.css">
    <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark-mode');}</script>
</head>
<body>
<div class="wrapper">
```

---

#### [NEW] `core/Views/sidebar.php`

Contendrá el `<nav id="sidebar">` completo con todos los menús, permisos y detección de página activa.

**Variables disponibles** (calculadas internamente):
- `$current_module` — detectado desde `$_SERVER['SCRIPT_NAME']`
- `$path_prefix` — calculado automáticamente
- Todas las verificaciones de permisos usan `\Core\Auth::hasPermission()`

**Cambio clave vs estado actual:**
- Se **agrega la entrada de Turnos** al submenú de Ventanilla, condicionada a `permiso_turnos`
- Se actualiza la condición del grupo Ventanilla para incluir `permiso_turnos`

```php
<!-- Dentro del submenú Ventanilla, después de "Ventanilla de Seguimiento": -->
<?php if (\Core\Auth::hasPermission('permiso_turnos')): ?>
<li class="<?php echo ($current_module == 'turnos') ? 'active' : ''; ?>">
    <a href="<?php echo ($current_module == 'turnos') ? 'index.php' : $path_prefix . 'turnos/index.php'; ?>">
        <i class="fa-solid fa-list-ol text-info"></i>
        <span class="sidebar-text">Turnos de Atención</span>
    </a>
</li>
<?php endif; ?>
```

---

#### [NEW] `core/Views/navbar.php`

Contendrá la barra superior con:
- Botón hamburguesa (`#sidebarCollapse`)
- Dropdown de notificaciones (`#notificacionesMenu`, `#notifBadge`, `#notifList`)
- Dropdown de perfil de usuario (avatar, nombre, enlace a perfil, cerrar sesión)

**Variables internas:** `$profile_link`, `$logout_link` calculadas desde `$current_module`.

---

#### [NEW] `core/Views/footer.php`

Contendrá:
- Cierre de `</div><!-- /.container-fluid -->`, `</div><!-- /#content -->`, `</div><!-- /.wrapper -->`
- Scripts base: jQuery, Bootstrap bundle, DataTables, SweetAlert2, global.js
- Slot para scripts adicionales: `$extra_js` (array opcional)

```php
<?php
// core/Views/footer.php
?>
        </div><!-- /.container-fluid -->
    </div><!-- /#content -->
</div><!-- /.wrapper -->

<script src="<?php echo $base_path; ?>/assets/vendor/jquery/jquery-3.7.1.min.js"></script>
<script src="<?php echo $base_path; ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $base_path; ?>/assets/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="<?php echo $base_path; ?>/assets/vendor/datatables/js/dataTables.bootstrap5.min.js"></script>
<script src="<?php echo $base_path; ?>/assets/vendor/datatables/js/dataTables.responsive.min.js"></script>
<script src="<?php echo $base_path; ?>/assets/vendor/datatables/js/responsive.bootstrap5.min.js"></script>
<script src="<?php echo $base_path; ?>/assets/vendor/sweetalert2/sweetalert2.all.min.js"></script>
<?php if (!empty($extra_js)): foreach ($extra_js as $js): ?>
<script src="<?php echo $base_path; ?>/<?php echo $js; ?>"></script>
<?php endforeach; endif; ?>
<script src="<?php echo $base_path; ?>/assets/js/global.js"></script>
</body>
</html>
```

---

### Componente 2 — Modificación de los 34 archivos existentes

Cada archivo pasa de ~250-350 líneas a ~50-150 líneas. El patrón de cada archivo queda:

```php
<?php
require_once '../../core/Auth.php';
\Core\Auth::check();
\Core\Auth::checkPermission('permiso_registro_nacimientos');

$page_title = 'Actas de Nacimiento - ERP DRC';
// $extra_css = ['assets/vendor/tomselect/tom-select.bootstrap5.min.css'];
require_once '../../core/Views/header.php';
require_once '../../core/Views/sidebar.php';
require_once '../../core/Views/navbar.php';
?>

<!-- =============== CONTENIDO ESPECÍFICO DE LA PÁGINA =============== -->
<div id="content">
    <div class="container-fluid">
        <!-- ... contenido propio del módulo (se preserva íntegramente) ... -->
    </div>
</div>

<?php
// $extra_js = ['assets/vendor/tomselect/tom-select.complete.min.js'];
require_once '../../core/Views/footer.php';
?>

<script>
// ... JavaScript inline específico de esta página (se preserva) ...
</script>
```

**Lo que se ELIMINA de cada archivo:**
- `<!DOCTYPE>` hasta `<div class="wrapper">` (~20 líneas)
- Todo el `<nav id="sidebar">` (~150 líneas)
- Todo el `<nav class="navbar">` (~30 líneas)
- Los `</div>` de cierre de wrapper y los `<script src>` base (~15 líneas)

**Lo que se PRESERVA intacto:**
- Las llamadas a `Auth::check()` y `Auth::checkPermission()` al inicio
- Todo el contenido HTML específico de la página
- Todo el JavaScript inline específico de la página
- Cualquier PHP de lógica de negocio

---

### Componente 3 — Consolidación de clases Core (#17)

#### [DELETE] `core/Audit.php`
Clase legacy de 35 líneas apuntando a `bitacora_auditoria`. Confirmado como código muerto — todo el sistema usa `core/Auditoria.php` → `auditoria_logs`.

#### [MODIFY] Consolidar `Catalogo.php` y `Catalogs.php`
Verificar cuál se usa activamente, eliminar la otra, y actualizar referencias.

---

## Orden de Ejecución

### Paso 1 — Preparación (sin cambios visibles)
1. Crear directorio `core/Views/`
2. Crear `core/Views/.htaccess` con `Deny from all` (protección web)
3. Crear los 4 parciales (`header.php`, `sidebar.php`, `navbar.php`, `footer.php`)
4. Verificar que `composer dump-autoload -o` no se rompe (los Views no son clases PSR-4)

### Paso 2 — Archivo piloto
5. Migrar **un solo archivo** como piloto: `modules/nacimientos/index.php`
6. Probar manualmente en el navegador: verificar que la página carga correctamente, el sidebar funciona, dark mode funciona, DataTables funciona, notificaciones cargan
7. Si el piloto falla → corregir los parciales antes de continuar

### Paso 3 — Migración por lotes
8. Migrar los 5 archivos de `public/` (tienen rutas `../` en vez de `../../`)
9. Migrar los 28 archivos de `modules/` restantes
10. Verificar en navegador 3-4 páginas representativas de cada grupo

### Paso 4 — Turnos en sidebar (#2)
11. **Ya resuelto automáticamente** — el sidebar compartido ya incluye la entrada de Turnos condicionada a `permiso_turnos`
12. Verificar que un usuario con `permiso_turnos` ve el enlace y puede navegar a `modules/turnos/index.php`

### Paso 5 — Limpieza Core (#17)
13. Eliminar `core/Audit.php`
14. Investigar uso de `Catalogo.php` vs `Catalogs.php` y consolidar
15. Ejecutar `composer dump-autoload -o`

### Paso 6 — Verificación final
16. Ejecutar `vendor\bin\phpunit` (22 tests deben pasar)
17. Verificar lint en los 4 nuevos archivos de Views
18. Navegación manual por las 5 secciones principales del ERP
19. Probar responsive/mobile en al menos 2 páginas

---

## Riesgos y Mitigaciones

| Riesgo | Probabilidad | Mitigación |
|---|---|---|
| Rutas relativas rotas (CSS, JS, links) | Media | `$base_path` se calcula automáticamente por `$_SERVER['SCRIPT_NAME']`; solo 2 patrones posibles (`../` o `../../`) |
| Sidebar con estado `active` incorrecto | Baja | `$current_module` y `basename()` se calculan exactamente igual que antes, solo se mueven a un archivo centralizado |
| JavaScript de `global.js` deja de funcionar | Baja | Los IDs del DOM (`#sidebar`, `#sidebarCollapse`, `#notificacionesMenu`, etc.) se preservan exactamente iguales |
| Algún archivo tiene CSS/JS extra no detectado | Media | Cada archivo se inspecciona individualmente; `$extra_css`/`$extra_js` permiten inyectar dependencias específicas |
| Merge conflicts con trabajo en progreso | Media | Hacer commit antes de empezar; cada archivo individual se puede revertir con `git checkout -- <file>` |

---

## Verificación

### Automatizada
```bash
vendor\bin\phpunit                    # 22 tests deben pasar
```

### Manual (checklist)
- [ ] Dashboard (`public/index.php`) carga correctamente
- [ ] Sidebar muestra/oculta items según permisos del usuario
- [ ] **Turnos aparece en Ventanilla** para usuarios con `permiso_turnos`
- [ ] Dark mode funciona (toggle y persistencia)
- [ ] Sidebar compacto funciona en escritorio (70px, tooltips)
- [ ] Sidebar drawer funciona en móvil (offcanvas, swipe-to-close)
- [ ] Notificaciones cargan por AJAX
- [ ] DataTables funciona en al menos 3 módulos
- [ ] Formularios `create.php` funcionan (TomSelect, AJAX submit, CSRF)
- [ ] Página 403 sigue funcionando (Auth::checkPermission con usuario sin permiso)
- [ ] `core/Audit.php` eliminado y no hay errores

### Documentación
- Actualizar `docs/versions.md` con entrada v1.5.2
- Registrar el cambio en `docs/analisis_completo/09_estado_implementacion.md`

---

## Archivos Afectados — Resumen

| Tipo | Archivos | Acción |
|---|---|---|
| [NEW] | `core/Views/header.php` | Crear |
| [NEW] | `core/Views/sidebar.php` | Crear |
| [NEW] | `core/Views/navbar.php` | Crear |
| [NEW] | `core/Views/footer.php` | Crear |
| [NEW] | `core/Views/.htaccess` | Crear (protección web) |
| [MODIFY] | 34 archivos PHP con layout | Reemplazar layout inline por `require` de parciales |
| [DELETE] | `core/Audit.php` | Eliminar clase legacy |
| [MODIFY] | `Catalogo.php` o `Catalogs.php` | Consolidar (eliminar una) |

**Total estimado:** ~40 archivos tocados, ~5000 líneas eliminadas (duplicación), ~300 líneas nuevas (parciales).

---

## Open Questions

> [!IMPORTANT]
> **¿Hay algún archivo PHP adicional con sidebar que no esté en la lista de 34?** — Los subagentes verificaron con búsqueda exhaustiva pero si conoces alguno que no esté listado, indícalo.

> [!IMPORTANT]
> **¿Hay páginas que incluyan CSS/JS extra específico** (como TomSelect en los `create.php`) que debamos asegurar que se preserven? El mecanismo `$extra_css`/`$extra_js` lo maneja, pero quiero confirmar que no se nos escape alguno.

> [!WARNING]
> **¿Quieres que ejecute la migración de CURP pendiente** (`docs/migration_pv_curp_bindex.php`) **antes** de empezar con esta fase, o la dejas para después?
