# Plan y Reporte: Revisión Completa del Sistema de Notificaciones y Header Navbar

## 1. Problemas Detectados y Corregidos
- **Ícono Duplicado de Modo Oscuro (`🌙 🌙`):** Había una doble declaración (un botón estático en `public/index.php` más la inyección dinámica de `assets/js/global.js`). Se unificó a un único controlador reactivo en `global.js`.
- **Flecha/Caret de Dropdown Desalineado bajo la Campana:** Bootstrap 5 forzaba el pseudo-elemento `::after` en `.dropdown-toggle`. Se corrigió en `assets/css/style.css` con `.no-caret::after { display: none !important; }`.
- **Enriquecimiento del Centro de Notificaciones (`public/api/notifications.php`):**
  - Soporte integral de 6 fuentes de eventos institucionales:
    1. Dictámenes / Aprobaciones de Seguimiento (`peticiones`).
    2. Descarga de Reportes Asíncronos (`export_jobs` / `jobs`).
    3. Actas Foráneas Pendientes de Cotejo (`foraneas`).
    4. Constancias de Inexistencia Pendientes (`inexistencias`).
    5. Trámites Activos de Ventanilla Rápida (`peticiones_ventanilla`).
    6. Últimos Registros Relevantes del Registro Civil (Nacimientos, Ciudadanos).
  - Enlaces de acción directa: al hacer clic sobre una notificación, redirige inmediatamente al módulo correspondiente o dispara la descarga del archivo.
- **Centralización en JavaScript (`assets/js/global.js`):**
  - Eliminados los scripts inline duplicados en 17 vistas.
  - Polling en tiempo real cada 30 segundos con actualización fluida del contador y badges de urgencia.
