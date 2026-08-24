# Plan de Implementación: Modal Universal de Registro Rápido de Ciudadanos

Este plan detalla la arquitectura e implementación del **Modal Universal de Registro Rápido de Ciudadanos** para eliminar las fricciones operativas cuando una persona no está previamente en el padrón, aplicable a todos los formularios de captura del ERP DRC.

---

## 1. Diagnóstico y Arquitectura

### A. Situación Actual
Cuando un ciudadano acude al Registro Civil a tramitar un acta (Nacimiento, Matrimonio, Divorcio, Defunción, Reconocimiento, Inscripción), una constancia (Inexistencia, Foránea), un ticket de seguimiento o un servicio de **Trámites CURP** (Alta, Baja, Corrección), el sistema exige seleccionar al ciudadano por búsqueda AJAX. Si la persona no está registrada:
- El operador se ve obligado a abandonar el formulario actual, perdiendo los datos ya capturados.
- Debe ir a `modules/ciudadanos/create.php`, dar de alta a la persona y regresar a comenzar el trámite desde cero.

### B. Solución Arquitectónica Universal
1. **Modal de Captura Inyectado / Centralizado ([`assets/js/global.js`](file:///c:/xampp/htdocs/DRC/assets/js/global.js)):**
   - Inyección automática del modal `#modalQuickCiudadano` con diseño institucional (Nombre, Apellido Paterno, Apellido Materno, Sexo, Fecha de Nacimiento y CURP con validación interactiva).
   - Inyección automática de botones de acción rápida `[ + Registrar Ciudadano ]` en las etiquetas de los selectores de ciudadanos (`.select-citizen`, `#ciudadano_id`, `#padre_id`, `#madre_id`, `#contrayente_1_id`, `#contrayente_2_id`, `#reconocido_id`, etc.).

2. **Endpoint de Guardado AJAX ([`modules/ciudadanos/save.php`](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/save.php)):**
   - Respuestas enriquecidas en JSON: `{ status: "success", id: <ID>, nombre_completo: "...", curp: "...", text: "..." }`.
   - Cifrado seguro AES-256 de CURP, cálculo determinista de `curp_bindex` (Blind Index HMAC) y registro de auditoría (`Auditoria::logAccion`).

3. **Autoselección Reactiva sin Salir de la Pantalla:**
   - Al guardar en el modal, el backend retorna el nuevo registro.
   - El script agrega la opción inmediatamente al `TomSelect` correspondiente y **lo selecciona de forma automática**.
   - El modal se cierra suavemente, se muestra una notificación toast de éxito y el operador continúa llenando su acta sin perder ni un solo campo.

---

## 2. Aclaración del Centro de Notificaciones

Las notificaciones del sistema **no son un mockup ni textos estáticos hardcodeados**:
- [`public/api/notifications.php`](file:///c:/xampp/htdocs/DRC/public/api/notifications.php) consulta en tiempo real:
  1. Expedientes en seguimiento (`peticiones` con estatus ABIERTA/EN_PROGRESO).
  2. Tareas de exportación asíncronas del usuario (`jobs` con descarga directa `.xlsx`).
  3. Actas foráneas pendientes de cotejo (`foraneas`).
  4. Constancias de inexistencia pendientes (`inexistencias`).
  5. Trámites de mostrador del día (`peticiones_ventanilla`).
  6. Actividad reciente en el padrón (`ciudadanos` y `nacimientos` recién capturados).
- El generador de prueba CLI ([`docs/seed_mockup.php`](file:///c:/xampp/htdocs/DRC/docs/seed_mockup.php)) combina diccionarios probabilísticos de nombres, apellidos, municipios, fechas, CURPs y estados de trámite para poblar la base de datos de manera realista.

---

## 3. Archivos a Modificar

### Backend
- [modules/ciudadanos/save.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/save.php): Retornar el payload estructurado `{ status: "success", id, text, nombre_completo, curp }`.

### Frontend Global & Vistas
- [assets/js/global.js](file:///c:/xampp/htdocs/DRC/assets/js/global.js): Implementar el componente universal `initQuickCitizenModal()` con inyección de modal, validación de CURP en tiempo real, envío AJAX y autoselección en `TomSelect`.
- [assets/css/style.css](file:///c:/xampp/htdocs/DRC/assets/css/style.css): Estilos para el botón de acceso rápido y modal de ciudadano.
- **Formularios de Creación:**
  - [modules/curp/create.php](file:///c:/xampp/htdocs/DRC/modules/curp/create.php)
  - [modules/nacimientos/create.php](file:///c:/xampp/htdocs/DRC/modules/nacimientos/create.php)
  - [modules/matrimonios/create.php](file:///c:/xampp/htdocs/DRC/modules/matrimonios/create.php)
  - [modules/divorcios/create.php](file:///c:/xampp/htdocs/DRC/modules/divorcios/create.php)
  - [modules/defunciones/create.php](file:///c:/xampp/htdocs/DRC/modules/defunciones/create.php)
  - [modules/inscripciones/create.php](file:///c:/xampp/htdocs/DRC/modules/inscripciones/create.php)
  - [modules/reconocimientos/create.php](file:///c:/xampp/htdocs/DRC/modules/reconocimientos/create.php)
  - [modules/foraneas/create.php](file:///c:/xampp/htdocs/DRC/modules/foraneas/create.php)
  - [modules/inexistencias/create.php](file:///c:/xampp/htdocs/DRC/modules/inexistencias/create.php)
  - [modules/actas_locales/create.php](file:///c:/xampp/htdocs/DRC/modules/actas_locales/create.php)
  - [modules/peticiones/create.php](file:///c:/xampp/htdocs/DRC/modules/peticiones/create.php)

---

## 4. Plan de Verificación

1. **Prueba en Trámites CURP ([`modules/curp/create.php`](file:///c:/xampp/htdocs/DRC/modules/curp/create.php)):**
   - Hacer clic en `+ Registrar Ciudadano`.
   - Llenar datos de prueba en el modal (ej. *"MARÍA FERNANDA SÁNCHEZ GÓMEZ"*, Fecha, Sexo F, CURP opcional o generada).
   - Guardar: verificar que el modal se cierre, que el TomSelect de *Ciudadano Solicitante* quede seleccionado con *"MARÍA FERNANDA SÁNCHEZ GÓMEZ"* y que se pueda guardar el trámite CURP exitosamente.
2. **Prueba Multiusuario en Nacimientos / Matrimonios:**
   - Registrar rápidamente al padre o madre desde el mismo formulario sin recargar.
3. **Pruebas Automatizadas:**
   - Ejecutar `vendor/bin/phpunit` para verificar la integridad del backend.
