# Políticas de Pruebas y Seguridad (ERP DRC)

Este documento establece las normativas de seguridad implementadas en el sistema para proteger la información ciudadana y la integridad del servidor.

## 1. Protección contra Inyecciones SQL (SQLi)
Todas las consultas a la base de datos se realizan a través de la clase Singleton `Database` ubicada en `/core/Database.php`.
* **Regla estricta:** Está prohibido concatenar variables directamente en las sentencias SQL. Siempre se deben usar Sentencias Preparadas (`Prepared Statements`) de PDO con `bindParam` o arreglos en el método `execute()`.

## 2. Protección contra Cross-Site Scripting (XSS)
Toda salida de datos proveniente de la base de datos hacia la interfaz (HTML o DataTables) debe ser sanitizada.
* En los archivos `data.php` de los módulos (ej. Nacimientos, Defunciones, etc.), se utiliza la función `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')` antes de enviar la carga útil JSON al frontend.

## 3. Protección de Servidor (Apache)
Se han implementado medidas activas en la configuración local mediante archivos `.htaccess` para evitar el descubrimiento de información:
1. **Prevención de Directory Listing:** El archivo `.htaccess` en la raíz contiene `Options -Indexes`. Esto evita que el navegador liste los archivos de los directorios como `/assets` o la carpeta raíz si no hay un `index.php` explícito.
2. **Redirección de Entrada Segura:** Un `index.php` en la raíz del proyecto redirige de inmediato a `/public/index.php`, sirviendo como única puerta de entrada al dashboard.
3. **Bloqueo de Directorios Sensibles:** Las carpetas de código backend puro (`/core/`) y de diseño/respaldos (`/docs/`) tienen un archivo `.htaccess` con directivas `Require all denied` (o `Deny from all`). Nadie puede acceder a `Database.php` o `database.sql` escribiendo la URL directamente en el navegador.

## 4. Protección Cross-Site Request Forgery (CSRF)
Todos los formularios (ej. `create.php`) implementan un token CSRF generado con `bin2hex(random_bytes(32))` que viaja como input oculto y se valida obligatoriamente en el archivo de guardado correspondiente (`save.php`).

---
**Nota para Desarrolladores:**
Cualquier nuevo módulo añadido al sistema debe apegarse rigurosamente a estos 4 puntos antes de ser liberado a producción.
