<?php
namespace Core\Services;

use Core\Database;
use PDOException;
use Exception;

/**
 * Servicio encargado de gestionar las reglas de negocio, transacciones
 * y auditorías referentes al módulo de defunciones.
 */
class GestorDefunciones {
    /**
     * Registra una defunción en la base de datos, marca al ciudadano como FINADO
     * y genera un log de auditoría de forma atómica.
     * 
     * @param string $numero_acta Número único de acta
     * @param int $ciudadano_id ID del ciudadano finado
     * @param string $fecha_defuncion Fecha del deceso
     * @param string $fecha_registro Fecha del registro
     * @param string $causa_muerte Causa de defunción
     * @return array Resultado del proceso (status y mensaje)
     */
    public static function registrarDefuncion($numero_acta, $ciudadano_id, $fecha_defuncion, $fecha_registro, $causa_muerte) {
        $numero_acta = trim($numero_acta);
        $fecha_defuncion = trim($fecha_defuncion);
        $fecha_registro = trim($fecha_registro);
        $causa_muerte = mb_strtoupper(trim($causa_muerte), 'UTF-8');

        if (!$ciudadano_id || !$numero_acta || !$causa_muerte || !$fecha_defuncion || !$fecha_registro) {
            return ['status' => 'error', 'message' => 'Faltan campos obligatorios.'];
        }

        $pdo = Database::getWriteConnection();

        try {
            $pdo->beginTransaction();

            $sqlDefuncion = "INSERT INTO defunciones (numero_acta, ciudadano_id, fecha_defuncion, causa_muerte, fecha_registro) 
                             VALUES (:numero_acta, :ciudadano_id, :fecha_defuncion, :causa_muerte, :fecha_registro)";
            
            $stmtDefuncion = $pdo->prepare($sqlDefuncion);
            $stmtDefuncion->execute([
                ':numero_acta' => $numero_acta,
                ':ciudadano_id' => $ciudadano_id,
                ':fecha_defuncion' => $fecha_defuncion,
                ':causa_muerte' => $causa_muerte,
                ':fecha_registro' => $fecha_registro
            ]);

            // Actualizar el estado vital a FINADO
            $sqlEstado = "UPDATE ciudadanos SET estado_vital = 'FINADO' WHERE id = :ciudadano_id";
            $stmtEstado = $pdo->prepare($sqlEstado);
            $stmtEstado->execute([':ciudadano_id' => $ciudadano_id]);

            $pdo->commit();

            \Core\Auditoria::logAccion('Defunciones', 'CREAR', "Se registró una defunción. Acta: $numero_acta, Ciudadano ID: $ciudadano_id");
            return ['status' => 'success'];

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getCode() == 23000) {
                return ['status' => 'error', 'message' => 'El número de acta ya existe o hubo un error en la validación.'];
            } else {
                return ['status' => 'error', 'message' => 'Error de integridad en la base de datos.'];
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['status' => 'error', 'message' => 'Error inesperado del servidor: ' . $e->getMessage()];
        }
    }
}
