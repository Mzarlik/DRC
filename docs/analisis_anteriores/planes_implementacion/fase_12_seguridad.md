# Seguridad y Criptografía (Fase 12)

Este plan detalla la implementación de controles de seguridad estrictos para cumplir con las normativas gubernamentales en el ERP DRC, cubriendo encriptación de datos sensibles en reposo (AES-256-CBC determinista), limitación de peticiones (Rate Limiting) en endpoints críticos y mitigación de secuestro de sesiones (Rotación de Sesión).

## User Review Required

> [!IMPORTANT]
> **Modificación del Tamaño de la Columna `curp`**
> La columna `curp` originalmente estaba configurada como `VARCHAR(18)`. Debido a que la encriptación AES-256-CBC codificada en base64 (incluyendo el vector de inicialización de 16 bytes) requiere aproximadamente 64 caracteres, se debe ejecutar un cambio en el esquema de la base de datos para ampliar el tamaño de esta columna a `VARCHAR(255)`. Este ajuste ya está contemplado en el script de migración.

---

## Proposed Changes

### 1. Encriptación en Reposo (AES-256-CBC Determinista)
Garantizar la confidencialidad de la CURP de los ciudadanos directamente en la base de datos.

#### [NEW] [Encryption.php](file:///c:/xampp/htdocs/DRC/core/Encryption.php)
- Clase helper `Encryption` en el espacio de nombres `Core`.
- Implementa encriptación simétrica AES-256-CBC con clave derivada de `.env` (`ENCRYPTION_KEY`).
- Genera un IV determinista (derivado mediante HMAC-SHA-256 de los datos planos) permitiendo búsquedas exactas e indexación en base de datos.
- Proporciona métodos `encrypt($data)` y `decrypt($data)` con fallback seguro para datos no cifrados (retrocompatibilidad).

#### [NEW] [migration_encrypt.php](file:///c:/xampp/htdocs/DRC/docs/migration_encrypt.php)
- Script de migración de base de datos.
- Modifica la columna `curp` de la tabla `ciudadanos` a `VARCHAR(255)`.
- Recorre y encripta de forma segura todos los CURPs existentes en texto plano.

#### [MODIFY] [save.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/save.php)
- Encriptar la CURP con `Core\Encryption::encrypt()` antes de la inserción o actualización.

#### [MODIFY] [data.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/data.php)
- Cambiar la búsqueda por `LIKE` en CURP por una búsqueda exacta (`= :search_curp`) sobre la CURP encriptada si el término coincide con el formato.
- Desencriptar el valor de la columna `curp` mediante `Core\Encryption::decrypt()` en la salida JSON.

#### [MODIFY] [search.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/search.php)
- Si el término de búsqueda coincide con un formato de CURP válido, realizar búsqueda exacta `=` sobre el campo encriptado.
- Desencriptar CURPs en la lista de resultados Select2.

#### [MODIFY] [data.php](file:///c:/xampp/htdocs/DRC/modules/actas_locales/data.php)
- Adaptar la búsqueda para usar coincidencia exacta en campos de CURP encriptada.
- Desencriptar `curp_1` y `curp_2` en la salida de DataTables.

#### [MODIFY] [get_details.php](file:///c:/xampp/htdocs/DRC/modules/actas_locales/get_details.php)
- Desencriptar automáticamente cualquier campo que contenga la palabra `curp` antes de enviarlo a la interfaz.

#### [MODIFY] [pdf.php](file:///c:/xampp/htdocs/DRC/modules/actas_locales/pdf.php)
- Desencriptar los campos de CURP recuperados de la base de datos.

#### [MODIFY] [.env.example](file:///c:/xampp/htdocs/DRC/.env.example) y [.env](file:///c:/xampp/htdocs/DRC/.env)
- Definir la variable `ENCRYPTION_KEY` para la clave de encriptación simétrica.

---

### 2. Limitación de Peticiones (Rate Limiting)
Evitar raspado (scraping) de padrón en endpoints de ciudadanos.

#### [NEW] [RateLimiter.php](file:///c:/xampp/htdocs/DRC/core/RateLimiter.php)
- Clase `RateLimiter` en el espacio de nombres `Core`.
- Método estático `check($endpoint, $maxRequests, $decaySeconds)`.
- Utiliza la capa de caché global (`Cache::get` y `Cache::set`) para almacenar contadores IP y ventana de tiempo por cliente.

#### [MODIFY] [search.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/search.php)
- Aplicar un límite de 30 peticiones por minuto. Retornar código HTTP `429 Too Many Requests` si se excede.

#### [MODIFY] [data.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/data.php)
- Aplicar un límite de 60 peticiones por minuto para la carga y filtrado del catálogo de ciudadanos.

---

### 3. Rotación de ID de Sesiones (Mitigación de Hijacking)
Forzar la regeneración de sesión de PHP en cambios de contexto de seguridad.

#### [MODIFY] [auth.php](file:///c:/xampp/htdocs/DRC/public/auth.php)
- Invocar `session_regenerate_id(true)` inmediatamente tras el inicio de sesión exitoso.

#### [MODIFY] [update_perfil.php](file:///c:/xampp/htdocs/DRC/public/update_perfil.php)
- Regenerar el ID de sesión cuando un usuario cambia su propia contraseña.

#### [MODIFY] [update_usuario.php](file:///c:/xampp/htdocs/DRC/public/update_usuario.php)
- Regenerar el ID de sesión si un administrador modifica el rol o estatus del usuario que se encuentra actualmente logueado.

---

## Verification Plan

### Automated Verification
- Comprobar la ausencia de errores de sintaxis en todos los archivos con `php -l`.
- Validar la base de datos tras la migración.

### Manual Verification
1. **Encriptación de CURP**:
   - Registrar un nuevo ciudadano con una CURP de prueba.
   - Acceder al gestor de base de datos y constatar que el campo `curp` contenga una cadena encriptada base64 de 64 caracteres.
   - Verificar que en el catálogo y Select2 se muestre la CURP en texto plano correctamente desencriptada.
2. **Rate Limiting**:
   - Lanzar peticiones rápidas repetidas al endpoint `modules/ciudadanos/search.php?q=TEST`.
   - Constatar que a la petición número 31 se reciba un código de respuesta HTTP **429** con el mensaje de excedido.
3. **Regeneración de Sesión**:
   - Iniciar sesión y comprobar en las cookies del navegador el valor del ID de sesión (`PHPSESSID`).
   - Modificar la contraseña en el perfil del usuario y validar que el valor de `PHPSESSID` haya cambiado.
