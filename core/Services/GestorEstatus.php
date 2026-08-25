<?php
namespace Core\Services;

use Core\Database;
use Exception;

/**
 * Servicio genérico de transiciones de estatus para los módulos de negocio.
 * Centraliza validación de transiciones, motivo obligatorio en reversiones y
 * cancelaciones, trazabilidad en observaciones (si la tabla la tiene) y auditoría.
 */
class GestorEstatus {

    private static function config(string $modulo): ?array {
        $tablasActas = [
            'nacimientos'     => 'Acta de nacimiento',
            'matrimonios'     => 'Acta de matrimonio',
            'divorcios'       => 'Acta de divorcio',
            'defunciones'     => 'Acta de defunción',
            'inscripciones'   => 'Inscripción de acta foránea',
            'reconocimientos' => 'Acta de reconocimiento',
        ];

        if (isset($tablasActas[$modulo])) {
            return [
                'tabla' => $modulo,
                'sustantivo' => $tablasActas[$modulo],
                'campo_label' => 'numero_acta',
                'columna_observaciones' => null,
                'estados' => ['REGISTRADO', 'CANCELADO'],
                'transiciones' => [
                    'REGISTRADO' => ['CANCELADO'],
                    'CANCELADO'  => ['REGISTRADO']
                ],
                'motivo_obligatorio' => ['CANCELADO', 'REGISTRADO'],
                'etiquetas' => ['CANCELADO' => 'CANCELADA', 'REGISTRADO' => 'REACTIVADA'],
                'mensajes' => [
                    'CANCELADO'  => 'Acta marcada como CANCELADA correctamente.',
                    'REGISTRADO' => 'Acta reactivada a REGISTRADO correctamente.'
                ]
            ];
        }

        $configs = [
            'foraneas' => [
                'tabla' => 'foraneas',
                'sustantivo' => 'Acta foránea',
                'campo_label' => 'numero_acta',
                'columna_observaciones' => 'observaciones',
                'estados' => ['PENDIENTE', 'VALIDADA', 'RECHAZADA'],
                'transiciones' => [
                    'PENDIENTE' => ['VALIDADA', 'RECHAZADA'],
                    'VALIDADA'  => ['PENDIENTE'],
                    'RECHAZADA' => ['PENDIENTE']
                ],
                'motivo_obligatorio' => ['RECHAZADA', 'PENDIENTE'],
                'etiquetas' => [
                    'VALIDADA' => 'VALIDADA', 'RECHAZADA' => 'RECHAZADA', 'PENDIENTE' => 'REACTIVADA'
                ],
                'mensajes' => [
                    'VALIDADA'  => 'Acta foránea VALIDADA correctamente.',
                    'RECHAZADA' => 'Acta foránea RECHAZADA correctamente.',
                    'PENDIENTE' => 'Acta foránea reactivada a PENDIENTE correctamente.'
                ]
            ],
            'curp' => [
                'tabla' => 'tramites_curp',
                'sustantivo' => 'Trámite CURP',
                'campo_label' => 'tipo_solicitud',
                'columna_observaciones' => null,
                'estados' => ['PENDIENTE', 'PROCESADO', 'RECHAZADO'],
                'transiciones' => [
                    'PENDIENTE' => ['PROCESADO', 'RECHAZADO'],
                    'PROCESADO' => ['PENDIENTE'],
                    'RECHAZADO' => ['PENDIENTE']
                ],
                'motivo_obligatorio' => ['RECHAZADO', 'PENDIENTE'],
                'etiquetas' => [
                    'PROCESADO' => 'PROCESADO', 'RECHAZADO' => 'RECHAZADO', 'PENDIENTE' => 'REACTIVADO'
                ],
                'mensajes' => [
                    'PROCESADO' => 'Trámite CURP marcado como PROCESADO correctamente.',
                    'RECHAZADO' => 'Trámite CURP RECHAZADO correctamente.',
                    'PENDIENTE' => 'Trámite CURP reactivado a PENDIENTE correctamente.'
                ]
            ]
        ];

        return $configs[$modulo] ?? null;
    }

