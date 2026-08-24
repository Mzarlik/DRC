# Plan de Implementación: Sistema de Catálogos Dinámicos

Actualmente, las listas de opciones del sistema (como los tipos de constancia en Inexistencias, regímenes patrimoniales, etc.) se definen mediante restricciones `ENUM` en la base de datos y están escritas de forma estática (hardcoded) en los archivos HTML/PHP de los formularios.

Para permitir que un Administrador o Supervisor agregue nuevas opciones sin necesidad de modificar el código ni el esquema de la base de datos, proponemos la creación de un **Módulo de Gestión de Catálogos**.

---

## Decisiones de Diseño Propuestas

1. **Tablas de Catálogos**:
   Crear una estructura relacional para administrar los catálogos del sistema:
   * **`catalogos`**: Define el catálogo (ej: `tipo_constancia`, `regimen_patrimonial`, `tipo_divorcio`, `paises`).
   * **`catalogo_opciones`**: Contiene las opciones dinámicas asociadas a cada catálogo (ej: `NO_DEUDOR` -> "Constancia de No Deudor Alimentario"), con un campo para habilitar/deshabilitar la opción y un orden de visualización.

2. **Migración Progresiva**:
   * Para evitar alterar los tipos de datos históricos de golpe, las columnas de la base de datos (que hoy son `ENUM` o `VARCHAR`) seguirán almacenando el código/valor (`opcion_clave`).
   * Los formularios HTML realizarán una consulta dinámica a la base de datos para cargar las opciones activas del catálogo respectivo.

3. **Interfaz de Gestión**:
   * Dentro del módulo de **Administración**, se agregará una nueva vista: **Gestor de Catálogos** (`public/catalogos.php`), accesible para roles `ADMIN` y `SUPERVISOR`.
   * En esta vista, el usuario podrá seleccionar un catálogo, visualizar las opciones existentes, agregar una nueva opción (clave y valor descriptivo), reordenarlas o desactivarlas.

---

## Cambios Propuestos

### Base de Datos

#### [NEW] `schema_catalogos.sql`
Script para crear las tablas del sistema de catálogos:
```sql
CREATE TABLE IF NOT EXISTS catalogos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_interno VARCHAR(50) NOT NULL UNIQUE, -- ej. 'tipo_constancia'
    descripcion VARCHAR(100) NOT NULL,          -- ej. 'Tipos de Constancias de Inexistencia'
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS catalogo_opciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    catalogo_id INT NOT NULL,
    clave VARCHAR(50) NOT NULL,                 -- ej. 'INEXISTENCIA_NACIMIENTO' o 'NUEVA_CATEGORIA'
    valor VARCHAR(150) NOT NULL,                -- ej. 'Constancia de Inexistencia de Nacimiento'
    activo TINYINT(1) DEFAULT 1,                -- 1 = Activo, 0 = Inactivo
    orden INT DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (catalogo_id) REFERENCES catalogos(id) ON DELETE CASCADE,
    UNIQUE KEY uq_catalogo_clave (catalogo_id, clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

*Nota: Se insertarán por defecto las opciones actuales de `tipo_constancia`, `regimen_patrimonial`, etc., para garantizar compatibilidad hacia atrás.*

---

### Backend Components

#### [NEW] [Catalogo.php](file:///c:/xampp/htdocs/DRC/core/Catalogo.php)
Clase utilitaria para obtener las opciones de un catálogo dinámicamente:
* `public static function getOpciones($catalogoNombre, $soloActivos = true): array`
* `public static function agregarOpcion($catalogoNombre, $clave, $valor, $orden = 0): bool`
* `public static function toggleEstadoOpcion($opcionId, $activo): bool`

---

### UI Components

#### [MODIFY] [create.php (Inexistencias)](file:///c:/xampp/htdocs/DRC/modules/inexistencias/create.php)
Sustituir las opciones estáticas del `<select id="tipo_constancia">` por una carga dinámica:
```php
<?php
$opciones = \Core\Catalogo::getOpciones('tipo_constancia');
foreach ($opciones as $opc) {
    echo '<option value="' . htmlspecialchars($opc['clave']) . '">' . htmlspecialchars($opc['valor']) . '</option>';
}
?>
```

#### [NEW] [catalogos.php](file:///c:/xampp/htdocs/DRC/public/catalogos.php)
Panel administrativo para que ADMIN/SUPERVISOR gestionen los catálogos:
* Selector de Catálogo (dropdown).
* Tabla con las opciones actuales.
* Botón "Agregar Nueva Categoría" que abre un modal solicitando la **Clave** (ej. `NUEVO_TIPO`) y el **Nombre Comercial/Descripción** (ej. `Constancia de X`).
* Botón para desactivar/activar opciones existentes.

#### [NEW] [api/catalogos_handler.php](file:///c:/xampp/htdocs/DRC/public/api/catalogos_handler.php)
Controlador backend AJAX para procesar las peticiones del panel administrativo.

---

## Plan de Verificación

### Pruebas Manuales
1. Entrar con rol `ADMIN` o `SUPERVISOR` a la nueva sección **Gestor de Catálogos**.
2. Agregar una nueva categoría al catálogo de *Tipos de Constancias* (ej: clave `CONSTANCIA_PRUEBA`, valor `Constancia de Prueba Dinámica`).
3. Ir al módulo de Inexistencias -> Crear Nuevo Registro y verificar que la opción `Constancia de Prueba Dinámica` aparezca automáticamente en el desplegable.
4. Guardar un registro con la nueva categoría y verificar en la tabla y reporte Excel que se guarde adecuadamente.
