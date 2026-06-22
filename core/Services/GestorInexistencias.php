<?php
namespace Core\Services;

use Core\Database;
use Core\Audit;
use PDOException;
use Exception;

/**
 * Servicio encargado de gestionar las reglas de negocio, validaciones cruzadas
 * y auditorías referentes al módulo de inexistencias y constancias.
 */
class GestorInexistencias {
    /**
     * Registra una constancia de inexistencia o no deudor, validando la línea de pago,
     * realizando validación cruzada con ciudadanos, y guardando la auditoría.
     * 
     * @param string $tipo_constancia Tipo de constancia
     * @param string $linea_pago Línea de pago única
     * @param string $fecha_tramite Fecha de inicio de trámite
     * @param string $fecha_llegada Fecha estimada de entrega
     * @param string $nombre_completo Nombre completo del solicitante
     * @param string $observaciones Observaciones adicionales
     * @return array Resultado del proceso (status y mensaje)
     */
    public static function registrarInexistencia($tipo_constancia, $linea_pago, $fecha_tramite, $fecha_llegada, $nombre_completo, $observaciones) {
        $tipo_constancia = trim($tipo_constancia);
        $linea_pago = trim($linea_pago);
        $fecha_tramite = trim($fecha_tramite);
        $fecha_llegada = trim($fecha_llegada);
        $nombre_completo = mb_strtoupper(trim($nombre_completo), 'UTF-8');
        $observaciones = mb_strtoupper(trim($observaciones), 'UTF-8');

        // Validación de tipo de constancia
        $tipos_validos = ['INEXISTENCIA_NACIMIENTO', 'INEXISTENCIA_MATRIMONIO', 'INEXISTENCIA_DESCENDENCIA', 'NO_DEUDOR'];
        if (!in_array($tipo_constancia, $tipos_validos)) {
            return ['status' => 'error', 'message' => 'Tipo de constancia inválido.'];
        }

        // Validación de longitud y formato de cadena para la línea de pago
        if (strlen($linea_pago) < 17 || strlen($linea_pago) > 25) {
            return ['status' => 'error', 'message' => 'La línea de pago debe tener entre 17 y 25 caracteres.'];
        }

        if (empty($nombre_completo) || empty($fecha_tramite) || empty($fecha_llegada)) {
            return ['status' => 'error', 'message' => 'Faltan campos obligatorios.'];
        }

        try {
            // Validación Cruzada: Verificar si ya existe en ciudadanos (usar conexión de lectura)
            if ($tipo_constancia === 'INEXISTENCIA_NACIMIENTO') {
                $pdoRead = Database::getReadConnection();
                $stmtCheck = $pdoRead->prepare("SELECT COUNT(*) FROM ciudadanos WHERE CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE :nombre");
                $stmtCheck->execute([':nombre' => '%' . $nombre_completo . '%']);
                $exists = $stmtCheck->fetchColumn();

                if ($exists > 0) {
                    return [
                        'status' => 'error',
                        'message' => 'Se detectó un registro local previo para este nombre en el Catálogo de Ciudadanos. No es posible expedir la constancia.'
                    ];
                }
            }

            // Inserción con Sentencia Preparada (Escritura)
            $pdo = Database::getWriteConnection();
            $sql = "INSERT INTO inexistencias (tipo_constancia, linea_pago, fecha_tramite, fecha_llegada, nombre_completo, observaciones) 
                    VALUES (:tipo_constancia, :linea_pago, :fecha_tramite, :fecha_llegada, :nombre_completo, :observaciones)";
            
            $stmt = $pdo->prepare($sql);
            
            $result = $stmt->execute([
                ':tipo_constancia' => $tipo_constancia,
                ':linea_pago' => $linea_pago,
                ':fecha_tramite' => $fecha_tramite,
                ':fecha_llegada' => $fecha_llegada,
                ':nombre_completo' => $nombre_completo,
                ':observaciones' => $observaciones
            ]);

            if ($result) {
                Audit::log('INSERT', 'inexistencias', 'Se registró un nuevo trámite/registro.');
                return ['status' => 'success'];
            } else {
                return ['status' => 'error', 'message' => 'Error al guardar el registro en la base de datos.'];
            }

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['status' => 'error', 'message' => 'La línea de pago ingresada ya se encuentra registrada en el sistema.'];
            } else {
                return ['status' => 'error', 'message' => 'Error de integridad en la base de datos.'];
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Error inesperado del servidor: ' . $e->getMessage()];
        }
    }
}
