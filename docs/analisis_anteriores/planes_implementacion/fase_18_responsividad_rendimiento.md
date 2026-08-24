# Plan de Implementación: Responsividad de Rendimiento (PWA-Like - Fase 4)

Este plan detalla la optimización del rendimiento táctil y la experiencia de usuario (UX) al guardar registros, eliminando recargas completas de pantalla y reemplazando las alertas molestas de pantalla completa por notificaciones tipo Toast.

---

## Cambios Propuestos

### 1. Eliminar Recargas de Página (Modal e Instant Update en Inexistencias)

Convertiremos el formulario de creación en el módulo de **Inexistencias** en un modal integrado en la misma página de índice. Esto permite registrar nuevos datos sin abandonar la pantalla y actualizar la tabla instantáneamente.

#### [MODIFY] [index.php (Inexistencias)](file:///c:/xampp/htdocs/DRC/modules/inexistencias/index.php)
* Reemplazar el enlace a `create.php` por un botón que active el modal:
  ```html
  <button class="btn btn-primary" style="background: var(--secondary-color); border: none;" data-bs-toggle="modal" data-bs-target="#createInexistenciaModal">
      <i class="fa-solid fa-plus"></i> Nuevo Registro
  </button>
  ```
* Añadir el marcado del modal de registro de Inexistencias al final de la página (con los campos `tipo_constancia`, `linea_pago`, `nombre_completo`, `fecha_tramite`, `fecha_llegada` y `observaciones`).
* Añadir el escuchador de eventos para el envío por AJAX del formulario en el modal:
  * Al enviar, llamar a `save.php` por POST.
  * Si la respuesta es exitosa, cerrar el modal, limpiar el formulario, recargar la DataTable (`table.ajax.reload(null, false)`) y mostrar una notificación tipo Toast.

---

### 2. Notificaciones Tipo Toast en lugar de Alertas

Reemplazaremos los popups gigantes de SweetAlert2 por Toasts pequeños que aparecen en la parte superior derecha durante 3 segundos y se descartan automáticamente.

#### [MODIFY] [global.js](file:///c:/xampp/htdocs/DRC/assets/js/global.js)
* Crear una función global `window.showToast(type, title, text)` para disparar notificaciones SweetAlert2 no bloqueantes.
* Implementar un lector automático de parámetros de URL (`?toast=success&msg=Texto`) para que las páginas tradicionales que redirigen tras guardar registros puedan mostrar notificaciones Toast de forma fluida tras la navegación.

#### [MODIFY] [save.php de todos los módulos](file:///c:/xampp/htdocs/DRC/modules/)
Para los otros módulos que rediriguen a su página de índice, modificaremos el redireccionamiento para pasar parámetros de Toast en la URL, los cuales serán interpretados por `global.js` mostrando un Toast en la pantalla de destino en lugar del alert tradicional de confirmación:
* [Nacimientos](file:///c:/xampp/htdocs/DRC/modules/nacimientos/create.php)
* [Matrimonios](file:///c:/xampp/htdocs/DRC/modules/matrimonios/create.php)
* [Divorcios](file:///c:/xampp/htdocs/DRC/modules/divorcios/create.php)
* [Defunciones](file:///c:/xampp/htdocs/DRC/modules/defunciones/create.php)
* [Inscripciones](file:///c:/xampp/htdocs/DRC/modules/inscripciones/create.php)
* [Reconocimientos](file:///c:/xampp/htdocs/DRC/modules/reconocimientos/create.php)
* [Actas Foráneas](file:///c:/xampp/htdocs/DRC/modules/foraneas/create.php)
* [Ciudadanos](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/create.php)

---

## Plan de Verificación

### Pruebas de Flujo:
1. **Verificar Modal de Inexistencias**:
   * Abrir el listado de Inexistencias en el navegador.
   * Hacer clic en "Nuevo Registro". Verificar que se abra el formulario en un modal de Bootstrap.
   * Rellenar los campos y hacer clic en "Guardar Registro".
   * Validar que el modal se cierre solo, la tabla de DataTables se actualice en segundo plano sin recargar la página, y aparezca el Toast de éxito arriba a la derecha.
2. **Validar Notificaciones Toast en Redireccionamientos**:
   * Ingresar al módulo de Nacimientos, hacer clic en "Nuevo Registro", rellenar el formulario y hacer clic en guardar.
   * Validar que, tras redirigir al listado, aparezca un Toast flotante arriba a la derecha indicando éxito y se limpie la URL del navegador automáticamente.
