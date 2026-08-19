# Plan de Implementación: Petición Rápida de Ventanilla v2 y Catálogo Oficial

Histórico de planteamiento arquitectónico para la estandarización de trámites de ventanilla, sistema de folios simplificado y validaciones de datos.

- Servicio Centralizado: `Core\Services\PeticionRapidaService`
- Catálogo de 21 trámites oficiales con código de 3 letras.
- Folios: `[CODIGO]-[YYMMDD]-[001]`
- Validaciones en frontend y backend.
