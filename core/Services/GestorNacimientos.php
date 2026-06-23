<?php
namespace Core\Services;

use Core\Database;
use PDOException;
use Exception;

/**
 * Servicio encargado de gestionar las reglas de negocio, validaciones
 * y auditorías referentes al módulo de nacimientos.
 */
class GestorNacimientos {
    /**
     * Registra un nuevo acta de nacimiento en la base de datos y genera
     * la auditoría correspondiente.
     * 
     * @param string $numero_acta Número de acta único
     * @param int $ciudadano_id ID del ciudadano registrado
     * @param int|null $padre_id ID del padre
     * @param int|null $madre_id ID de la madre
     * @param string $lugar_nacimiento Lugar de nacimiento
     * @param string $fecha_registro Fecha del registro
     * @return array Resultado del proceso (status y mensaje)
     */
    public static function registrarNacimiento($numero_acta, $ciudadano_id, $padre_id, $madre_id, $lugar_nacimiento, $fecha_registro) {
        $numero_acta = trim($numero_acta);
        $fecha_registro = trim($fecha_registro);
        $lugar_nacimiento = mb_strtoupper(trim($lugar_nacimiento), 'UTF-8');

        if (!$ciudadano_id || !$numero_acta || !$lugar_nacimiento || !$fecha_registro) {
            return ['status' => 'error', 'message' => 'Faltan campos obligatorios.'];
        }

        $pdo = Database::getWriteConnection();

        try {
            $sql = "INSERT INTO nacimientos (numero_acta, ciudadano_id, padre_id, madre_id, lugar_nacimiento, fecha_registro) 
                    VALUES (:numero_acta, :ciudadano_id, :padre_id, :madre_id, :lugar_nacimiento, :fecha_registro)";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                ':numero_acta' => $numero_acta,
                ':ciudadano_id' => $ciudadano_id,
                ':padre_id' => $padre_id,
                ':madre_id' => $madre_id,
                ':lugar_nacimiento' => $lugar_nacimiento,
                ':fecha_registro' => $fecha_registro
            ]);

            if ($result) {
                \Core\Auditoria::logAccion('Nacimientos', 'CREAR', "Se registró un nacimiento. Acta: $numero_acta, Ciudadano ID: $ciudadano_id");
                return ['status' => 'success'];
            } else {
                return ['status' => 'error', 'message' => 'Error al guardar el registro.'];
            }

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['status' => 'error', 'message' => 'El número de acta ya existe o hubo un error de llaves foráneas.'];
            } else {
                return ['status' => 'error', 'message' => 'Error de integridad en la base de datos.'];
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Error inesperado del servidor: ' . $e->getMessage()];
        }
    }
}
