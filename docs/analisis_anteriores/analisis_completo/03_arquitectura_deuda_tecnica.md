# 🟠 PRIORIDAD MEDIA — Arquitectura y Deuda Técnica

## 14. Extraer Layout a Componentes Compartidos

**Problema:** No existen parciales (`header.php`, `sidebar.php`, `footer.php`). El layout completo (head, sidebar 150+ líneas, navbar, scripts) está **copiado en 34 archivos PHP**. Cada cambio de navegación requiere editar 34 archivos.

**Acción:** Crear:
- `core/Views/header.php` — `<head>`, CSS, anti-FOUC
- `core/Views/sidebar.php` — Menú lateral con permisos
- `core/Views/navbar.php` — Barra superior, notificaciones
- `core/Views/footer.php` — Scripts, cierre de tags

> [!WARNING]
> Cambio masivo. Requiere tests E2E completos antes y después. Resuelve automáticamente #2 (turnos en sidebar).

---

## 15. Crear Clases de Servicio para Módulos Faltantes

**Problema:** Solo 4 de 13 módulos delegan a `core/Services/`. Los otros 9 ejecutan SQL en `save.php`.

**Módulos sin servicio dedicado:**

| Módulo | Servicio sugerido | Justificación |
|---|---|---|
| matrimonios | `GestorMatrimonios.php` | Validación de régimen, testigos, capitulaciones |
| divorcios | `GestorDivorcios.php` | Validación de acta de matrimonio previa |
| reconocimientos | `GestorReconocimientos.php` | Validación de parentesco |
| inscripciones | `GestorInscripciones.php` | Validación de actas extranjeras |
| foraneas | `GestorForaneas.php` | Validación de procedencia |
| curp | `GestorCurp.php` | Validación de trámite CURP |
| peticiones | `GestorPeticiones.php` | Transiciones de estatus |
| reportes | `ReportService.php` | Lógica de filtros multi-módulo |
| ciudadanos | `GestorCiudadanos.php` | CRUD con soft-delete y cifrado |

---

## 16. Homologar `crear.php` → `create.php` en módulo Turnos

**Archivo:** [modules/turnos/crear.php](file:///c:/xampp/htdocs/DRC/modules/turnos/crear.php)

**Acción:** Renombrar a `create.php` y actualizar referencias internas.

---

## 17. Consolidar clases Core duplicadas

**Problema:**
- `Audit.php` (legacy, sin uso) vs `Auditoria.php` (activa)
- `Catalogo.php` vs `Catalogs.php`

**Acción:** Eliminar `Audit.php` (código muerto confirmado). Consolidar catálogos en una sola clase.

---

## 18. Unificar sistema de migraciones

**Problema:** Coexisten `core/Migrate.php` (SQL numerados en `docs/migrations/`) con 5 scripts PHP sueltos (`docs/migration_*.php`).

**Acción:** Convertir las 5 migraciones PHP a SQL numerados y deprecar los scripts PHP.

---

## 19. Módulos sin exportación Excel

**Módulos faltantes:** `curp` y `peticiones`.

**Acción:** Implementar `export_excel.php` para ambos usando `ExcelReportFormatter` + cola de jobs asíncronos.

---

## 20. Panel de Usuarios sin DataTables

**Archivo:** [public/usuarios.php](file:///c:/xampp/htdocs/DRC/public/usuarios.php)

**Problema:** Único listado con `foreach` PHP y paginación propia en vez de DataTables server-side.

**Acción:** Migrar a DataTables con endpoint `users_data.php`.

---

## 21. Tabla de auditoría legacy sin uso

**Problema:** `bitacora_auditoria` (v1) coexiste con `auditoria_logs` (v2). `Audit.php` apunta a la tabla legacy.

**Acción:** Verificar que nada escriba en `bitacora_auditoria`. Si confirmado, archivarla con migración.
