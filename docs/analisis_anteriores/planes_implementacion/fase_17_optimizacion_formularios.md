# Plan de Implementación: Optimización de Formularios (Touch-Friendly - Fase 3)

Este plan detalla las mejoras de accesibilidad táctil para dispositivos móviles en todo el ERP. Las optimizaciones aseguran que el sistema sea cómodo para capturistas que utilizan tabletas o teléfonos inteligentes en campo.

---

## Cambios Propuestos

### 1. Zonas de Toque (Touch Targets)

Ajustaremos el tamaño de todos los elementos interactivos en dispositivos móviles para cumplir con el estándar recomendado de **44x44 píxeles** (para evitar pulsaciones accidentales).

#### [MODIFY] [style.css](file:///c:/xampp/htdocs/DRC/assets/css/style.css)
Añadiremos reglas responsivas en la media query para móviles (< 768px):
* Botones (`.btn`, `button`): Altura mínima de 44px, tipografía alineada y espaciado táctil cómodo.
* Controles de Entrada (`.form-control`, `.form-select`, `.ts-control` de TomSelect): Altura mínima de 44px para facilitar la selección.

```css
@media (max-width: 767.98px) {
    /* Optimización de Zonas de Toque */
    .btn, 
    button {
        min-height: 44px !important;
        min-width: 44px !important;
        padding: 10px 20px !important;
        font-size: 1rem !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    .form-control, 
    .form-select, 
    .ts-wrapper .ts-control {
        min-height: 44px !important;
        font-size: 1rem !important;
    }
}
```

---

### 2. Teclados Contextuales para Entrada Numérica

Configuraremos inputs numéricos para que fuercen la apertura del teclado numérico grande en dispositivos móviles.

#### [MODIFY] [create.php (Inexistencias)](file:///c:/xampp/htdocs/DRC/modules/inexistencias/create.php)
Modificar el campo de captura `linea_pago` de tipo alfanumérico a numérico con el atributo `inputmode`:
```html
- <input type="text" class="form-control" id="linea_pago" name="linea_pago" maxlength="25" required>
+ <input type="number" inputmode="numeric" class="form-control" id="linea_pago" name="linea_pago" required>
```

#### [MODIFY] [Vistas de creación (Nacimientos, Matrimonios, Defunciones, etc.)](file:///c:/xampp/htdocs/DRC/modules/)
Para optimizar la captura de actas (los cuales son numéricos pero se guardan como cadenas), configuraremos `inputmode="numeric"` and `pattern="[0-9]*"` en los campos de `numero_acta`. Esto abre el teclado numérico móvil pero permite conservar la naturaleza de texto del campo sin romper compatibilidad:
* [Nacimientos](file:///c:/xampp/htdocs/DRC/modules/nacimientos/create.php)
* [Matrimonios](file:///c:/xampp/htdocs/DRC/modules/matrimonios/create.php)
* [Divorcios](file:///c:/xampp/htdocs/DRC/modules/divorcios/create.php)
* [Defunciones](file:///c:/xampp/htdocs/DRC/modules/defunciones/create.php)
* [Inscripciones](file:///c:/xampp/htdocs/DRC/modules/inscripciones/create.php)
* [Reconocimientos](file:///c:/xampp/htdocs/DRC/modules/reconocimientos/create.php)
* [Actas Foráneas](file:///c:/xampp/htdocs/DRC/modules/foraneas/create.php)

---

### 3. Panel de Acciones Flotante (FAB - Floating Action Bar)

En lugar de que los botones primarios (como "Nuevo Registro" y "Exportar a Excel") se queden arriba en la pantalla obligando a hacer scroll, los agruparemos de forma flotante fija en la parte inferior de la pantalla para fácil acceso del pulgar.

#### [MODIFY] [style.css](file:///c:/xampp/htdocs/DRC/assets/css/style.css)
Añadiremos estilos para la barra flotante con efectos de glassmorphism premium:
```css
@media (max-width: 767.98px) {
    .mobile-action-bar {
        position: fixed !important;
        bottom: 15px !important;
        left: 15px !important;
        right: 15px !important;
        background: rgba(255, 255, 255, 0.85) !important;
        backdrop-filter: blur(10px) !important;
        -webkit-backdrop-filter: blur(10px) !important;
        border: 1px solid var(--border-color) !important;
        border-radius: 16px !important;
        padding: 12px !important;
        display: flex !important;
        gap: 10px !important;
        justify-content: center !important;
        z-index: 1030 !important;
        box-shadow: 0 8px 32px rgba(0,0,0,0.12) !important;
    }
    
    body.dark-mode .mobile-action-bar {
        background: rgba(31, 41, 55, 0.85) !important;
        border-color: var(--border-color) !important;
    }

    .mobile-action-bar .btn {
        flex: 1 !important;
        min-height: 48px !important;
        border-radius: 10px !important;
        font-weight: 600 !important;
        margin: 0 !important;
    }
}
```

#### [MODIFY] [global.js](file:///c:/xampp/htdocs/DRC/assets/js/global.js)
Añadiremos una rutina automatizada en el ciclo de carga que detecte los botones de acción del encabezado y los traslade dinámicamente al panel de acciones móvil:
```javascript
    // Reubicación de Botones de Acción en Móviles (FAB)
    function setupMobileActionButtons() {
        if ($(window).width() < 768) {
            const $headerFlex = $('.container-fluid > .d-flex.justify-content-between.align-items-center');
            if ($headerFlex.length) {
                const $actionDiv = $headerFlex.find('div:has(.btn)');
                if ($actionDiv.length && !$actionDiv.hasClass('mobile-action-bar-processed')) {
                    $actionDiv.addClass('mobile-action-bar-processed');
                    
                    const $mobileBar = $('<div class="mobile-action-bar d-md-none"></div>');
                    $actionDiv.children().appendTo($mobileBar);
                    $('body').append($mobileBar);
                    $actionDiv.addClass('d-none');
                    $('body').css('padding-bottom', '95px');
                }
            }
        } else {
            const $actionDiv = $('.mobile-action-bar-processed');
            const $mobileBar = $('.mobile-action-bar');
            if ($actionDiv.length && $mobileBar.length) {
                $mobileBar.children().appendTo($actionDiv);
                $mobileBar.remove();
                $actionDiv.removeClass('d-none mobile-action-bar-processed');
                $('body').css('padding-bottom', '');
            }
        }
    }

    // Ejecutar en carga y resize
    setupMobileActionButtons();
    $(window).on('resize', setupMobileActionButtons);
```

---

## Plan de Verificación

### Pruebas de Interfaz y Comportamiento:
1. **Comprobar Zonas de Toque (Mobile Target Size)**:
   * Abrir la consola de desarrollador en vista móvil (< 768px).
   * Validar que los botones e inputs alcancen una altura mínima de 44px.
2. **Validar Teclados Contextuales**:
   * En un emulador móvil, hacer foco en la caja de texto "Línea de Pago" en el formulario de Inexistencias.
   * Validar que se despliegue de inmediato el teclado numérico en lugar del alfabético.
   * Hacer foco en el campo "Número de Acta" y validar comportamiento similar.
3. **Verificar Floating Action Bar (FAB)**:
   * Desplazarse hacia abajo (hacer scroll) en la página del listado de inexistencias o de ciudadanos.
   * Confirmar que los botones "Exportar" y "Nuevo Registro" permanezcan fijos y cómodamente pulsables al alcance del pulgar.
