# Manual de Usuario — ERP Dirección de Registro Civil

Guía de uso para operadores, supervisores y administradores del sistema.

---

## 1. Acceso al sistema

1. Abrir el navegador y entrar a la URL del sistema (ej. `http://localhost/DRC/public/login.php`).
2. Ingresar **correo** y **contraseña**.
3. Presionar **Entrar**.

> Si la cuenta está inactiva, el sistema lo notificará. Si olvida la contraseña, solicite al administrador restablecerla.

### Roles

| Rol | Alcance |
|---|---|
| **ADMIN** | Acceso total: usuarios, permisos, auditoría, catálogos y todos los módulos |
| **SUPERVISOR** | Aprobaciones, actualización de estatus de tickets, catálogos y la mayoría de módulos |
| **OPERADOR** | Solo los módulos habilitados con banderas de permiso individuales |

### Permisos por módulo (11 banderas)

El administrador activa/desactiva el acceso de cada operador a: Nacimientos, Matrimonios, Divorcios, Defunciones, Inscripciones, Reconocimientos, Actas Locales, Actas Foráneas, Constancias, Trámites CURP y Tickets. Si al entrar a un módulo aparece **"Acceso Denegado"**, su cuenta no tiene permiso; acuda con el administrador.

---

## 2. Dashboard

Muestra en tiempo real:

- **Tarjetas estadísticas:** trámites de hoy, peticiones pendientes, inexistencias pendientes, foráneas validadas y recaudación total.
- **Gráficas:** trámites por día, recaudación proyectada y carga operativa.
- **Campana de notificaciones:** últimos 5 movimientos del sistema (nuevos trámites, exportaciones terminadas, pendientes de aprobación). Haga clic en la campana para verlos.

---

## 3. Filtrar y buscar en las tablas

Todas las listas (módulos, ciudadanos, reportes) usan **DataTables**:

- **Buscar:** escribir en el campo de búsqueda (filtra en servidor).
- **Paginación:** flechas o números al pie de la tabla.
- **Ordenar:** hacer clic en el encabezado de columna.
- **Exportar:** botón de Excel en la cabecera del módulo (genera el archivo en segundo plano; notificación en la campana cuando esté listo, el archivo queda en el área de descargas/exportaciones).

---

## 4. Módulos de registro

El flujo general de captura es idéntico en todos los módulos:

1. Ir al menú del módulo (Nacimientos, Matrimonios, etc.).
2. Presionar **Nuevo registro** (o el botón azul de alta).
3. Llenar el formulario. **En los campos de persona use la búsqueda de ciudadanos** (autocompletado; si el ciudadano no existe, créelo primero en el módulo **Ciudadanos**).
4. Guardar. Los cambios se confirman con una notificación (toast) y la tabla se actualiza automáticamente.

### 4.1 Ciudadanos (Catálogo Maestro)

- Registro único de identidades (nombre, CURP, sexo, fecha de nacimiento).
- **Todos los módulos se vinculan a este catálogo** — no escriba nombres a mano en los trámites.
- Los nombres se guardan en **MAYÚSCULAS** automáticamente.
- La CURP queda cifrada en la base de datos; la búsqueda funciona igual.

### 4.2 Nacimientos / Matrimonios / Divorcios / Defunciones / Reconocimientos / Inscripciones

- Registrar el acta correspondiente con su número de acta (número de control).
- **Defunciones:** al registrar una defunción, el estado vital del ciudadano cambia automáticamente a `FINADO` (no se puede revertir).
- Un número de acta no puede repetirse entre módulos del mismo tipo.

### 4.3 Constancias de Inexistencia

Tipos disponibles: Inexistencia de Nacimiento, de Matrimonio, de Descendencia y No Deudor Alimentario.

- La **línea de pago** debe tener de 17 a 25 caracteres alfanuméricos; el sistema la valida.
- La **fecha de llegada** se calcula automáticamente sumando 15 días (configurable) a la fecha de trámite.
- Estatus: `PENDIENTE → FINALIZADO/CANCELADO`.

### 4.4 Actas Foráneas

- Registro de actas provenientes de otros estados.
- Estatus: `PENDIENTE → VALIDADA/RECHAZADA`.

### 4.5 Trámites CURP

Solicitudes de ALTA/BAJA/CORRECCIÓN con estatus `PROCESADO/PENDIENTE/RECHAZADO`.

### 4.6 Actas Locales

Buscador centralizado de actas locales (nacimientos, matrimonios, divorcios, defunciones, reconocimientos). Haga clic en una fila para ver el detalle en ventana modal y generar PDF.

### 4.7 Peticiones (Mesa de ayuda / Tickets)

- Crear ticket asociado a un ciudadano (corrección de acta, digitalización, aclaración, otro).
- El sistema genera **folio automático** (`TK-AAAA-#####`).
- Estatus: `ABIERTA → EN_PROGRESO → CERRADA`.
- Supervisores/administradores actualizan el estatus desde el listado.

### 4.8 Reportes

- Filtros gerenciales por rango de fechas, módulo, estatus, etc.
- Exportación a Excel (cola en segundo plano).

---

## 5. Administración (solo ADMIN)

### 5.1 Usuarios (`Usuarios`)

- **Crear usuario:** nombre, correo, contraseña y rol.
- **Permisos:** activar/desactivar las 11 casillas de módulos del operador.
- **Estatus:** activo/inactivo (inactivo no puede entrar). Al desactivar a un usuario conectado, su sesión se revoca en el siguiente acceso.
- Al asignar rol ADMIN, todos los permisos se fuerzan a activados.

### 5.2 Auditoría (`Auditoría`)

- Bitácora: quién, qué acción (alta/modificación/baja), en qué módulo, desde qué IP y cuándo.
- Errores del sistema: mensaje, archivo, línea y pila de errores para soporte técnico.
- Exportables a Excel (ADMIN).

### 5.3 Catálogos (`Catálogos`)

- Mantenimiento de opciones de catálogos dinámicos (agregar/activar/desactivar opciones).
- Disponible para ADMIN y SUPERVISOR.

---

## 6. Perfil personal (`Mi perfil`)

- Cambiar nombre y correo.
- **Cambiar contraseña:** requiere la contraseña actual + nueva + confirmación. Al cambiarla, la sesión se renueva (le pide volver a iniciar sesión en la misma sesión abierta de otros dispositivos).

---

## 7. Atajos de teclado (agilidad de captura)

| Tecla | Acción |
|---|---|
| `Enter` | Pasar al siguiente campo del formulario |
| `Ctrl + Enter` (o `Cmd + Enter`) | Guardar el formulario |

---

## 8. Tema claro/oscuro y móvil

- **Cambiar tema:** usar el botón de luna/sol en el encabezado; la preferencia se recuerda entre visitas.
- **Móvil:** el menú se convierte en cajón deslizable (botón de menú superior). Las tablas se adaptan a pantallas pequeñas; use las flechas de paginación y el buscador.