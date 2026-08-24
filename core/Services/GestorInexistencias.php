<?php
namespace Core\Services;

use Core\Database;
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
    public static function registrarInexistencia($tipo_constancia, $linea_pago, $fecha_tramite, $fecha_llegada, $nombre_completo, $observaciones, $peticion_origen = '') {
        $tipo_constancia = trim($tipo_constancia);
        $linea_pago = trim($linea_pago);
        $fecha_tramite = trim($fecha_tramite);
        $fecha_llegada = trim($fecha_llegada);
        $nombre_completo = mb_strtoupper(trim($nombre_completo), 'UTF-8');
        $observaciones = mb_strtoupper(trim($observaciones), 'UTF-8');
        $peticion_origen = trim($peticion_origen);

        // Validación de tipo de constancia desde base de datos (Catálogos)
        require_once __DIR__ . '/../Catalogo.php';
        $opciones = \Core\Catalogo::getOpciones('tipo_constancia', true);
        $tipos_validos = [
            'INEXISTENCIA_DESCENDENCIA',
            'CONSTANCIA_DESCENDENCIA',
            'NO_DEUDOR',
            'INEXISTENCIA_DEUDOR',
            'INEXISTENCIA_MATRIMONIO',
            'INEXISTENCIA_NACIMIENTO'
        ];
        if (!empty($opciones)) {
            $tipos_validos = array_merge($tipos_validos, array_column($opciones, 'clave'), array_column($opciones, 'valor'));
        }
        if (!in_array($tipo_constancia, $tipos_validos, true)) {
            return ['status' => 'error', 'message' => 'Tipo de constancia inválido.'];
        }

        // Validación de longitud y formato de cadena para la línea de pago
        if (!\Core\Utils::validarLineaPago($linea_pago)) {
            return ['status' => 'error', 'message' => 'La línea de pago debe tener entre 17 y 25 caracteres alfanuméricos.'];
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
                // Si proviene de una petición de ventanilla, actualizar su estatus
                if (!empty($peticion_origen)) {
                    $stmtUpd = $pdo->prepare("UPDATE peticiones_ventanilla 
                                              SET estatus = 'ENTREGADO', 
                                                  detalle = CONCAT(COALESCE(detalle, ''), ' [CONSTANCIA EMITIDA - LP: ', ?, ']'), 
                                                  actualizado_en = NOW() 
                                              WHERE (folio = ? OR id = ?) AND estatus IN ('PENDIENTE', 'EN_PROCESO')");
                    $stmtUpd->execute([$linea_pago, $peticion_origen, intval($peticion_origen)]);
                }

                \Core\Auditoria::logAccion('Inexistencias', 'CREAR', "Se registró una constancia de inexistencia ($tipo_constancia) para $nombre_completo. Línea de pago: $linea_pago" . (!empty($peticion_origen) ? " (Petición Origen: $peticion_origen)" : ""));
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
            error_log('GestorInexistencias: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error inesperado del servidor.'];
        }
    }

    /**
     * Actualiza el estatus de una constancia validando la transición y registrando auditoría.
     * Transiciones permitidas:
     *   PENDIENTE  -> FINALIZADO | CANCELADO
     *   FINALIZADO -> PENDIENTE (reactivación por corrección)
     *   CANCELADO  -> PENDIENTE (reactivación por corrección)
     *
     * @param int $id ID de la constancia
     * @param string $nuevo_estatus PENDIENTE | FINALIZADO | CANCELADO
     * @param string $motivo Justificación (obligatoria para CANCELAR y reactivar)
     * @return array Resultado (status y mensaje)
     */
    public static function actualizarEstatus($id, $nuevo_estatus, $motivo = '') {
        $id = intval($id);
        $nuevo_estatus = strtoupper(trim($nuevo_estatus));
        $motivo = mb_strtoupper(trim($motivo), 'UTF-8');
        $usuario = \Core\Auth::getUserName();

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Identificador de constancia inválido.'];
        }
        if (!in_array($nuevo_estatus, ['PENDIENTE', 'FINALIZADO', 'CANCELADO'], true)) {
            return ['status' => 'error', 'message' => 'Estatus inválido.'];
        }

        $transiciones = [
            'PENDIENTE'  => ['FINALIZADO', 'CANCELADO'],
            'FINALIZADO' => ['PENDIENTE'],
            'CANCELADO'  => ['PENDIENTE']
        ];

        try {
            $pdo = Database::getWriteConnection();
            $stmt = $pdo->prepare("SELECT id, nombre_completo, linea_pago, estatus, observaciones FROM inexistencias WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $registro = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$registro) {
                return ['status' => 'error', 'message' => 'La constancia no existe o fue eliminada.'];
            }

            $actual = $registro['estatus'];
            if ($actual === $nuevo_estatus) {
                return ['status' => 'error', 'message' => "La constancia ya se encuentra en estatus $nuevo_estatus."];
            }
            if (!in_array($nuevo_estatus, $transiciones[$actual] ?? [], true)) {
                return ['status' => 'error', 'message' => "Transición no permitida: una constancia $actual solo puede pasar a " . implode(' o ', $transiciones[$actual] ?? []) . '.' ];
            }

            $es_reactivacion = ($nuevo_estatus === 'PENDIENTE');
            if (($nuevo_estatus === 'CANCELADO' || $es_reactivacion) && $motivo === '') {
                $mensaje = $es_reactivacion ? 'reactivar' : 'cancelar';
                return ['status' => 'error', 'message' => "El motivo es obligatorio para $mensaje la constancia."];
            }

            $observaciones = $registro['observaciones'];
            if ($motivo !== '') {
                $etiqueta = $es_reactivacion ? 'REACTIVADA' : ($nuevo_estatus === 'CANCELADO' ? 'CANCELADA' : 'FINALIZADA');
                $observaciones = trim(($observaciones ?? '') . " [$etiqueta POR $usuario: $motivo]");
            }

            $stmtUpd = $pdo->prepare("UPDATE inexistencias SET estatus = :estatus, observaciones = :observaciones WHERE id = :id");
            $stmtUpd->execute([
                ':estatus' => $nuevo_estatus,
                ':observaciones' => $observaciones,
                ':id' => $id
            ]);

            $accion_auditoria = $es_reactivacion ? 'REACTIVAR' : ($nuevo_estatus === 'CANCELADO' ? 'CANCELAR' : 'FINALIZAR');
            \Core\Auditoria::logAccion(
                'Inexistencias',
                'EDITAR',
                "Se {$accion_auditoria} la constancia #{$id} de {$registro['nombre_completo']} (LP: {$registro['linea_pago']}). Estatus: $actual -> $nuevo_estatus." . ($motivo !== '' ? " Motivo: $motivo" : '')
            );

            $mensajes = [
                'FINALIZADO' => 'Constancia marcada como FINALIZADA correctamente.',
                'CANCELADO'  => 'Constancia cancelada correctamente.',
                'PENDIENTE'  => 'Constancia reactivada a PENDIENTE correctamente.'
            ];
            return ['status' => 'success', 'message' => $mensajes[$nuevo_estatus]];

        } catch (Exception $e) {
            error_log('GestorInexistencias::actualizarEstatus: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error inesperado del servidor.'];
        }
    }
}