    public static function actualizar(string $modulo, $id, string $nuevo_estatus, string $motivo = ''): array {
        $cfg = self::config($modulo);
        if (!$cfg) {
            return ['status' => 'error', 'message' => 'Módulo no soportado para cambio de estatus.'];
        }

        $id = intval($id);
        $nuevo_estatus = strtoupper(trim($nuevo_estatus));
        $motivo = mb_strtoupper(trim($motivo), 'UTF-8');
        $usuario = \Core\Auth::getUserName();

        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'Identificador inválido.'];
        }
        if (!in_array($nuevo_estatus, $cfg['estados'], true)) {
            return ['status' => 'error', 'message' => 'Estatus inválido.'];
        }

        try {
            $pdo = Database::getWriteConnection();
            $columnas = "id, {$cfg['campo_label']}, estatus";
            if ($cfg['columna_observaciones']) {
                $columnas .= ", {$cfg['columna_observaciones']}";
            }
            $stmt = $pdo->prepare("SELECT $columnas FROM {$cfg['tabla']} WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $registro = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$registro) {
                return ['status' => 'error', 'message' => 'El registro no existe o fue eliminado.'];
            }

            $actual = $registro['estatus'];
            if ($actual === $nuevo_estatus) {
                return ['status' => 'error', 'message' => "El registro ya se encuentra en estatus $nuevo_estatus."];
            }
            if (!in_array($nuevo_estatus, $cfg['transiciones'][$actual] ?? [], true)) {
                $permitidos = implode(' o ', $cfg['transiciones'][$actual] ?? []);
                return ['status' => 'error', 'message' => "Transición no permitida: de $actual solo puede pasar a $permitidos."];
            }

            if (in_array($nuevo_estatus, $cfg['motivo_obligatorio'], true) && mb_strlen($motivo) < 5) {
                return ['status' => 'error', 'message' => 'El motivo es obligatorio (mínimo 5 caracteres) para esta acción.'];
            }

            return self::aplicarCambio($cfg, $registro, $id, $nuevo_estatus, $motivo, $actual, $usuario);

        } catch (Exception $e) {
            error_log('GestorEstatus::actualizar: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error inesperado del servidor.'];
        }
    }

    private static function aplicarCambio(array $cfg, array $registro, int $id, string $nuevo_estatus, string $motivo, string $actual, string $usuario): array {
        $pdo = Database::getWriteConnection();
        $params = [':estatus' => $nuevo_estatus, ':id' => $id];
        $sql = "UPDATE {$cfg['tabla']} SET estatus = :estatus";

        if ($cfg['columna_observaciones'] && $motivo !== '') {
            $etiqueta = $cfg['etiquetas'][$nuevo_estatus] ?? $nuevo_estatus;
            $observaciones = trim(($registro[$cfg['columna_observaciones']] ?? '') . " [$etiqueta POR $usuario: $motivo]");
            $sql .= ", {$cfg['columna_observaciones']} = :observaciones";
            $params[':observaciones'] = $observaciones;
        }
        $sql .= " WHERE id = :id";
        $pdo->prepare($sql)->execute($params);

        $es_reversion = in_array($nuevo_estatus, ['PENDIENTE', 'REGISTRADO'], true);
        $accion = $es_reversion ? 'REACTIVAR' : (in_array($nuevo_estatus, ['CANCELADO', 'RECHAZADO', 'RECHAZADA'], true) ? ($nuevo_estatus === 'CANCELADO' ? 'CANCELAR' : 'RECHAZAR') : 'ACTUALIZAR');

        \Core\Auditoria::logAccion(
            $cfg['sustantivo'],
            'EDITAR',
            "Se {$accion} {$cfg['sustantivo']} #{$id} ({$cfg['campo_label']}: {$registro[$cfg['campo_label']]}). Estatus: $actual -> $nuevo_estatus."
            . ($motivo !== '' ? " Motivo: $motivo" : '')
        );

        return ['status' => 'success', 'message' => $cfg['mensajes'][$nuevo_estatus] ?? 'Estatus actualizado correctamente.'];
    }
}
