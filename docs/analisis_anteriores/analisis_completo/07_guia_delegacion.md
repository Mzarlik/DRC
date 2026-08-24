# 🤖 Guía de Delegación a DeepSeek

Esta guía clasifica las 42 tareas del análisis completo según el nivel de riesgo y supervisión requerida para delegar a DeepSeek.

---

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

---

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

---

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
