/**
 * components-alpine.js — Componentes Reactivos CSP-Friendly para el ERP DRC.
 * Utiliza la arquitectura Alpine.data() para evitar 'unsafe-eval' en la CSP.
 */

document.addEventListener('alpine:init', () => {
    // 1. Componente para Formulario de Matrimonios
    Alpine.data('formMatrimonios', () => ({
        regimen: 'SOCIEDAD_CONYUGAL',
        testigos: 2,
        folioCapitulaciones: '',
        
        get requiereCapitulaciones() {
            return this.regimen === 'SEPARACION_BIENES';
        }
    }));

    // 2. Componente para Formulario de Inexistencias / Constancias
    Alpine.data('formInexistencias', () => ({
        tipoConstancia: 'NACIMIENTO',
        modalidad: 'ESTANDAR',
        costoBase: 180,
        
        get costoTotal() {
            return this.modalidad === 'URGENTE' ? (this.costoBase * 1.5) : this.costoBase;
        },
        
        get diasEstimados() {
            return this.modalidad === 'URGENTE' ? 2 : 5;
        }
    }));

    // 3. Componente para Ventanilla Rápida y Turnos
    Alpine.data('formVentanillaTurnos', () => ({
        tramiteSeleccionado: '',
        prioridad: 'NORMAL',
        observaciones: '',
        generando: false,

        setTramite(nombre, costo) {
            this.tramiteSeleccionado = nombre;
        }
    }));

    // 4. Componente para Registro de Nacimientos
    Alpine.data('formNacimientos', () => ({
        presentaPadre: true,
        presentaMadre: true,
        lugarNacimientoTipo: 'HOSPITAL',
        
        get requiereTestigosExtra() {
            return !this.presentaPadre && !this.presentaMadre;
        }
    }));
});
