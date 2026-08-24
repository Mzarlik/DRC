# 🟡 PRIORIDAD MEDIA-BAJA — Frontend, UX y Estándares

## 22. Campos sin `strtoupper` / `mb_strtoupper` en backend

**Violación de regla:** *"Nombres, observaciones y estados → MAYÚSCULAS"*

| Archivo | Campo | Línea |
|---|---|---|
| [nacimientos/save.php](file:///c:/xampp/htdocs/DRC/modules/nacimientos/save.php) | `$numero_acta` | L18 |
| [GestorNacimientos.php](file:///c:/xampp/htdocs/DRC/core/Services/GestorNacimientos.php) | `$numero_acta` | L26 |
| [defunciones/save.php](file:///c:/xampp/htdocs/DRC/modules/defunciones/save.php) | `$numero_acta` | L19 |
| [GestorDefunciones.php](file:///c:/xampp/htdocs/DRC/core/Services/GestorDefunciones.php) | `$numero_acta` | L25 |
| [foraneas/save.php](file:///c:/xampp/htdocs/DRC/modules/foraneas/save.php) | `$numero_acta`, `$tipo_acta` | L18, L20 |
| [peticiones/save.php](file:///c:/xampp/htdocs/DRC/modules/peticiones/save.php) | `$tipo_peticion` | L23 |

**Acción:** Agregar `mb_strtoupper()` a cada campo afectado.

---

## 23. Eliminar JavaScript duplicado en formularios

**Problema:** Cada `create.php` repite:
1. Event listener de `.text-uppercase-input` (ya en `global.js` L566)
2. Configuración completa de TomSelect (ya existe `initCiudadanoSelect()` en `global.js`)
3. Patrón idéntico de submit AJAX + redirect + toast (12 formularios)

**Acción:** Eliminar duplicados y usar funciones globales existentes.

---

## 24. Extraer CSS inline a hojas de estilo

**Archivos con estilos embebidos:**
- [login.php](file:///c:/xampp/htdocs/DRC/public/login.php) L26-56 (`.login-card`)
- [turnos.php](file:///c:/xampp/htdocs/DRC/public/turnos.php) L13-34 (kiosco)
- [validate.php](file:///c:/xampp/htdocs/DRC/public/validate.php) L94-118 (validación QR)
- Botones dispersos con `style="background: var(--secondary-color); border: none;"`

**Acción:** Mover a secciones nombradas en `assets/css/style.css`.

---

## 25. Estandarizar feedback post-guardado

**Problema:** Algunos módulos usan `Swal.fire()` modal, otros toast auto-dismiss 3s, otros redirect directo.

**Acción:** Usar patrón único: Toast automático post-redirect vía `?toast=success&msg=...`.

---

## 26. Mejorar accesibilidad (A11y)

**Acciones:**
1. `aria-label` en botones de acción con solo iconos (DataTables, sidebar)
2. `<label for>` correcto en TomSelect e inputs hidden
3. `<label>` en filtros de estatus (`#filter_peticion_estatus`)
4. Verificar contraste en `.text-muted` con `0.68rem`

---

## 27. SQL con fechas interpoladas en stats.php

**Archivo:** [public/api/stats.php](file:///c:/xampp/htdocs/DRC/public/api/stats.php) L17-25, L84-92

**Problema:** `$today` y `$six_days_ago` se interpolan en strings SQL en vez de usar `bindValue()`. Aunque no son manipulables por usuario, viola el estándar del proyecto.

**Acción:** Migrar a prepared statements con `bindValue()`.

---

## 28. Manejo de excepciones incompleto

| Archivo | Problema |
|---|---|
| [public/validate.php](file:///c:/xampp/htdocs/DRC/public/validate.php) L78-80 | `catch (Exception $e)` no captura `TypeError`/`Error` en PHP 8+ |
| [public/update_perfil.php](file:///c:/xampp/htdocs/DRC/public/update_perfil.php) L102 | Solo `catch (PDOException $e)` |

**Acción:** Cambiar a `catch (\Throwable $e)` en ambos archivos.
