# Plan y Corrección: Generación y Descarga de Reportes Excel (.xlsx)

## 1. Causa del Error ".php"
- **Invocación del Worker CLI en Windows:** Cuando PHP corre bajo Apache, `PHP_BINARY` devuelve `httpd.exe`, y en el comando Windows `start /B` el primer parámetro entre comillas se toma como título de ventana si no se antepone `""`.
- Como consecuencia, el worker asíncrono no se ejecutaba automáticamente desde la web, dejando el trabajo en estado `pending`.
- Al intentar descargar el archivo que aún no existía en disco, el endpoint `download_export.php` devolvía un JSON de error HTTP 404/500, el cual el navegador descargaba como un archivo `.php`.

## 2. Solución Aplicada
1. **Lanzador Asíncrono Robusto (`core/Jobs.php`):**
   - Resolución prioritaria de `C:\xampp\php\php.exe`.
   - Comando Windows compatible: `cmd.exe /c start /B "" [php.exe] [Worker.php] > NUL 2>&1`.
2. **Descarga Segura con Headers Binarios (`public/api/download_export.php`):**
   - Headers estrictos `Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` y `Content-Disposition: attachment; filename="Reporte_....xlsx"`.
   - Validación de extensiones permitidas (`.xlsx`, `.csv`, `.pdf`).
3. **Limpieza de Menú Duplicado:**
   - Se removió el bloque residual `<!-- Ventanilla (Petición Rápida y Turnos) -->` en las 26 vistas, eliminando el segundo menú redundante "Ventanilla".
