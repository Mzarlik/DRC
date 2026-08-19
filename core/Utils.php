<?php
namespace Core;

/**
 * Clase de utilidades críticas del sistema que encapsula lógica matemática,
 * formateo de fechas y validaciones estrictas susceptibles de pruebas unitarias.
 */
class Utils {
    /**
     * Calcula la fecha de entrega sumando días naturales o hábiles a una fecha dada.
     * 
     * @param string $fecha_tramite Fecha base en formato Y-m-d
     * @param int $dias_espera Número de días a añadir (por defecto 15)
     * @param bool $solo_habiles True para excluir sábados y domingos
     * @return string Fecha calculada en formato Y-m-d
     */
    public static function calcularFechaLlegada($fecha_tramite, $dias_espera = 15, $solo_habiles = false) {
        $date = new \DateTime($fecha_tramite);
        if (!$solo_habiles) {
            $date->modify("+$dias_espera days");
            return $date->format('Y-m-d');
        }
        
        $added = 0;
        while ($added < $dias_espera) {
            $date->modify('+1 day');
            $w = $date->format('w'); // 0 = Domingo, 6 = Sábado
            if ($w != 0 && $w != 6) {
                $added++;
            }
        }
        return $date->format('Y-m-d');
    }

    /**
     * Valida el formato y longitud de la línea de pago.
     * 
     * @param string $linea_pago Línea de pago
     * @return bool True si cumple con las reglas, False de lo contrario
     */
    public static function validarLineaPago($linea_pago) {
        $len = strlen($linea_pago);
        // Debe tener entre 17 y 25 caracteres
        if ($len < 17 || $len > 25) {
            return false;
        }
        // Debe ser estrictamente alfanumérico
        return preg_match('/^[a-zA-Z0-9]+$/', $linea_pago) === 1;
    }

    /**
     * Genera el HTML de un avatar con las iniciales del usuario (100% offline).
     * 
     * @param string|null $nombre Nombre del usuario
     * @param int $size Tamaño en píxeles
     * @return string HTML seguro del avatar
     */
    public static function getAvatarHtml(?string $nombre, int $size = 32): string {
        $nombre = trim($nombre ?? 'Usuario');
        $words = preg_split('/\s+/', $nombre);
        $initials = '';
        if (count($words) >= 2) {
            $initials = mb_strtoupper(mb_substr($words[0], 0, 1, 'UTF-8') . mb_substr($words[1], 0, 1, 'UTF-8'), 'UTF-8');
        } else {
            $initials = mb_strtoupper(mb_substr($nombre, 0, 2, 'UTF-8'), 'UTF-8');
        }
        $fontSize = round($size * 0.42);
        return '<span class="avatar-initials rounded-circle d-inline-flex align-items-center justify-content-center me-2 text-white fw-bold shadow-sm" style="width:' . $size . 'px; height:' . $size . 'px; font-size:' . $fontSize . 'px; background-color: var(--secondary-color); flex-shrink: 0;">' . htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') . '</span>';
    }
}
