# Historial de Versiones (Changelog)

Este documento registra todos los cambios notables, actualizaciones y correciones del sistema ERP para la Dirección de Registro Civil.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

## [1.2.0] - 2026-06-19
### Añadido
- **Nuevos Módulos de Registro:** Lanzamiento de los módulos para matrimonios (`modules/matrimonios`), divorcios (`modules/divorcios`), reconocimientos (`modules/reconocimientos`), inscripciones (`modules/inscripciones`) y trámites de CURP (`modules/curp`).
- **Sistema de Permisos Granular:** 11 banderas booleanas para controlar el acceso individual de los operadores a nacimientos, matrimonios, divorcios, defunciones, inscripciones, reconocimientos, actas locales, actas foráneas, constancias, trámites CURP y tickets de soporte.
- **Buscador de Actas Locales:** Módulo `modules/actas_locales` para consultar e inspeccionar los detalles de cualquier acta local (Nacimiento, Matrimonio, Divorcio, Defunción, Reconocimiento) mediante un buscador dinámico y ventana de detalle interactiva (SweetAlert2).
- **Constancias Expandidas:** Soporte para Constancias de Inexistencia de Matrimonio, Nacimiento, Descendencia y Constancias de No Deudor Alimentario en `modules/inexistencias`.
- **Panel de Permisos de Usuarios:** Interfaz de usuario mejorada en `public/usuarios.php` que permite a los administradores activar/desactivar checkboxes para los 11 permisos individuales de cada operador.

## [1.1.0] - 2026-06-19
### Añadido
- **Perfil de Usuario (Mi Perfil):** Página de gestión de perfil (`public/perfil.php` y `public/update_perfil.php`) que permite a los usuarios cambiar su nombre de visualización, correo electrónico y cambiar su contraseña de manera segura tras validar la actual contra la base de datos.
- **Notificaciones (Historial en Header):** Botón de campana en el header de todos los módulos con un contador dinámico de novedades. Carga mediante AJAX los últimos 5 trámites o registros realizados en todo el ERP (Nacimientos, Defunciones, Ciudadanos, Tickets o Foráneas) con formateo visual y tiempo relativo.
- **Seguridad (Autenticación):** Sistema de inicio y cierre de sesión seguro basado en PHP Sessions (`core/Auth.php`, `public/login.php`, `public/auth.php`, `public/logout.php`).
- **Seguridad (Control de Acceso):** Validación de sesión mediante `Auth::check()` en el Dashboard, vistas de creación y endpoints/APIs de todos los módulos (`ciudadanos`, `nacimientos`, `defunciones`, `foraneas`, `inexistencias`, `peticiones`).
- **Usuario Administrador:** Inclusión de credenciales por defecto (`admin@drc.gob.mx` / `Admin123!`) mediante inserción SQL segura (`password_hash()`) en `docs/database.sql`.
- **UI (Sidebar Compacto):** Soporte para contraer la barra lateral en escritorio (ocultando textos de menús y submenús, dejando solo iconos visibles) al hacer clic en el botón de menú para maximizar el espacio de trabajo.
- **Responsividad (Móvil):** Menú lateral tipo cajón (*drawer*) desplegable en pantallas pequeñas (<768px) y redimensionamiento automático de tarjetas estadísticas a ancho completo.

## [1.0.0] - Liberación Inicial
### Añadido
- **Fase 1:** Inicialización de arquitectura MVC-like, base de datos segura (PDO/Prepared Statements) y UI con Bootstrap 5.
- **Fase 1:** Módulo de Constancias de Inexistencias con cálculo dinámico de fechas.
- **Fase 2:** Implementación del Catálogo Maestro de Ciudadanos para control único de identidades.
- **Fase 2:** Módulos de Nacimientos y Defunciones con lógica transaccional de cambio de Estado Vital.
- **Fase 3:** Recepción y validación de Actas Foráneas.
- **Fase 3:** Sistema de Mesa de Ayuda (Tickets/Peticiones) con generación automática de folios alfanuméricos.
- **Fase 3:** Dashboard dinámico con API de estadísticas en tiempo real y gráficas interactivas con Chart.js.
- Documentos normativos de seguridad (`TESTING_SECURITY.md`).
