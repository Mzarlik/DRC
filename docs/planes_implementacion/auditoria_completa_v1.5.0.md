# 📋 Auditoría Integral del ERP DRC — Listado Maestro de Tareas

> **Fecha:** 21 de agosto de 2026 · **Versión actual:** v1.5.0
> **Propósito:** Listado exhaustivo y delegable de revisiones, correcciones y mejoras pendientes.
> Cada ítem incluye prioridad, archivos afectados y contexto suficiente para que un agente externo (DeepSeek u otro) pueda ejecutar la tarea sin ambigüedad.

---

## 🚨 PRIORIDAD CRÍTICA — Bugs y Vulnerabilidades en Producción

### 1. Bug de Mapeo de Sexo/Género (Corrupción de datos)

**Problema:** Los valores de `sexo` son incompatibles entre el formulario principal, el modal rápido y el backend. **Los ciudadanos masculinos registrados desde `create.php` quedan guardados como `'X'` (No binario).**

| Fuente | Masculino | Femenino | Otro |
|---|---|---|---|
| [create.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/create.php) L266-268 | `M` | `F` | `X` |
| [global.js](file:///c:/xampp/htdocs/DRC/assets/js/global.js) L1118-1120 (modal rápido) | `H` | `M` | `X` |
| [save.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/save.php) L23 (backend) | Espera `H` → `M` | Espera `F`/`MUJER` → `F` | Default → `X` |

**Falla:** Si un usuario registra masculino desde `create.php`, envía `sexo="M"`, backend no matchea `H` ni `F`, **asigna `'X'`**. Si registra mujer desde modal rápido, envía `sexo="M"` (Mujer), backend tampoco matchea → `'X'`.

**Acción:**
1. Decidir convención: ¿`H/M/X` o `M/F/X`?
2. Corregir `create.php`, `save.php` y `global.js`
3. Ejecutar UPDATE correctivo en BD para registros corruptos

**Archivos:** `modules/ciudadanos/create.php`, `modules/ciudadanos/save.php`, `assets/js/global.js`

---

### 2. Módulo de Turnos sin enlace en el Sidebar

**Problema:** [modules/turnos/index.php](file:///c:/xampp/htdocs/DRC/modules/turnos/index.php) está completamente implementado con permisos (`permiso_turnos`), pero **no aparece en la navegación del sidebar** en ninguna vista. Es invisible para los usuarios.

**Acción:** Agregar enlace al sidebar en los **34 archivos PHP** con menú lateral replicado, condicionado a `permiso_turnos`.

> [!TIP]
> Combinar con el ítem #12 (extraer layout a componentes) para evitar modificar 34 archivos dos veces.

---

### 3. CURP almacenada en texto plano en Peticiones Rápidas

**Problema:** La columna `solicitante_curp` en `peticiones_ventanilla` se guarda **sin cifrar**, violando la regla del proyecto: *"CURP: cifrar con `Core\Encryption::encrypt()` y buscar cifrado"*.

**Archivos afectados:**
- [PeticionRapidaService.php](file:///c:/xampp/htdocs/DRC/core/Services/PeticionRapidaService.php) L376: `'solicitante_curp' => !empty($curp) ? $curp : null`
- [modules/peticion_rapida/save.php](file:///c:/xampp/htdocs/DRC/modules/peticion_rapida/save.php) L47
- [modules/peticion_rapida/update.php](file:///c:/xampp/htdocs/DRC/modules/peticion_rapida/update.php) L62
- [modules/peticion_rapida/ticket.php](file:///c:/xampp/htdocs/DRC/modules/peticion_rapida/ticket.php) L73

**Acción:**
1. Cifrar con `Encryption::encrypt()` al guardar/actualizar
2. Descifrar con `Encryption::decrypt()` al leer para tickets
3. Ejecutar migración para cifrar CURPs existentes en `peticiones_ventanilla`

---

### 4. CSRF simulado en nacimientos/save.php (solo comprueba presencia)

**Archivo:** [modules/nacimientos/save.php](file:///c:/xampp/htdocs/DRC/modules/nacimientos/save.php) L13-16

```php
if (empty($_POST['csrf_token'])) {  // ❌ Solo comprueba que no esté vacío
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
    exit;
}
```

**Impacto:** Un atacante enviando `csrf_token=1` pasa la validación. Nunca se llama a `Auth::validateCSRF()`.

**Acción:** Reemplazar por `Auth::validateCSRF($_POST['csrf_token'])`.

---

## 🔴 PRIORIDAD ALTA — Seguridad

### 5. Login sin protección CSRF

**Archivos:** [public/auth.php](file:///c:/xampp/htdocs/DRC/public/auth.php) L8-21, [public/login.php](file:///c:/xampp/htdocs/DRC/public/login.php) L70-89

**Problema:** El endpoint de autenticación no implementa ni valida tokens CSRF. Aunque tiene Rate Limiter, permite ataques de Login CSRF (forzar login como otro usuario).

**Acción:** Agregar `Auth::generateCSRF()` en `login.php` y `Auth::validateCSRF()` en `auth.php`.

---

### 6. `.htaccess` — Brechas en el perímetro

**Archivo:** [.htaccess](file:///c:/xampp/htdocs/DRC/.htaccess)

| Brecha | Detalle |
|---|---|
| `vendor/` y `tests/` expuestos | No están en la regla de reescritura L33 que bloquea `core\|docs\|logs\|cache\|scripts\|\.agents` |
| `composer.phar` accesible | L9 bloquea `composer.(json\|lock)` pero no `.phar` (3.5 MB binario) |
| Exportaciones no-XLSX | L34 solo bloquea `.xlsx`, no `.csv`, `.pdf`, `.zip` |
| Archivos `.md` en raíz | `AGENTS.md`, `ROADMAP.md`, `CONTEXTO.md`, `TESTING_SECURITY.md` accesibles por browser |
| Sin CSP header | No hay cabecera `Content-Security-Policy` |

**Acción:** Actualizar `.htaccess` para bloquear `vendor/`, `tests/`, `composer.phar`, archivos `.md` en raíz, y agregar CSP header.

---

### 7. `unserialize()` inseguro en Cache.php

**Archivo:** [core/Cache.php](file:///c:/xampp/htdocs/DRC/core/Cache.php) L89, L134

**Problema:** Usa `unserialize()` sin `['allowed_classes' => false]`, lo que permite PHP Object Injection si un atacante logra modificar archivos `.cache`.

**Acción:** Cambiar a `unserialize($data, ['allowed_classes' => false])`.

---

### 8. Ciudadanos delete/restore sin verificación de permisos

**Archivos:** [modules/ciudadanos/delete.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/delete.php) L3, [restore.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/restore.php) L3

**Problema:** Ejecutan `Auth::check()` pero **NO validan ningún rol o permiso específico**. Cualquier operador autenticado puede dar de baja o reactivar registros del padrón.

**Acción:** Agregar `Auth::checkPermission('permiso_registro_nacimientos')` o crear un permiso dedicado.

---

### 9. Validación de contraseña incompleta en perfil

**Archivo:** [public/update_perfil.php](file:///c:/xampp/htdocs/DRC/public/update_perfil.php) L64-74

**Problema:** No verifica que `$newPassword === $confirmPassword` ni valida longitud mínima/complejidad. Si se burla la validación JavaScript, se aceptan contraseñas de 1 carácter.

**Acción:** Agregar validación backend: coincidencia de contraseñas, mínimo 8 caracteres, al menos 1 mayúscula + 1 número.

---

### 10. Fugas CDN — Falla en redes sin Internet

**Problema:** 33 archivos PHP cargan Google Fonts desde `fonts.googleapis.com`. El error 403 en [core/Auth.php](file:///c:/xampp/htdocs/DRC/core/Auth.php) L117 carga Bootstrap desde `cdn.jsdelivr.net`.

**Acción:**
1. Descargar fuente **Inter** (woff2) a `assets/vendor/fonts/` y declarar `@font-face` en `style.css`
2. Cambiar CDN de Bootstrap en Auth.php por ruta local
3. Eliminar 33 referencias a `fonts.googleapis.com`

---

### 11. Llave de cifrado y blind index con fallback hardcodeado

**Archivo:** [core/Encryption.php](file:///c:/xampp/htdocs/DRC/core/Encryption.php) L28, L50

**Problema:** Si `ENCRYPTION_KEY` o `BLIND_INDEX_KEY` no están en `.env`, se usan claves estáticas (`'drc_erp_secure_aes256_symmetric_key_2026'` y `'blind_index_salt_drc'`).

**Acción:** Lanzar excepción fatal si las claves no están configuradas (excepto en entorno de testing).

---

### 12. Exportación diaria sin validar `checkExport()`

**Archivo:** [modules/peticion_rapida/export_diario_excel.php](file:///c:/xampp/htdocs/DRC/modules/peticion_rapida/export_diario_excel.php) L5-8

**Problema:** Verifica `checkPermission('permiso_peticiones_rapidas')` pero **omite `Auth::checkExport()`**. Cualquier usuario con permiso de peticiones rápidas puede exportar, sin necesitar `permiso_exportar`.

**Acción:** Agregar `Auth::checkExport()` al inicio del archivo.

---

### 13. Cookies de sesión sin flags de seguridad

**Problema:** No se configuran `HttpOnly`, `Secure` y `SameSite=Lax`.

**Acción:** Agregar en [Auth.php](file:///c:/xampp/htdocs/DRC/core/Auth.php) o bootstrap:
```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Solo si HTTPS
ini_set('session.cookie_samesite', 'Lax');
```

---

## 🟠 PRIORIDAD MEDIA — Arquitectura y Deuda Técnica

### 14. Extraer Layout a Componentes Compartidos

**Problema:** No existen parciales (`header.php`, `sidebar.php`, `footer.php`). El layout completo (head, sidebar 150+ líneas, navbar, scripts) está **copiado en 34 archivos PHP**. Cada cambio de navegación requiere editar 34 archivos.

**Acción:** Crear:
- `core/Views/header.php` — `<head>`, CSS, anti-FOUC
- `core/Views/sidebar.php` — Menú lateral con permisos
- `core/Views/navbar.php` — Barra superior, notificaciones
- `core/Views/footer.php` — Scripts, cierre de tags

> [!WARNING]
> Cambio masivo. Requiere tests E2E completos antes y después. Resuelve automáticamente #2 (turnos en sidebar).

---

### 15. Crear Clases de Servicio para Módulos Faltantes

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

### 16. Homologar `crear.php` → `create.php` en módulo Turnos

**Archivo:** [modules/turnos/crear.php](file:///c:/xampp/htdocs/DRC/modules/turnos/crear.php)

**Acción:** Renombrar a `create.php` y actualizar referencias internas.

---

### 17. Consolidar clases Core duplicadas

**Problema:**
- `Audit.php` (legacy, sin uso) vs `Auditoria.php` (activa)
- `Catalogo.php` vs `Catalogs.php`

**Acción:** Eliminar `Audit.php` (código muerto confirmado). Consolidar catálogos en una sola clase.

---

### 18. Unificar sistema de migraciones

**Problema:** Coexisten `core/Migrate.php` (SQL numerados en `docs/migrations/`) con 5 scripts PHP sueltos (`docs/migration_*.php`).

**Acción:** Convertir las 5 migraciones PHP a SQL numerados y deprecar los scripts PHP.

---

### 19. Módulos sin exportación Excel

**Módulos faltantes:** `curp` y `peticiones`.

**Acción:** Implementar `export_excel.php` para ambos usando `ExcelReportFormatter` + cola de jobs asíncronos.

---

### 20. Panel de Usuarios sin DataTables

**Archivo:** [public/usuarios.php](file:///c:/xampp/htdocs/DRC/public/usuarios.php)

**Problema:** Único listado con `foreach` PHP y paginación propia en vez de DataTables server-side.

**Acción:** Migrar a DataTables con endpoint `users_data.php`.

---

### 21. Tabla de auditoría legacy sin uso

**Problema:** `bitacora_auditoria` (v1) coexiste con `auditoria_logs` (v2). `Audit.php` apunta a la tabla legacy.

**Acción:** Verificar que nada escriba en `bitacora_auditoria`. Si confirmado, archivarla con migración.

---

## 🟡 PRIORIDAD MEDIA-BAJA — Frontend, UX y Estándares

### 22. Campos sin `strtoupper` / `mb_strtoupper` en backend

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

### 23. Eliminar JavaScript duplicado en formularios

**Problema:** Cada `create.php` repite:
1. Event listener de `.text-uppercase-input` (ya en `global.js` L566)
2. Configuración completa de TomSelect (ya existe `initCiudadanoSelect()` en `global.js`)
3. Patrón idéntico de submit AJAX + redirect + toast (12 formularios)

**Acción:** Eliminar duplicados y usar funciones globales existentes.

---

### 24. Extraer CSS inline a hojas de estilo

**Archivos con estilos embebidos:**
- [login.php](file:///c:/xampp/htdocs/DRC/public/login.php) L26-56 (`.login-card`)
- [turnos.php](file:///c:/xampp/htdocs/DRC/public/turnos.php) L13-34 (kiosco)
- [validate.php](file:///c:/xampp/htdocs/DRC/public/validate.php) L94-118 (validación QR)
- Botones dispersos con `style="background: var(--secondary-color); border: none;"`

**Acción:** Mover a secciones nombradas en `assets/css/style.css`.

---

### 25. Estandarizar feedback post-guardado

**Problema:** Algunos módulos usan `Swal.fire()` modal, otros toast auto-dismiss 3s, otros redirect directo.

**Acción:** Usar patrón único: Toast automático post-redirect vía `?toast=success&msg=...`.

---

### 26. Mejorar accesibilidad (A11y)

**Acciones:**
1. `aria-label` en botones de acción con solo iconos (DataTables, sidebar)
2. `<label for>` correcto en TomSelect e inputs hidden
3. `<label>` en filtros de estatus (`#filter_peticion_estatus`)
4. Verificar contraste en `.text-muted` con `0.68rem`

---

### 27. SQL con fechas interpoladas en stats.php

**Archivo:** [public/api/stats.php](file:///c:/xampp/htdocs/DRC/public/api/stats.php) L17-25, L84-92

**Problema:** `$today` y `$six_days_ago` se interpolan en strings SQL en vez de usar `bindValue()`. Aunque no son manipulables por usuario, viola el estándar del proyecto.

**Acción:** Migrar a prepared statements con `bindValue()`.

---

### 28. Manejo de excepciones incompleto

| Archivo | Problema |
|---|---|
| [public/validate.php](file:///c:/xampp/htdocs/DRC/public/validate.php) L78-80 | `catch (Exception $e)` no captura `TypeError`/`Error` en PHP 8+ |
| [public/update_perfil.php](file:///c:/xampp/htdocs/DRC/public/update_perfil.php) L102 | Solo `catch (PDOException $e)` |

**Acción:** Cambiar a `catch (\Throwable $e)` en ambos archivos.

---

## 📄 PRIORIDAD MEDIA — Documentación

### 29. Actualizar `docs/esquema_bd.md`

**Faltante:** Tablas de `turnos`, `peticion_rapida`, `peticiones_ventanilla`. Rol `COORDINADOR`. Banderas `permiso_exportar`, `permiso_peticiones_rapidas`, `permiso_turnos`.

---

### 30. Actualizar `GUIA_DESARROLLADOR.md`

**Faltante:** Sección de permisos solo lista 11 banderas originales. Falta documentar: rol `COORDINADOR`, 3 banderas nuevas, servicios nuevos (`PeticionRapidaService`, `FirmaElectronicaService`, `ErrorMessages`).

---

### 31. Actualizar `CONTEXTO.md`

**Faltante:** Redactado hasta Fase 6 / v1.3.0. No refleja fases 10-18 ni v1.5.0.

---

### 32. Completar `docs/api_referencia.md`

**Faltante:** Verificar inclusión de todos los endpoints de v1.4.0-v1.5.0:
- `modules/peticion_rapida/*` (13 endpoints)
- `modules/turnos/*` (5 endpoints)
- `public/api/turnos_pantalla.php`
- `public/turnos.php`

---

## 🔵 PRIORIDAD BAJA — Mejoras Futuras y Roadmap

### 33. CI/CD con GitHub Actions

Pipeline con: PSR-12 (PHP CS Fixer), PHPStan análisis estático, PHPUnit, Playwright E2E.

---

### 34. Firma electrónica avanzada en actas

Emisión con firma electrónica simplificada, marcas de agua digitales, QR de verificación mejorados.

---

### 35. Observabilidad y telemetría

Integración de Sentry o Monolog estructurado para monitoreo en caliente.

---

### 36. Portabilidad — Ruta PHP hardcodeada

**Archivos:** [core/Jobs.php](file:///c:/xampp/htdocs/DRC/core/Jobs.php) L28, [core/Services/FirmaElectronicaService.php](file:///c:/xampp/htdocs/DRC/core/Services/FirmaElectronicaService.php) L73-77

**Acción:** Usar constante `PHP_BINARY` o leer desde `.env`.

---

### 37. Credenciales fallback en Database.php

**Archivo:** [core/Database.php](file:///c:/xampp/htdocs/DRC/core/Database.php) L17-21

**Acción:** Forzar error fatal si credenciales no están en `.env`.

---

### 38. Expandir suite de pruebas

**Estado actual:** 8 tests unitarios + 1 test E2E.

**Sugerencias:**
- Tests para `PeticionRapidaService`, `ExcelReportFormatter`, `ErrorMessages`
- Tests de integración para flujos CRUD
- Tests E2E para ciudadano, petición rápida, turnos
- Code coverage con `--coverage-html`

---

### 39. Localizar todas las dependencias CDN

Verificar que **todas** las dependencias pueden funcionar 100% offline/airgapped (Google Fonts ya identificado en #10).

---

### 40. Rutas OpenSSL hardcodeadas

**Archivo:** [core/Services/FirmaElectronicaService.php](file:///c:/xampp/htdocs/DRC/core/Services/FirmaElectronicaService.php) L73-77

**Problema:** Rutas estáticas para `openssl.cnf` (`C:/xampp/apache/bin/openssl.cnf`, `/etc/ssl/openssl.cnf`).

**Acción:** Configurar en `.env` o detectar automáticamente.

---

### 41. Agregar Content-Security-Policy header

**Archivo:** [.htaccess](file:///c:/xampp/htdocs/DRC/.htaccess)

**Acción:** Agregar cabecera CSP para mitigar XSS y data exfiltration.

---

### 42. Considerar migrar `usuarios.php` a DataTables

Ya mencionado en #20 — listado de usuarios es el único sin DataTables server-side.

---

## 📊 Resumen Ejecutivo

| Categoría | Cant. | Ítems |
|---|---|---|
| 🚨 **Bugs / Vulnerabilidades críticas** | 4 | #1, #2, #3, #4 |
| 🔴 **Seguridad alta/media** | 9 | #5, #6, #7, #8, #9, #10, #11, #12, #13 |
| 🟠 **Arquitectura / Deuda técnica** | 8 | #14, #15, #16, #17, #18, #19, #20, #21 |
| 🟡 **Frontend / UX / Estándares** | 7 | #22, #23, #24, #25, #26, #27, #28 |
| 📄 **Documentación** | 4 | #29, #30, #31, #32 |
| 🔵 **Mejoras futuras / Roadmap** | 10 | #33-#42 |
| **TOTAL** | **42 ítems** | — |

---

## 🤖 Guía de Delegación a DeepSeek

### ✅ Tareas seguras para delegar (bajo riesgo, bien definidas):

| # | Tarea | Complejidad |
|---|---|---|
| 16 | Renombrar `crear.php` → `create.php` en turnos | Trivial |
| 22 | Agregar `mb_strtoupper()` a campos faltantes | Baja |
| 23 | Eliminar JS duplicado en `create.php` | Baja |
| 24 | Extraer CSS inline a `style.css` | Baja |
| 25 | Estandarizar feedback post-guardado | Baja |
| 26 | Agregar aria-labels y mejoras A11y | Baja |
| 27 | Migrar fechas en stats.php a prepared statements | Baja |
| 28 | Cambiar `catch(Exception)` → `catch(\Throwable)` | Trivial |
| 29-32 | Actualización de 4 documentos | Media |

### ⚠️ Tareas delegables con revisión manual posterior:

| # | Tarea | Notas |
|---|---|---|
| 4 | CSRF real en nacimientos/save.php | Verificar patrón de otros módulos |
| 5 | CSRF en login | Seguir patrón existente |
| 7 | `unserialize()` seguro en Cache.php | Cambio puntual pero sensible |
| 9 | Validación de contraseña en perfil | Requiere definir política |
| 10 | Localizar Google Fonts y CDN Auth.php | Descargar fuentes + actualizar 33 archivos |
| 12 | Agregar `checkExport()` a export_diario | Cambio puntual |
| 15 | Crear clases de servicio | Seguir patrón de `GestorNacimientos` |
| 19 | Export Excel para curp y peticiones | Seguir patrón existente |
| 20 | Usuarios.php a DataTables | Crear `users_data.php` |
| 38 | Expandir tests | Seguir patrones existentes en `tests/Unit/` |

### 🔒 Tareas que requieren supervisión directa:

| # | Tarea | Razón |
|---|---|---|
| 1 | Bug de sexo/género | **Decisión de negocio** + script correctivo de datos en producción |
| 2 | Turnos en sidebar | Afecta 34 archivos, ideal combinar con #14 |
| 3 | Cifrar CURP en peticiones_ventanilla | Migración de datos sensibles en producción |
| 6 | Brechas en .htaccess | Configuración de seguridad perimetral |
| 8 | Permisos en delete/restore ciudadanos | Decisión de política de acceso |
| 11 | Eliminar fallback encryption key | Impacto en entornos existentes |
| 13 | Cookies de sesión seguras | Depende de si hay HTTPS |
| 14 | **Extraer layout compartido** | Cambio arquitectónico mayor (34 archivos) |
| 17 | Consolidar clases Core duplicadas | Riesgo de romper compatibilidad |
| 21 | Archivar tabla auditoría legacy | Requiere verificar que no hay dependencias |
| 37 | Credenciales fallback Database.php | Impacto en entornos existentes |
