# 🚨 PRIORIDAD CRÍTICA — Bugs y Vulnerabilidades en Producción

## 1. Bug de Mapeo de Sexo/Género (Corrupción de datos)

**Problema:** Los valores de `sexo` son incompatibles entre el formulario principal, el modal rápido y el backend. **Los ciudadanos masculinos registrados desde `create.php` quedan guardados como `'X'` (No binario).**

| Fuente | Masculino | Femenino | Otro |
|---|---|---|---|
| [create.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/create.php) L266-268 | `M` | `F` | `X` |
| [global.js](file:///c:/xampp/htdocs/DRC/assets/js/global.js) L1118-1120 (modal rápido) | `H` | `M` | `X` |
| [save.php](file:///c:/xampp/htdocs/DRC/modules/ciudadanos/save.php) L23 (backend) | Espera `H` → `M` | Espera `F`/`MUJER` → `F` | Default → `X` |

**Falla:** Si un usuario registra masculino desde `create.php`, envía `sexo="M"`, backend no matchea `H` ni `F`, **asigna `'X'`**. Si registra mujer desde modal rápido, envía `sexo="M"` (Mujer), backend tampoco matchea → `'X'`.

**Acción:**
1. Decidir convención: ¿`H/M/X` o `M/F/X`?
2. Corregir `create.php`, `save.php` y `global.js`
3. Ejecutar UPDATE correctivo en BD para registros corruptos

**Archivos:** `modules/ciudadanos/create.php`, `modules/ciudadanos/save.php`, `assets/js/global.js`

---

## 2. Módulo de Turnos sin enlace en el Sidebar

**Problema:** [modules/turnos/index.php](file:///c:/xampp/htdocs/DRC/modules/turnos/index.php) está completamente implementado con permisos (`permiso_turnos`), pero **no aparece en la navegación del sidebar** en ninguna vista. Es invisible para los usuarios.

**Acción:** Agregar enlace al sidebar en los **34 archivos PHP** con menú lateral replicado, condicionado a `permiso_turnos`.

> [!TIP]
> Combinar con el ítem #12 (extraer layout a componentes) para evitar modificar 34 archivos dos veces.

---

## 3. CURP almacenada en texto plano en Peticiones Rápidas

**Problema:** La columna `solicitante_curp` en `peticiones_ventanilla` se guarda **sin cifrar**, violando la regla del proyecto: *"CURP: cifrar con `Core\Encryption::encrypt()` y buscar cifrado"*.

**Archivos afectados:**
- [PeticionRapidaService.php](file:///c:/xampp/htdocs/DRC/core/Services/PeticionRapidaService.php) L376: `'solicitante_curp' => !empty($curp) ? $curp : null`
- [modules/peticion_rapida/save.php](file:///c:/xampp/htdocs/DRC/modules/peticion_rapida/save.php) L47
- [modules/peticion_rapida/update.php](file:///c:/xampp/htdocs/DRC/modules/peticion_rapida/update.php) L62
- [modules/peticion_rapida/ticket.php](file:///c:/xampp/htdocs/DRC/modules/peticion_rapida/ticket.php) L73

**Acción:**
1. Cifrar con `Encryption::encrypt()` al guardar/actualizar
2. Descifrar con `Encryption::decrypt()` al leer para tickets
3. Ejecutar migración para cifrar CURPs existentes en `peticiones_ventanilla`

---

## 4. CSRF simulado en nacimientos/save.php (solo comprueba presencia)

**Archivo:** [modules/nacimientos/save.php](file:///c:/xampp/htdocs/DRC/modules/nacimientos/save.php) L13-16

```php
if (empty($_POST['csrf_token'])) {  // ❌ Solo comprueba que no esté vacío
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF inválido.']);
    exit;
}
```

**Impacto:** Un atacante enviando `csrf_token=1` pasa la validación. Nunca se llama a `Auth::validateCSRF()`.

**Acción:** Reemplazar por `Auth::validateCSRF($_POST['csrf_token'])`.
