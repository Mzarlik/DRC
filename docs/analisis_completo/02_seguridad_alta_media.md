# 🔴 PRIORIDAD ALTA — Seguridad

## 5. Login sin protección CSRF

**Archivos:** [public/auth.php](file:///c:/xampp/htdocs/DRC/public/auth.php) L8-21, [public/login.php](file:///c:/xampp/htdocs/DRC/public/login.php) L70-89

**Problema:** El endpoint de autenticación no implementa ni valida tokens CSRF. Aunque tiene Rate Limiter, permite ataques de Login CSRF (forzar login como otro usuario).

**Acción:** Agregar `Auth::generateCSRF()` en `login.php` y `Auth::validateCSRF()` en `auth.php`.

---

## 6. `.htaccess` — Brechas en el perímetro

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

## 7. `unserialize()` inseguro en Cache.php

**Archivo:** [core/Cache.php](file:///c:/xampp/htdocs/DRC/core/Cache.php) L89, L134

**Problema:** Usa `unserialize()` sin `['allowed_classes' => false]`, lo que permite PHP Object Injection si un atacante logra modificar archivos `.cache`.

**Acción:** Cambiar a `unserialize($data, ['allowed_classes' => false])`.

---

## 8. Ciudadanos delete/restore sin verificación de permisos

**Archivos:** [modules/ciudadanos/delete.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/delete.php) L3, [restore.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/restore.php) L3

**Problema:** Ejecutan `Auth::check()` pero **NO validan ningún rol o permiso específico**. Cualquier operador autenticado puede dar de baja o reactivar registros del padrón.

**Acción:** Agregar `Auth::checkPermission('permiso_registro_nacimientos')` o crear un permiso dedicado.

---

## 9. Validación de contraseña incompleta en perfil

**Archivo:** [public/update_perfil.php](file:///c:/xampp/htdocs/DRC/public/update_perfil.php) L64-74

**Problema:** No verifica que `$newPassword === $confirmPassword` ni valida longitud mínima/complejidad. Si se burla la validación JavaScript, se aceptan contraseñas de 1 carácter.

**Acción:** Agregar validación backend: coincidencia de contraseñas, mínimo 8 caracteres, al menos 1 mayúscula + 1 número.

---

## 10. Fugas CDN — Falla en redes sin Internet

**Problema:** 33 archivos PHP cargan Google Fonts desde `fonts.googleapis.com`. El error 403 en [core/Auth.php](file:///c:/xampp/htdocs/DRC/core/Auth.php) L117 carga Bootstrap desde `cdn.jsdelivr.net`.

**Acción:**
1. Descargar fuente **Inter** (woff2) a `assets/vendor/fonts/` y declarar `@font-face` en `style.css`
2. Cambiar CDN de Bootstrap en Auth.php por ruta local
3. Eliminar 33 referencias a `fonts.googleapis.com`

---

## 11. Llave de cifrado y blind index con fallback hardcodeado

**Archivo:** [core/Encryption.php](file:///c:/xampp/htdocs/DRC/core/Encryption.php) L28, L50

**Problema:** Si `ENCRYPTION_KEY` o `BLIND_INDEX_KEY` no están en `.env`, se usan claves estáticas (`'drc_erp_secure_aes256_symmetric_key_2026'` y `'blind_index_salt_drc'`).

**Acción:** Lanzar excepción fatal si las claves no están configuradas (excepto en entorno de testing).

---

## 12. Exportación diaria sin validar `checkExport()`

**Archivo:** [modules/peticion_rapida/export_diario_excel.php](file:///c:/xampp/htdocs/DRC/modules/peticion_rapida/export_diario_excel.php) L5-8

**Problema:** Verifica `checkPermission('permiso_peticiones_rapidas')` pero **omite `Auth::checkExport()`**. Cualquier usuario con permiso de peticiones rápidas puede exportar, sin necesitar `permiso_exportar`.

**Acción:** Agregar `Auth::checkExport()` al inicio del archivo.

---

## 13. Cookies de sesión sin flags de seguridad

**Problema:** No se configuran `HttpOnly`, `Secure` y `SameSite=Lax`.

**Acción:** Agregar en [Auth.php](file:///c:/xampp/htdocs/DRC/core/Auth.php) o bootstrap:
```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Solo si HTTPS
ini_set('session.cookie_samesite', 'Lax');
```
