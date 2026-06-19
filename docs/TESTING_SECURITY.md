# Políticas de Pruebas y Seguridad (Testing & Security)

Este documento establece las normativas estrictas de seguridad, calidad de código y validación que deben aplicarse en el desarrollo del ERP de la Dirección de Registro Civil.

## 1. Seguridad en Base de Datos
* **Prevención de Inyección SQL:** Es estrictamente obligatorio el uso de **PDO (PHP Data Objects)** con **Prepared Statements** (Sentencias Preparadas) para absolutamente todas las consultas (`SELECT`, `INSERT`, `UPDATE`, `DELETE`).
* **Reglas de Tipos:** Los campos largos como las "Líneas de Pago" (17 a 20 dígitos) jamás deben tratarse, castearse o validarse como enteros (`INT`). Deben tratarse exclusivamente como cadenas de texto (`VARCHAR` en la BD y `string` en PHP).
* **Manejo de Contraseñas:** Se debe utilizar `password_hash()` con el algoritmo `PASSWORD_DEFAULT` para almacenar contraseñas, y `password_verify()` para la validación de login.

## 2. Validación y Sanitización (XSS y CSRF)
* **Cross-Site Scripting (XSS):** Todo dato proveniente de la base de datos que se muestre en el frontend (vistas HTML/PHP) debe ser sanitizado utilizando `htmlspecialchars($dato, ENT_QUOTES, 'UTF-8')`.
* **Cross-Site Request Forgery (CSRF):** Todos los formularios que realicen mutaciones en el sistema (Altas, Bajas, Modificaciones) deben incluir un Token CSRF que será validado en el controlador correspondiente.

## 3. Reglas de Negocio Estrictas
* **Transformación a Mayúsculas:** Conforme a las reglas institucionales, todos los nombres, descripciones y estados deben registrarse en la base de datos en **MAYÚSCULAS**. En el backend se aplicará `mb_strtoupper($cadena, 'UTF-8')` antes de la persistencia.
* **Manejo de Fechas:** Las lógicas de cálculo de fechas (ej. sumatoria de 15 días a la fecha de trámite) deben ser consistentes e ignorar días inhábiles si se llegase a solicitar, utilizando la clase `DateTime` de PHP para evitar desfasajes.

## 4. Revisión de Código (Code Review)
* Antes de dar por finalizado un módulo, se debe verificar que:
  * No existan consultas a base de datos dentro de las vistas (`index.php` de módulos). Toda la lógica de datos debe estar separada.
  * Los mensajes de error retornados por el backend (ej. en llamadas AJAX) no deben exponer la estructura de la tabla, ni el query ejecutado. Deben retornar mensajes genéricos (ej. "Error al procesar la solicitud") mediante SweetAlert2 en el frontend.
