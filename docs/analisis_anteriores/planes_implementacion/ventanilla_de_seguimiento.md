# Plan de Implementación: Transformación a Ventanilla de Seguimiento

- Se eliminó la sección genérica "Mesa de Ayuda" en todos los sidebars.
- Se consolidó la suite integral bajo la sección "Ventanilla":
  1. Petición Rápida (`modules/peticion_rapida/index.php`)
  2. Reporte Diario Oficial (`modules/peticion_rapida/reporte_diario.php`)
  3. Ventanilla de Seguimiento (`modules/peticiones/index.php`)
- Escalamiento directo con 1 clic desde Petición Rápida hacia Ventanilla de Seguimiento precargando solicitante, antecedentes y folio de origen.
- Generación de folios formales `SEG-AAAA-#####`.
