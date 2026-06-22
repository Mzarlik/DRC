# Pruebas Automatizadas (Fase 13)

Este plan detalla el diseño e implementación de la suite de pruebas del ERP DRC, dividida en pruebas unitarias backend con PHPUnit y pruebas de extremo a extremo (E2E) con Playwright.

## User Review Required

> [!IMPORTANT]
> **Instalación de Dependencias de Desarrollo**
> Para ejecutar las pruebas unitarias y E2E se requiere la instalación de herramientas en el entorno. Se instalará `phpunit/phpunit` mediante Composer, y se proveerán los archivos `package.json` y `playwright.config.js` para configurar Playwright, requiriendo que el administrador ejecute `npm install` si desea correr las pruebas de interfaz gráfica en su sistema.

---

## Proposed Changes

### 1. Refactorización de Funciones Críticas para Testabilidad
Extraer funciones críticas dispersas a una clase de utilidades testable.

#### [NEW] [Utils.php](file:///c:/xampp/htdocs/DRC/core/Utils.php)
- Clase `Utils` en el espacio de nombres `Core`.
- Método `calcularFechaLlegada($fecha_tramite, $dias_espera, $solo_habiles)`: Permite calcular la fecha de entrega de constancias (apoyando días naturales y hábiles).
- Método `validarLineaPago($linea_pago)`: Valida la longitud (17-25) y formato alfanumérico estricto.

#### [MODIFY] [GestorInexistencias.php](file:///c:/xampp/htdocs/DRC/core/Services/GestorInexistencias.php)
- Modificar la validación de la línea de pago para utilizar `Core\Utils::validarLineaPago($linea_pago)`.

---

### 2. Pruebas Unitarias (PHPUnit)
Configurar e implementar pruebas para el core de base de datos y utilidades.

#### [NEW] [phpunit.xml](file:///c:/xampp/htdocs/DRC/phpunit.xml)
- Configuración de PHPUnit apuntando al directorio de pruebas.

#### [NEW] [DatabaseTest.php](file:///c:/xampp/htdocs/DRC/tests/Unit/DatabaseTest.php)
- Pruebas para `Core\Database`:
  - `testGetConnectionReturnsPDO()`: Conexión Singleton Master.
  - `testGetReadConnectionReturnsPDO()`: Conexión Réplica.
  - `testGenerateFolio()`: Transaccionalidad de folios únicos correlativos.

#### [NEW] [UtilsTest.php](file:///c:/xampp/htdocs/DRC/tests/Unit/UtilsTest.php)
- Pruebas para `Core\Utils`:
  - `testCalcularFechaLlegadaCalendarDays()`: Días naturales.
  - `testCalcularFechaLlegadaBusinessDays()`: Días hábiles (excluyendo fines de semana).
  - `testValidarLineaPago()`: Validación de longitud y formato alfanumérico.

---

### 3. Pruebas de Extremo a Extremo (E2E Playwright)
Configurar y simular flujos completos de usuario final en navegador.

#### [NEW] [package.json](file:///c:/xampp/htdocs/DRC/package.json)
- Dependencia de desarrollo `@playwright/test`.

#### [NEW] [playwright.config.js](file:///c:/xampp/htdocs/DRC/playwright.config.js)
- Configuración de baseURL y timeouts para Playwright.

#### [NEW] [login_and_register.spec.js](file:///c:/xampp/htdocs/DRC/tests/e2e/login_and_register.spec.js)
- Script E2E Playwright que realiza el siguiente flujo:
  1. Abre el ERP en `/public/login.php`.
  2. Completa credenciales del administrador y envía.
  3. Comprueba redirección al Dashboard.
  4. Navega a la creación de Inexistencia `/modules/inexistencias/create.php`.
  5. Rellena el formulario con línea de pago aleatoria (única) y datos.
  6. Guarda y valida la aparición de la alerta SweetAlert2 de éxito.
  7. Confirma la alerta y verifica la redirección al listado.

---

## Verification Plan

### Automated Tests
- Instalar PHPUnit: `c:\xampp\php\php.exe composer.phar require --dev phpunit/phpunit`
- Correr pruebas unitarias: `c:\xampp\php\php.exe vendor/bin/phpunit`
- Validar sintaxis con `php -l`.

### Manual Verification
- Inspeccionar visualmente que los nuevos scripts unitarios y E2E se ubiquen correctamente en `tests/`.
