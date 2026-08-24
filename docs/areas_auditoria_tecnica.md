# Áreas de Auditoría Técnica Pendientes — ERP DRC

Tras completar las 5 fases de modernización UI/UX (P0–P4), estas son las áreas estratégicas que merecen revisión exhaustiva, ordenadas por **impacto y urgencia** para un sistema gubernamental que maneja datos sensibles de ciudadanos.

---

## 🔴 Área 1: Seguridad y Endurecimiento (URGENTE)

El propio proyecto ya tiene una auditoría estática documentada en [`SEGURIDAD_AUDITORIA.md`](file:///c:/xampp/htdocs/DRC/docs/analisis/SEGURIDAD_AUDITORIA.md) con **hallazgos críticos no remediados**. Esta es, sin duda, la prioridad más alta.

### Hallazgos Críticos Documentados (Sin Resolver)

| # | Vulnerabilidad | Severidad | Archivo(s) |
|---|---|---|---|
| 1 | **Inyección SQL en `ORDER BY`** — `$columnSortOrder` viene directo de `$_GET` sin whitelist en 8 endpoints DataTables | 🔴 CRÍTICA | `peticiones/data.php`, `nacimientos/data.php`, `defunciones/data.php`, `inexistencias/data.php`, `foraneas/data.php`, `ciudadanos/data.php`, `auditoria_data.php`, `errores_data.php` |
| 2 | **Clave AES-256 fallback hardcodeada** — Si falta `ENCRYPTION_KEY` en `.env`, todas las CURPs se descifran con clave pública conocida | 🔴 CRÍTICA | [`Encryption.php:25`](file:///c:/xampp/htdocs/DRC/core/Encryption.php) |
| 3 | **Migraciones web sin autenticación** — Ejecutables vía navegador, permiten `ALTER TABLE` y re-encriptación de CURPs | 🔴 CRÍTICA | `docs/migration_*.php` |
| 4 | **CSRF roto o ausente** — 6 `save.php` validan con `!empty()` (cualquier string pasa); 5 módulos no tienen token | 🔴 CRÍTICA | 11 endpoints de guardado |
| 5 | **Bypass universal de autenticación** vía `?cron_token=` en cualquier URL | 🟠 ALTA | [`Auth.php:21`](file:///c:/xampp/htdocs/DRC/core/Auth.php) |
| 6 | **XSS almacenado en Reportes** — `reportes/data.php` devuelve datos sin escapar | 🟠 ALTA | [`reportes/data.php`](file:///c:/xampp/htdocs/DRC/modules/reportes/data.php) |
| 7 | **Login sin rate-limiting** — Fuerza bruta directa posible | 🟠 ALTA | [`auth.php`](file:///c:/xampp/htdocs/DRC/public/auth.php) |
| 8 | **`validate.php` filtra PII** con token base64 no firmado y enumerable | 🟠 ALTA | [`validate.php`](file:///c:/xampp/htdocs/DRC/public/validate.php) |
| 9 | **Archivos `.xlsx` con PII** en historial de Git y `exports/` descargable sin login | 🟠 ALTA | Historial Git + `public/exports/` |
| 10 | **Cookies de sesión inseguras** — Sin `HttpOnly`, `Secure`, ni `SameSite` | 🟡 MEDIA | Toda la aplicación |

> [!CAUTION]
> Las vulnerabilidades 1–4 son explotables **hoy** y permitirían extracción completa de la base de datos (incluyendo hashes de contraseñas) o toma de cuentas vía CSRF. Es la área de mayor riesgo del proyecto.

### Alcance de la Auditoría Propuesta
- Remediar las 10 prioridades documentadas en `SEGURIDAD_AUDITORIA.md` §8.
- Añadir headers de seguridad HTTP (`X-Frame-Options`, `Content-Security-Policy`, `X-Content-Type-Options`).
- Endurecer configuración de cookies de sesión (`session_set_cookie_params`).
- Sanitizar mensajes de excepción en respuestas JSON (`$e->getMessage()` → mensajes genéricos).
- Whitelist estricta del campo `rol` en `update_usuario.php`.

---

## 🟠 Área 2: Cobertura de Pruebas y QA

La suite de pruebas actual es **muy limitada** para un sistema ERP gubernamental en pre-deploy.

### Estado Actual

| Tipo | Cobertura | Detalle |
|---|---|---|
| **Tests Unitarios (PHPUnit)** | 8 archivos | Solo cubren: `Utils`, `Database`, `Encryption`, `Auditoria`, `Queue`, `Deadlock`, `PdfGenerator`, `FirmaElectronica` |
| **Tests E2E (Playwright)** | 1 archivo | Solo cubre: Login + formulario de inexistencias |
| **Tests de Integración** | 0 | No existen |
| **Tests de Seguridad** | 0 | No existen |

### Brechas Críticas
- ❌ **0 tests para 9 de los 13 módulos de negocio** (matrimonios, divorcios, reconocimientos, inscripciones, foráneas, CURP, actas_locales, turnos, peticiones).
- ❌ **0 tests de integración** del flujo completo de guardado (Controller → Service → DB → Audit).
- ❌ **0 tests del sistema de permisos** (11 banderas booleanas sin validación automatizada).
- ❌ **0 tests del Rate Limiter** bajo carga.
- ❌ **0 tests de la capa de caché** (Redis → Memcached → Filesystem fallback).
- ❌ **Solo 1 prueba E2E** — sin cobertura de módulos de captura, exportaciones, gestión de usuarios ni modo oscuro.

### Alcance de la Auditoría Propuesta
- Diseñar una estrategia de testing por capas (Unit → Integration → E2E).
- Añadir tests unitarios para los Gestores de negocio faltantes.
- Crear tests de integración para los flujos CRUD críticos.
- Expandir la suite E2E de Playwright a los módulos principales.
- Establecer umbrales mínimos de cobertura.

---

## 🟡 Área 3: Arquitectura y Service Layer

La capa de servicios de negocio está **incompleta**: solo 4 de 13 módulos tienen Gestores dedicados.

### Estado de Migración a Service Layer

| Módulo | ¿Tiene Gestor en `core/Services/`? | Lógica actual |
|---|---|---|
| Nacimientos | ✅ `GestorNacimientos.php` | Delegada a servicio |
| Defunciones | ✅ `GestorDefunciones.php` | Delegada a servicio |
| Inexistencias | ✅ `GestorInexistencias.php` | Delegada a servicio |
| Petición Rápida | ✅ `PeticionRapidaService.php` | Delegada a servicio |
| **Matrimonios** | ❌ | Lógica directa en `save.php` |
| **Divorcios** | ❌ | Lógica directa en `save.php` |
| **Reconocimientos** | ❌ | Lógica directa en `save.php` |
| **Inscripciones** | ❌ | Lógica directa en `save.php` |
| **Foráneas** | ❌ | Lógica directa en `save.php` |
| **CURP** | ❌ | Lógica directa en `save.php` |
| **Actas Locales** | ❌ | Lógica directa en `save.php` |
| **Turnos** | ❌ | Sin `save.php` |
| **Reportes** | ❌ | Generación en `data.php` |

### Otras Deudas Arquitectónicas
- **Sistema de permisos hardcodeado:** 11 columnas booleanas en tabla `usuarios` en lugar de RBAC relacional.
- **Worker monolítico:** `core/Worker.php` con ruta hardcodeada a `c:\xampp\php\php.exe` — no portable a Linux/Docker.
- **Doble tabla de auditoría:** Coexisten `bitacora_auditoria` (legacy) y `auditoria_logs` (nueva) sin consolidación.
- **Frontend con CDN externa:** Bootstrap, FontAwesome, DataTables cargados desde jsdelivr/cdnjs — punto único de fallo en redes gubernamentales aisladas.

### Alcance de la Auditoría Propuesta
- Migrar los 9 módulos faltantes al patrón Service Layer.
- Evaluar la viabilidad de un esquema RBAC relacional.
- Parametrizar la ruta del binario PHP vía `.env` (`PHP_BIN`).
- Consolidar las tablas de auditoría.
- Evaluar el empaquetamiento local de dependencias frontend (Vite/Webpack o copia local).

---

## 🟡 Área 4: Rendimiento y Base de Datos

### Hallazgos
- **Índices faltantes:** Las tablas de trámites (`nacimientos`, `defunciones`, `matrimonios`, etc.) carecen de índices compuestos para los filtros más frecuentes (`estatus + fecha_registro`, `usuario_registro + fecha`).
- **Sin purga de logs:** Las tablas `auditoria_logs` y `error_logs` crecen indefinidamente sin mecanismo de retención, particionamiento ni archivado.
- **Consultas `LIKE '%...%'`:** Búsquedas globales de DataTables realizan full-table scans en tablas grandes.
- **Caché en directorio web:** El directorio `cache/` con archivos serializados (`unserialize()`) está dentro del docroot con permisos `0777`.
- **Sin connection pooling:** Cada petición abre una nueva conexión PDO (aceptable para XAMPP, limitante para producción).

### Alcance de la Auditoría Propuesta
- Analizar y crear índices compuestos óptimos con `EXPLAIN` sobre las consultas más costosas.
- Diseñar política de retención y purga automatizada de logs.
- Mover `cache/` fuera del docroot o restringirlo con `.htaccess`.
- Evaluar la sustitución de `unserialize()` por `json_decode()` en la caché de archivos.
- Optimizar o reemplazar las búsquedas `LIKE '%...%'` con Full-Text Search donde corresponda.

---

## 🔵 Área 5: Observabilidad y Operaciones

### Estado Actual
- **Sin monitoreo de excepciones** en producción (ni Sentry, ni Monolog estructurado).
- **Sin alertas** ante errores críticos — solo se registran silenciosamente en la tabla `error_logs`.
- **Sin métricas de rendimiento** (tiempos de respuesta, queries lentas, uso de caché).
- **Sin health-check endpoint** para monitoreo de infraestructura.
- **Logs planos** sin formato estructurado (JSON) para herramientas de análisis.

> [!NOTE]
> El propio `ROADMAP.md` §6 ya identifica esto como "Próximo Paso": *"Integración de herramientas de tracking de excepciones en producción (como Sentry o Monolog estructurado)"*.

### Alcance
- Integrar Monolog con formateo JSON para logs estructurados.
- Crear un endpoint `/api/health` para verificación de disponibilidad.
- Establecer alertas por email o webhook ante errores críticos.
- Implementar logging de queries lentas.

---

## 🔵 Área 6: Documentación Técnica y Onboarding

### Estado Actual
- La documentación es **buena** en cuanto a contexto y arquitectura (`CONTEXTO.md`, `ROADMAP.md`, `GUIA_DESARROLLADOR.md`).
- **Pero:** La `api_referencia.md` no documenta los códigos de error, payloads de request, ni ejemplos de respuesta.
- **Falta:** Documentación de los flujos de negocio complejos (ej. el ciclo de vida completo de una petición de ventanilla).
- **Falta:** Diagrama ER actualizado de la base de datos.
- **Falta:** Runbook de operaciones (despliegue, backup, restauración, rotación de llaves).

---

## Resumen y Priorización Recomendada

| Prioridad | Área | Esfuerzo | Impacto |
|---|---|---|---|
| 🔴 **P0** | **Seguridad y Endurecimiento** | Alto | Crítico — previene brechas de datos y cumplimiento normativo |
| 🟠 **P1** | **Cobertura de Pruebas** | Alto | Alto — estabiliza el sistema ante cambios futuros |
| 🟡 **P2** | **Arquitectura / Service Layer** | Medio-Alto | Alto — reduce deuda técnica y facilita mantenimiento |
| 🟡 **P3** | **Rendimiento y BD** | Medio | Medio — mejora la experiencia en producción |
| 🔵 **P4** | **Observabilidad** | Medio | Medio — habilita operaciones confiables |
| 🔵 **P5** | **Documentación** | Bajo-Medio | Medio — facilita onboarding y soporte |

> [!IMPORTANT]
> **¿Cuáles de estas áreas te gustaría que abordemos?** Puedo preparar un plan de implementación detallado para cualquiera de ellas. La recomendación fuerte es comenzar por **Seguridad (P0)** dado que hay vulnerabilidades críticas documentadas y sin resolver.
