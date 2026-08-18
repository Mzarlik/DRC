# AGENTS.md — Instrucciones para agentes de IA

Guía para agentes/colaboradores de IA que trabajen en este repositorio.

---

## 1. Proyecto

ERP modular en **PHP 8.2+ sin framework** para la Dirección de Registro Civil (XAMPP/Windows). MVC-like: `core/` (infraestructura + servicios PSR-4 `Core\`), `modules/` (13 módulos de negocio), `public/` (docroot web).

**Regla de oro:** respetar la arquitectura existente. Copiar patrones de módulos ya implementados (ej. `modules/nacimientos`) antes de escribir código nuevo.

---

## 2. Reglas de negocio inmutables

1. Nombres, observaciones y estados → **MAYÚSCULAS** (`strtoupper` backend).
2. Líneas de pago, folios y códigos largos → **SIEMPRE strings** (VARCHAR). Nunca int.
3. Trámites se vinculan a `ciudadanos` por FK + búsqueda AJAX (nunca nombres a mano).
4. Defunción ⇒ `estado_vital = FINADO` (transaccional).
5. Folios secuenciales con `Database::generateFolio()`.
6. Fecha de llegada = `calcularFechaLlegada()` (`Core\Utils`), días en `configuracion`.

## 3. Reglas de código

- Todo endpoint/controlador nuevo: `Auth::check()` + `Auth::checkPermission('<bandera>')`.
- Todo POST de guardado: token CSRF (`Auth::generateCSRF()` en form / `validateCSRF()` al recibir).
- Toda operación de escritura: `Auditoria::logAccion()`.
- CURP: cifrar con `Core\Encryption::encrypt()` y buscar cifrado. **No** romper compatibilidad de `decrypt()`.
- Respuestas de módulos: JSON `{status:"success"|"error", message}`; DataTables legacy en `data.php` (`{draw, iTotalRecords, iTotalDisplayRecords, aaData}`).
- Lógica de negocio compleja → clase en `core/Services/` (autoload automático, no editar composer.json salvo dependencias).
- Sin comentarios innecesarios en el código; documentar cambios de comportamiento en `docs/versions.md`.

## 4. Comandos de verificación

```bash
vendor\bin\phpunit              # tests unitarios (obligatorio tras tocar Core\Utils, fechas, folios)
composer dump-autoload -o       # tras agregar/quitar clases en core/
php core/Worker.php             # procesar cola de exportaciones
npm run test:e2e                # E2E Playwright (requiere servidor corriendo)
```

No ejecutar migraciones (`docs/migration_*.php`) sin confirmar que el usuario lo pide.

## 5. Documentación de referencia

| Se necesita | Leer |
|---|---|
| Contexto y fases | `CONTEXTO.md`, `ROADMAP.md` |
| Cómo agregar módulo | `GUIA_DESARROLLADOR.md` §4 |
| Estructura de BD | `docs/esquema_bd.md` |
| Endpoints | `docs/api_referencia.md` |
| Seguridad | `TESTING_SECURITY.md`, `docs/analisis/SEGURIDAD_AUDITORIA.md` |
| Instalación/test local | `README.md` |
| Stack y limitaciones | `docs/analisis/STACK.md` (ojo: worker hardcodea `c:\xampp\php\php.exe`) |

## 6. Advertencias

- `.htaccess` bloquea desde la web: `docs/`, `core/`, `.env`, `composer.*`, `.git*` — no remover protecciones.
- Credenciales y llaves viven en `.env` (NO committear; `.env.example` es solo plantilla).
- `Auth::check()` admite `?cron_token=<CRON_SECRET>`: no exponerlo ni loguearlo.
- Endpoints conocidos con deuda técnica (no "arreglar" sin pedir instrucción explícita): save.php validan CSRF solo por presencia; exportaciones son GET mutantes; ruta de PHP hardcodeada en Worker.php.