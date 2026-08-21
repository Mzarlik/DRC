# 📊 Estado de Implementación — v1.5.1

> **Última actualización:** 21 de agosto de 2026  
> **Referencia:** [docs/versions.md](file:///c:/xampp/htdocs/DRC/docs/versions.md) — Changelog v1.5.1

---

## ✅ Implementado en v1.5.1 (16 ítems)

| # | Ítem | Cambio Realizado | Archivos Clave |
|---|---|---|---|
| **#1** | Bug de sexo/género | Modal rápido ahora envía M/F/X (igual que `create.php` y BD); `save.php` normaliza H/HOMBRE/M/MASCULINO→M, F/FEMENINO/MUJER→F | `assets/js/global.js`, `modules/ciudadanos/save.php` |
| **#3** | CURP cifrada | `PeticionRapidaService` cifra + blind index; `save/update/data/edit/ticket/Worker` descifran en salida; búsqueda exacta por CURP vía índice. Migración CLI creada | `core/Services/PeticionRapidaService.php`, `modules/peticion_rapida/*`, `docs/migration_pv_curp_bindex.php` |
| **#4** | CSRF nacimientos | `nacimientos/save.php` usa `Auth::validateCSRF()` (era el último módulo pendiente) | `modules/nacimientos/save.php` |
| **#5** | Login CSRF | Token CSRF en formulario `login.php` + validación en `auth.php`; sesión vía `Auth::initSession()` | `public/login.php`, `public/auth.php` |
| **#6** | .htaccess | Bloquea `vendor/`, `tests/`, `.phar`, `*.md`, exportes csv/pdf/zip + CSP estricta | `.htaccess` |
| **#7** | unserialize | `allowed_classes => false` en los 3 puntos de `Cache.php` | `core/Cache.php` |
| **#8** | delete/restore | Solo ADMIN/COORDINADOR/SUPERVISOR (`esCoordinador()`) | `modules/ciudadanos/delete.php`, `modules/ciudadanos/restore.php` |
| **#9** | Contraseñas | Backend valida confirmación + mínimo 8 chars + mayúscula + número (perfil y creación de usuarios) | `public/update_perfil.php`, `public/update_usuario.php` |
| **#10** | CDN fuera | Fuente Inter variable local (`assets/vendor/fonts/`) + CSS local reemplaza las 34 referencias a Google Fonts; página 403 de Auth.php autónoma sin jsdelivr | `assets/vendor/fonts/inter-latin-var.woff2`, `assets/css/fonts.css`, `core/Auth.php` |
| **#11** | Claves cifrado | Sin fallback público: falta `ENCRYPTION_KEY` ⇒ excepción (excepto tests); blind index conserva derivación compatible con datos existentes | `core/Encryption.php` |
| **#12** | checkExport | Añadido a `export_diario_excel.php` | `modules/peticion_rapida/export_diario_excel.php` |
| **#13** | Cookies sesión | `Auth::initSession()` configura `HttpOnly`, `SameSite=Lax`, `Secure` (si HTTPS) | `core/Auth.php` |
| **#16** | crear→create | `modules/turnos/crear.php` renombrado a `create.php`; referencia AJAX actualizada | `modules/turnos/create.php` |
| **#22** | strtoupper | `mb_strtoupper` en `numero_acta` (nacimientos, defunciones, foráneas + Gestores), `tipo_acta` (foráneas) y `tipo_peticion` (peticiones) | Múltiples `save.php` y Gestores |
| **#27** | stats.php PDO | Prepared statements con `bindValue()` para las fechas de contadores diarios | `public/api/stats.php` |
| **#28** | \\Throwable | `validate.php` y `update_perfil.php` capturan `\Throwable` | `public/validate.php`, `public/update_perfil.php` |

**Verificación realizada:** lint OK en 30+ archivos clave, 0 caracteres residuales, 22/22 tests PHPUnit OK.

---

## ⚠️ Acciones Pendientes del Usuario

1. **Ejecutar migración** (regla del proyecto: no la ejecuté):
   ```
   c:\xampp\php\php.exe docs\migration_pv_curp_bindex.php
   ```

2. **Registros históricos con `sexo='X'`:** No son auto-corregibles (los X legítimos comparten valor); requieren revisión manual.

3. **Verificar `.env` de producción:** Que existan `ENCRYPTION_KEY` y (opcional) `BLIND_INDEX_KEY`.

---

## ⏳ No Implementado — 26 ítems restantes

### 🚨 Crítico pendiente (1)

| # | Ítem | Razón de no implementación | Prerequisito |
|---|---|---|---|
| **#2** | Turnos en sidebar | Afecta ~30 vistas; ideal combinar con #14 (layout compartido) | Decisión: ¿hacer #14 primero o agregar a 30 archivos individuales? |

### 🟠 Arquitectura y Deuda Técnica (7)

| # | Ítem | Complejidad | Dependencias |
|---|---|---|---|
| **#14** | Extraer layout a componentes compartidos (`core/Views/`) | **ALTA** — Cambio masivo en ~30 vistas | Ninguna; resuelve #2 automáticamente |
| **#15** | Crear clases de Servicio para 9 módulos faltantes | **ALTA** — 9 clases nuevas | Seguir patrón de `GestorNacimientos` |
| **#17** | Consolidar clases Core duplicadas (`Audit.php` vs `Auditoria.php`, `Catalogo.php` vs `Catalogs.php`) | Media | Verificar que `Audit.php` no tiene dependencias |
| **#18** | Unificar sistema de migraciones (PHP sueltos → SQL numerados) | Media | Ninguna |
| **#19** | Exportación Excel para módulos `curp` y `peticiones` | Media | Seguir patrón existente |
| **#20** | Panel de Usuarios a DataTables server-side | Media | Crear `users_data.php` |
| **#21** | Archivar tabla auditoría legacy `bitacora_auditoria` | Baja | Verificar ausencia de dependencias |

### 🟡 Frontend/UX (4)

| # | Ítem | Complejidad |
|---|---|---|
| **#23** | Eliminar JavaScript duplicado en formularios (TomSelect, uppercase, submit AJAX) | Baja |
| **#24** | Extraer CSS inline a hojas de estilo (`login.php`, `turnos.php`, `validate.php`) | Baja |
| **#25** | Estandarizar feedback post-guardado (Toast unificado) | Baja |
| **#26** | Mejorar accesibilidad A11y (aria-labels, `<label for>`, contraste) | Baja |

### 📄 Documentación (4)

| # | Ítem | Descripción |
|---|---|---|
| **#29** | Actualizar `docs/esquema_bd.md` | Agregar tablas `turnos`, `peticion_rapida`, `peticiones_ventanilla`, rol COORDINADOR, permisos nuevos |
| **#30** | Actualizar `GUIA_DESARROLLADOR.md` | Documentar rol COORDINADOR, 3 banderas nuevas, servicios nuevos |
| **#31** | Actualizar `CONTEXTO.md` | Reflejar fases 10-18, v1.5.0 y v1.5.1 |
| **#32** | Completar `docs/api_referencia.md` | Endpoints de peticion_rapida (13), turnos (5), pantallas públicas |

### 🔵 Roadmap y Mejoras Futuras (10)

| # | Ítem | Complejidad |
|---|---|---|
| **#33** | CI/CD con GitHub Actions | Media |
| **#34** | Firma electrónica avanzada en actas | Alta |
| **#35** | Observabilidad y telemetría (Sentry/Monolog) | Media |
| **#36** | Portabilidad — ruta PHP hardcodeada (`PHP_BINARY`) | Baja |
| **#37** | Credenciales fallback en `Database.php` | Baja |
| **#38** | Expandir suite de pruebas (unitarias + E2E) | Media |
| **#39** | Localizar todas las dependencias CDN restantes | Baja |
| **#40** | Rutas OpenSSL hardcodeadas en `FirmaElectronicaService` | Baja |
| **#41** | Agregar Content-Security-Policy header (ya parcial en v1.5.1) | Baja |
| **#42** | Migrar `usuarios.php` a DataTables (mismo que #20) | Media |

---

## 🗺️ Plan de Continuación Recomendado

### Fase A — Arquitectura Base (prioridad alta)

> Prerequisito: completar las 3 acciones pendientes del usuario.

| Orden | Ítems | Descripción | Impacto |
|---|---|---|---|
| **A.1** | **#14 + #2** | Extraer layout a `core/Views/` (header, sidebar, navbar, footer) y agregar turnos al sidebar | Resuelve el bug #2, elimina duplicación en ~30 archivos, facilita todos los cambios futuros de UI |
| **A.2** | **#17** | Consolidar clases Core duplicadas | Limpia código muerto, reduce confusión |
| **A.3** | **#18** | Unificar migraciones PHP → SQL numerados | Estandariza el sistema de migraciones |

### Fase B — Servicios y Funcionalidad (prioridad media)

| Orden | Ítems | Descripción | Impacto |
|---|---|---|---|
| **B.1** | **#15** | Crear clases de Servicio para 9 módulos | Mejora testabilidad y separación de responsabilidades |
| **B.2** | **#19** | Exportación Excel para `curp` y `peticiones` | Completa funcionalidad de exportación |
| **B.3** | **#20/#42** | Panel de Usuarios a DataTables | Homologa con el resto del sistema |

### Fase C — Calidad de Código y UX (prioridad media-baja)

| Orden | Ítems | Descripción |
|---|---|---|
| **C.1** | **#23** | Eliminar JS duplicado en formularios |
| **C.2** | **#24** | Extraer CSS inline |
| **C.3** | **#25** | Estandarizar feedback post-guardado |
| **C.4** | **#26** | Mejoras de accesibilidad |
| **C.5** | **#21** | Archivar tabla auditoría legacy |

### Fase D — Documentación (puede hacerse en paralelo)

| Orden | Ítems |
|---|---|
| **D.1** | **#29** — esquema_bd.md |
| **D.2** | **#30** — GUIA_DESARROLLADOR.md |
| **D.3** | **#31** — CONTEXTO.md |
| **D.4** | **#32** — api_referencia.md |

### Fase E — Roadmap Futuro (cuando las fases A-D estén completas)

| Orden | Ítems | Descripción |
|---|---|---|
| **E.1** | **#36, #37, #40** | Portabilidad (rutas PHP, credenciales, OpenSSL) |
| **E.2** | **#38** | Expandir suite de pruebas |
| **E.3** | **#33** | CI/CD con GitHub Actions |
| **E.4** | **#35** | Observabilidad (Sentry/Monolog) |
| **E.5** | **#39, #41** | CDN restantes y CSP refinada |
| **E.6** | **#34** | Firma electrónica avanzada |

---

## 🤖 Delegación Actualizada (post v1.5.1)

### ✅ Seguras para delegar a DeepSeek:

| # | Tarea | Complejidad |
|---|---|---|
| 23 | Eliminar JS duplicado en `create.php` | Baja |
| 24 | Extraer CSS inline a `style.css` | Baja |
| 25 | Estandarizar feedback post-guardado | Baja |
| 26 | Agregar aria-labels y mejoras A11y | Baja |
| 29-32 | Actualización de 4 documentos | Media |
| 36 | Reemplazar ruta PHP hardcodeada por `PHP_BINARY` | Trivial |
| 37 | Eliminar credenciales fallback en Database.php | Trivial |
| 40 | Configurar rutas OpenSSL desde `.env` | Baja |

### ⚠️ Delegables con revisión:

| # | Tarea | Notas |
|---|---|---|
| 15 | Crear clases de servicio (9 módulos) | Seguir patrón de `GestorNacimientos` exacto |
| 17 | Consolidar clases Core duplicadas | Verificar ausencia de dependencias antes |
| 18 | Unificar migraciones | Conversión mecánica PHP→SQL |
| 19 | Export Excel para curp y peticiones | Seguir patrón existente |
| 20/42 | Usuarios.php a DataTables | Crear `users_data.php` |
| 21 | Archivar tabla auditoría legacy | Verificar dependencias |
| 38 | Expandir tests | Seguir patrones de `tests/Unit/` |

### 🔒 Requieren supervisión directa:

| # | Tarea | Razón |
|---|---|---|
| 2+14 | **Extraer layout compartido + turnos en sidebar** | Cambio arquitectónico mayor (~30 archivos), define la base para todo lo demás |
| 33 | CI/CD | Configuración de infraestructura |
| 34 | Firma electrónica | Complejidad criptográfica y legal |
| 35 | Observabilidad | Decisiones de infraestructura |
