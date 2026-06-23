<?php
namespace Core;

use PDO;
use Exception;

/**
 * Clase para gestionar catálogos y opciones dinámicas en el sistema.
 */
class Catalogo {

    /**
     * Obtiene las opciones de un catálogo específico.
     * 
     * @param string $catalogoNombre Nombre interno del catálogo (ej. 'tipo_constancia')
     * @param bool $soloActivos Si es true, solo retorna las opciones activas (habilitadas)
     * @return array Lista de opciones con id, clave, valor, activo y orden
     */
    public static function getOpciones($catalogoNombre, $soloActivos = true): array {
        try {
            $pdo = Database::getConnection();
            $sql = "SELECT co.id, co.clave, co.valor, co.activo, co.orden 
                    FROM catalogo_opciones co
                    JOIN catalogos c ON co.catalogo_id = c.id
                    WHERE c.nombre_interno = :nombre";
            
            if ($soloActivos) {
                $sql .= " AND co.activo = 1";
            }
            
            $sql .= " ORDER BY co.orden ASC, co.valor ASC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':nombre' => $catalogoNombre]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Agrega una nueva opción a un catálogo específico.
     * 
     * @param string $catalogoNombre Nombre interno del catálogo
     * @param string $clave Clave identificadora (ej. 'INEXISTENCIA_DIVORCIO')
     * @param string $valor Texto descriptivo (ej. 'CONSTANCIA DE INEXISTENCIA DE DIVORCIO')
     * @param int $orden Orden de visualización
     * @return bool True si se agregó exitosamente, false en caso contrario
     */
    public static function agregarOpcion($catalogoNombre, $clave, $valor, $orden = 0): bool {
        try {
            $pdo = Database::getConnection();
            
            // Buscar el ID del catálogo
            $stmtId = $pdo->prepare("SELECT id FROM catalogos WHERE nombre_interno = ?");
            $stmtId->execute([$catalogoNombre]);
            $catalogoId = $stmtId->fetchColumn();
            
            if (!$catalogoId) {
                throw new Exception("El catálogo '$catalogoNombre' no existe.");
            }
            
            // Insertar la nueva opción
            $stmtInsert = $pdo->prepare("INSERT INTO catalogo_opciones (catalogo_id, clave, valor, orden, activo) VALUES (?, ?, ?, ?, 1)");
            return $stmtInsert->execute([$catalogoId, trim(strtoupper($clave)), trim($valor), $orden]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Activa o desactiva (lógica) una opción de catálogo por su ID.
     * 
     * @param int $opcionId ID de la opción de catálogo
     * @param int $activo Estado (1 = activo, 0 = inactivo)
     * @return bool
     */
    public static function toggleEstadoOpcion($opcionId, $activo): bool {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("UPDATE catalogo_opciones SET activo = ? WHERE id = ?");
            return $stmt->execute([$activo ? 1 : 0, $opcionId]);
        } catch (Exception $e) {
            return false;
        }
    }
}
