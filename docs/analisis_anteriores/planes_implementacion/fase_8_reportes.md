# Fase de Emisión Documental y Reportes (Fase 8)

Este plan detalla la integración de librerías externas para la generación de documentos oficiales inmutables (PDF), códigos QR dinámicos para validación pública, y la exportación de datos precisos a Excel.

## User Review Required

> [!IMPORTANT]
> **Instalación de Dependencias**
> Dado que Composer no está instalado globalmente en el servidor, descargaré el ejecutable local `composer.phar` en la raíz del proyecto para instalar las siguientes librerías dentro de la carpeta `vendor/`:
> 1. `tecnickcom/tcpdf` (Para generación de PDFs).
> 2. `chillerlan/php-qrcode` (Para códigos QR sin dependencias complejas).
> 3. `phpoffice/phpspreadsheet` (Para reportes Excel precisos).

## Open Questions

> [!WARNING]
> ¿Deseas que el PDF generado permita selección de texto o prefieres que se bloquee completamente (cifrado) para evitar que los usuarios lo alteren con editores de PDF antes de imprimirlo? Por defecto, lo configuraré con permisos restringidos de modificación mediante TCPDF.

## Proposed Changes

### 1. Inicialización de Librerías (Composer)
- Descarga de `composer.phar`.
- Ejecución de `php composer.phar require tecnickcom/tcpdf chillerlan/php-qrcode phpoffice/phpspreadsheet`.

---

### 2. Motor de PDFs Oficiales y QR (Actas Locales)
Crear un endpoint para renderizar las actas almacenadas en un formato bloqueado e imprimible.

#### [NEW] [modules/actas_locales/pdf.php](file:///c:/xampp/htdocs/DRC/modules/actas_locales/pdf.php)
- Archivo encargado de recibir `tipo` y `id`.
- Instancia `chillerlan\QRCode\QRCode` para generar una imagen base64 apuntando a `http://localhost/DRC/public/validate.php?token=TOKEN_AQUI`.
- Instancia `TCPDF`, carga la información de la base de datos, incrusta el QR en una esquina, y dibuja el marco oficial del acta.
- Aplica `SetProtection()` para evitar modificaciones al archivo.

#### [MODIFY] [modules/actas_locales/index.php](file:///c:/xampp/htdocs/DRC/modules/actas_locales/index.php)
- En el modal de detalles (`SweetAlert2`), agregar un botón secundario: **"Imprimir / Descargar PDF"** que abra una nueva pestaña apuntando a `pdf.php`.

---

### 3. Validación Pública de Actas (Puvlika)
El destino del código QR impreso en el acta.

#### [NEW] [public/validate.php](file:///c:/xampp/htdocs/DRC/public/validate.php)
- Endpoint de acceso libre (sin requerir sesión).
- Recibe un identificador único (puede ser un hash seguro del registro, o el `registro_id` encriptado).
- Muestra una pantalla limpia de "Validación Oficial Puvlika" con el tipo de acta, número, nombres de los involucrados y fecha de registro.

---

### 4. Exportación Exacta a Excel (Inexistencias)
Descarga de reportes sin corrupción de formatos (evitando que Excel convierta `123456789012345678` en notación científica).

#### [NEW] [modules/inexistencias/export_excel.php](file:///c:/xampp/htdocs/DRC/modules/inexistencias/export_excel.php)
- Archivo que consulta todas las constancias generadas usando los mismos filtros de `index.php`.
- Instancia `PhpOffice\PhpSpreadsheet\Spreadsheet`.
- Al escribir la columna `Línea de Pago`, define el tipo de celda explícitamente como **String/Texto** (`\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING`) para proteger el formato.
- Fuerza la descarga en formato `.xlsx`.

#### [MODIFY] [modules/inexistencias/index.php](file:///c:/xampp/htdocs/DRC/modules/inexistencias/index.php)
- Añadir un botón verde de **"Exportar a Excel"** al lado de "Nuevo Registro" que redirija a `export_excel.php` pasándole los filtros actuales.

---

## Verification Plan
### Manual Verification
1. Entrar a `modules/actas_locales/`, visualizar los detalles de un acta y dar clic en "Imprimir". Se abrirá un PDF cerrado.
2. Escanear el código QR del PDF con un teléfono móvil, el cual redirigirá a `public/validate.php` mostrando la validez.
3. Ir a `modules/inexistencias/` y dar clic en "Exportar a Excel". Abrir el archivo `.xlsx` y verificar que las líneas de pago no estén corruptas.
