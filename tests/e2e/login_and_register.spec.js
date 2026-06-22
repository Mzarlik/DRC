const { test, expect } = require('@playwright/test');

test.describe('ERP DRC E2E Tests', () => {
  test('Login and register a Constancia de Inexistencia', async ({ page }) => {
    // 1. Ir al login
    await page.goto('/public/login.php');

    // 2. Llenar credenciales de administrador
    await page.fill('#correo', 'admin@drc.gob.mx');
    await page.fill('#password', 'Admin123!');
    
    // 3. Hacer clic en iniciar sesión
    await page.click('button[type="submit"]');

    // 4. Verificar redirección al dashboard
    await expect(page).toHaveURL(/\/public\/index.php/);
    await expect(page.locator('h2')).toContainText('Dashboard');

    // 5. Ir al formulario de registro de Inexistencias
    await page.goto('/modules/inexistencias/create.php');
    await expect(page.locator('h2')).toContainText('Registrar Trámite de Inexistencia / No Deudor');

    // 6. Llenar el formulario
    // Generar línea de pago única de 20 dígitos
    const randomPayLine = 'E2ETEST' + Math.random().toString().slice(2, 15);
    await page.fill('#linea_pago', randomPayLine);
    await page.fill('#nombre_completo', 'JUAN PEREZ PLAYWRIGHT');
    
    // Fecha de trámite
    await page.fill('#fecha_tramite', '2026-06-22');
    
    // Observaciones
    await page.fill('#observaciones', 'REGISTRO DE PRUEBA E2E PLAYWRIGHT');

    // 7. Guardar el formulario
    await page.click('button[type="submit"]');

    // 8. Verificar que aparece la alerta SweetAlert2 de éxito
    // SweetAlert2 usa la clase '.swal2-popup' y contiene texto del resultado
    const swalPopup = page.locator('.swal2-popup');
    await expect(swalPopup).toBeVisible();
    await expect(swalPopup).toContainText('¡Éxito!');
    
    // Cerrar el SweetAlert haciendo clic en el botón Confirmar
    await page.click('.swal2-confirm');

    // 9. Verificar que redirige al listado de inexistencias
    await expect(page).toHaveURL(/\/modules\/inexistencias\/index.php/);
  });
});
