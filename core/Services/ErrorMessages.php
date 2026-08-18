<?php
namespace Core\Services;

/**
 * Traduce errores técnicos comunes a mensajes entendibles para el usuario
 * o el administrador del sistema.
 */
class ErrorMessages {

    /** Mensaje genérico de respaldo (sin detalles técnicos). */
    public const GENERICO = 'Ocurrió un error inesperado. Intente de nuevo más tarde.';

    /**
     * Convierte una excepción (Throwable) en un mensaje entendible.
     *
     * @param \Throwable $e Excepción capturada
     * @return string Mensaje amigable
     */
    public static function humanize(\Throwable $e) {
        $code = (string)($e->getCode());
        $msg = $e->getMessage();

        // Errores de conexión
        if (strpos($code, '2002') !== false || strpos($code, '1045') !== false || strpos($code, '1049') !== false
            || stripos($msg, 'Connection refused') !== false || stripos($msg, 'SQLSTATE[HY000] [2002]') !== false
            || stripos($msg, 'Access denied for user') !== false || stripos($msg, 'Unknown database') !== false) {
            return 'No se pudo conectar con la base de datos. Verifique que el servicio esté activo e intente de nuevo.';
        }

        // Duplicados (clave única violada)
        if (strpos($code, '23000') !== false || stripos($msg, 'Duplicate entry') !== false) {
            return 'Ya existe un registro con el mismo folio, número de acta o línea de pago. Verifique los datos e intente de nuevo.';
        }

        // Registro con dependencias (FK)
        if (stripos($msg, 'a foreign key constraint fails') !== false
            || (strpos($code, '23000') !== false && stripos($msg, 'constraint') !== false)) {
            return 'No se puede completar la operación porque el registro está relacionado con otros trámites.';
        }

        // Columna / tabla inexistente (schema desactualizado)
        if (strpos($code, '42S22') !== false || stripos($msg, 'Unknown column') !== false) {
            return 'La base de datos no coincide con la versión del sistema. Contacte al administrador para aplicar las migraciones pendientes.';
        }
        if (strpos($code, '42S02') !== false || stripos($msg, "doesn't exist") !== false || stripos($msg, "Base table or view not found") !== false) {
            return 'La base de datos no coincide con la versión del sistema. Contacte al administrador para aplicar las migraciones pendientes.';
        }

        // Timeout / bloqueo
        if (strpos($code, '1205') !== false || stripos($msg, 'Lock wait timeout') !== false) {
            return 'La operación tardó demasiado en completarse. Intente de nuevo.';
        }

        // Datos inválidos
        if (strpos($code, '22001') !== false || stripos($msg, 'Data too long') !== false) {
            return 'Alguno de los campos supera la longitud máxima permitida. Verifique los datos ingresados.';
        }
        if (strpos($code, '22007') !== false || stripos($msg, 'Incorrect date value') !== false || stripos($msg, 'Incorrect datetime value') !== false) {
            return 'La fecha ingresada no es válida. Verifique el formato (AAAA-MM-DD).';
        }
        if (strpos($code, '22003') !== false || stripos($msg, 'Out of range value') !== false) {
            return 'Alguno de los valores numéricos ingresados está fuera del rango permitido.';
        }

        return self::GENERICO;
    }

    /**
     * Convierte un texto de error (ya almacenado) en un mensaje entendible.
     * Útil para el panel de auditoría/errores.
     *
     * @param string $mensaje Técnico (SQLSTATE / texto crudo)
     * @return string Mensaje amigable
     */
    public static function humanizeText($mensaje) {
        $mensaje = (string)$mensaje;
        if ($mensaje === '') {
            return self::GENERICO;
        }

        if (stripos($mensaje, 'SQLSTATE[23000]') !== false || stripos($mensaje, 'Duplicate entry') !== false) {
            return 'Registro duplicado: ya existe un folio, número de acta o línea de pago igual.';
        }
        if (stripos($mensaje, 'foreign key constraint fails') !== false) {
            return 'Registro relacionado: no se puede eliminar porque está vinculado a otros trámites.';
        }
        if (stripos($mensaje, 'SQLSTATE[42S22]') !== false || stripos($mensaje, 'Unknown column') !== false
            || stripos($mensaje, 'SQLSTATE[42S02]') !== false || stripos($mensaje, 'Base table or view not found') !== false) {
            return 'La base de datos no coincide con la versión del sistema (migraciones pendientes).';
        }
        if (stripos($mensaje, 'SQLSTATE[HY000] [2002]') !== false || stripos($mensaje, 'Access denied for user') !== false
            || stripos($mensaje, 'Unknown database') !== false) {
            return 'Fallo de conexión con la base de datos.';
        }
        if (stripos($mensaje, 'Lock wait timeout') !== false) {
            return 'La operación tardó demasiado en completarse.';
        }
        if (stripos($mensaje, 'Data too long') !== false) {
            return 'Un campo supera la longitud máxima permitida.';
        }
        if (stripos($mensaje, 'Incorrect date value') !== false || stripos($mensaje, 'Incorrect datetime value') !== false) {
            return 'Fecha ingresada no válida.';
        }

        // Errores de aplicación lanzados con mensajes propios ya son entendibles;
        // si es SQLSTATE genérico, mostrar el mensaje genérico.
        if (preg_match('/^SQLSTATE\[\d{5}\]/', $mensaje)) {
            return self::GENERICO;
        }

        return $mensaje;
    }
}
