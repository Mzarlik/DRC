<?php
namespace Core;

/**
 * Clase Catalogs para administrar la recuperación optimizada de listados estáticos
 * utilizando la capa de caché para evitar consultas redundantes.
 */
class Catalogs {
    /**
     * Devuelve la lista de Estados de la República Mexicana.
     * Utiliza la clase Cache para almacenar el catálogo en memoria/archivo.
     *
     * @return array Lista de estados (Código => Nombre completo)
     */
    public static function getEstados() {
        $cacheKey = 'catalogo_estados_mexico';
        $cached = Cache::get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }

        $estados = [
            'AGS' => 'AGUASCALIENTES',
            'BC'  => 'BAJA CALIFORNIA',
            'BCS' => 'BAJA CALIFORNIA SUR',
            'CAM' => 'CAMPECHE',
            'COA' => 'COAHUILA',
            'COL' => 'COLIMA',
            'CHP' => 'CHIAPAS',
            'CHH' => 'CHIHUAHUA',
            'CMX' => 'CIUDAD DE MÉXICO',
            'DUR' => 'DURANGO',
            'GUA' => 'GUANAJUATO',
            'GRO' => 'GUERRERO',
            'HID' => 'HIDALGO',
            'JAL' => 'JALISCO',
            'MEX' => 'ESTADO DE MÉXICO',
            'MIC' => 'MICHOACÁN',
            'MOR' => 'MORELOS',
            'NAY' => 'NAYARIT',
            'NLE' => 'NUEVO LEÓN',
            'OAX' => 'OAXACA',
            'PUE' => 'PUEBLA',
            'QUE' => 'QUERÉTARO',
            'ROO' => 'QUINTANA ROO',
            'SLP' => 'SAN LUIS POTOSÍ',
            'SIN' => 'SINALOA',
            'SON' => 'SONORA',
            'TAB' => 'TABASCO',
            'TAM' => 'TAMAULIPAS',
            'TLA' => 'TLAXCALA',
            'VER' => 'VERACRUZ',
            'YUC' => 'YUCATÁN',
            'ZAC' => 'ZACATECAS'
        ];

        // Guardar por un TTL largo (ej. 24 horas = 86400 segundos) ya que no cambia
        Cache::set($cacheKey, $estados, 86400);

        return $estados;
    }

    /**
     * Devuelve la lista de tipos de trámite o constancias disponibles.
     *
     * @return array (Código => Nombre completo)
     */
    public static function getTiposTramite() {
        $cacheKey = 'catalogo_tipos_tramite';
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $tipos = [
            'INEXISTENCIA_DESCENDENCIA' => 'CONSTANCIA DE DESCENDENCIA Y/O NO DESCENDENCIA',
            'NO_DEUDOR' => 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE DEUDOR ALIMENTARIO MOROSO',
            'INEXISTENCIA_MATRIMONIO' => 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE MATRIMONIO',
            'INEXISTENCIA_NACIMIENTO' => 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE NACIMIENTO'
        ];

        Cache::set($cacheKey, $tipos, 86400);

        return $tipos;
    }
}
