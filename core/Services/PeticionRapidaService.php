<?php
namespace Core\Services;

use Core\Database;
use Core\Encryption;
use Exception;
use PDO;

/**
 * Servicio integral para la gestión de Peticiones Rápidas, Trámites de Ventanilla 
 * y Generación del Reporte Diario Oficial en DRC.
 */
class PeticionRapidaService {

    /**
     * Catálogo maestro unificado de trámites y actividades oficiales de la Dirección de Registro Civil.
     */
    public const TRAMITES = [
        // --- 1. REGISTROS Y ACTAS DEL ESTADO CIVIL ---
        'REGISTRO_NACIMIENTO_HG' => [
            'codigo' => 'NHG',
            'nombre' => 'REGISTROS DE NACIMIENTO (HOSPITAL GENERAL)'
        ],
        'REGISTRO_NACIMIENTO' => [
            'codigo' => 'NAC',
            'nombre' => 'REGISTROS DE NACIMIENTO'
        ],
        'INSCRIPCION_NACIMIENTO' => [
            'codigo' => 'INA',
            'nombre' => 'INSCRIPCIONES DE NACIMIENTO'
        ],
        'SOLICITUD_EXTEMPORANEO' => [
            'codigo' => 'EXT',
            'nombre' => 'SOLICITUDES DE REGISTROS EXTEMPORÁNEOS'
        ],
        'ACTA_FIRMADA_EXTEMPORANEO' => [
            'codigo' => 'EXF',
            'nombre' => 'ACTAS FIRMADAS DE REGISTROS EXTEMPORÁNEOS'
        ],
        'REGISTRO_DEFUNCION' => [
            'codigo' => 'DEF',
            'nombre' => 'REGISTROS DE DEFUNCIÓN'
        ],
        'ACTA_CERTIFICADA_DEFUNCION' => [
            'codigo' => 'ACD',
            'nombre' => 'ACTAS CERTIFICADAS DE DEFUNCIÓN'
        ],
        'EXPEDIENTES_MATRIMONIO' => [
            'codigo' => 'EMR',
            'nombre' => 'EXPEDIENTES DE MATRIMONIO RECIBIDOS'
        ],
        'BODAS_REALIZADAS' => [
            'codigo' => 'BOD',
            'nombre' => 'BODAS REALIZADAS'
        ],
        'EXPEDIENTES_NACIMIENTO' => [
            'codigo' => 'ENR',
            'nombre' => 'EXPEDIENTES DE NACIMIENTO RECIBIDOS'
        ],
        'EXPEDIENTES_DIVORCIO' => [
            'codigo' => 'EDR',
            'nombre' => 'EXPEDIENTES DE DIVORCIO RECIBIDOS'
        ],
        'CAPTURA_DIVORCIO' => [
            'codigo' => 'CDV',
            'nombre' => 'CAPTURA DE REGISTROS DE DIVORCIO'
        ],
        'DIVORCIO_ADMINISTRATIVO' => [
            'codigo' => 'DVA',
            'nombre' => 'DIVORCIOS ADMINISTRATIVOS TRAMITADOS'
        ],
        'DIVORCIO_JUDICIAL' => [
            'codigo' => 'DVJ',
            'nombre' => 'DIVORCIOS JUDICIALES INSCRITOS'
        ],
        'ACTAS_LOCALES_FORANEAS_ENTREGADAS' => [
            'codigo' => 'ALF',
            'nombre' => 'ACTAS LOCALES Y FORÁNEAS ENTREGADAS'
        ],
        'ACTA_FORANEA' => [
            'codigo' => 'FOR',
            'nombre' => 'ACTA FORÁNEA (INTERESTATAL)'
        ],
        'ACTAS_ELABORADAS_ENTREGADAS' => [
            'codigo' => 'ELA',
            'nombre' => 'ACTAS ELABORADAS O ENTREGADAS'
        ],
        'ACTA_CAPTURISTA' => [
            'codigo' => 'TUR',
            'nombre' => 'ACTAS TURNADAS A CAPTURISTAS'
        ],
        'IDENTIDAD_GENERO' => [
            'codigo' => 'GEN',
            'nombre' => 'RECONOCIMIENTO DE IDENTIDAD DE GÉNERO'
        ],

        // --- 2. CONSTANCIAS, INEXISTENCIAS Y CERTIFICACIONES OFICIALES ---
        'CONSTANCIA_DESCENDENCIA' => [
            'codigo' => 'CND',
            'nombre' => 'CONSTANCIA DE DESCENDENCIA Y/O NO DESCENDENCIA'
        ],
        'CONSTANCIA_DEUDOR_MOROSO' => [
            'codigo' => 'CID',
            'nombre' => 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE DEUDOR ALIMENTARIO MOROSO'
        ],
        'CONSTANCIA_INEXISTENCIA_MATRIMONIO' => [
            'codigo' => 'CIM',
            'nombre' => 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE MATRIMONIO'
        ],
        'CONSTANCIA_INEXISTENCIA_NACIMIENTO' => [
            'codigo' => 'CIN',
            'nombre' => 'CONSTANCIA DE INEXISTENCIA DE REGISTRO DE NACIMIENTO'
        ],
        'COPIA_FIEL' => [
            'codigo' => 'CFI',
            'nombre' => 'COPIA FIEL DEL LIBRO'
        ],
        'COPIAS_CERTIFICADAS' => [
            'codigo' => 'COP',
            'nombre' => 'COPIAS CERTIFICADAS'
        ],

        // --- 3. CAJA Y PASES DE PAGO ---
        'EXPEDICION_PASES_CAJA' => [
            'codigo' => 'EPC',
            'nombre' => 'EXPEDICIÓN DE PASES DE CAJA'
        ],
        'PASES_CAJA_CONSTANCIAS' => [
            'codigo' => 'PCC',
            'nombre' => 'PASES DE CAJA PARA CONSTANCIAS (DEUDOR, NAC., MAT., DESC.)'
        ],

        // --- 4. BÚSQUEDAS, ARCHIVO Y DIGITALIZACIÓN ---
        'BUSQUEDA_ARCHIVO' => [
            'codigo' => 'BAR',
            'nombre' => 'BÚSQUEDA DE ACTAS EN ARCHIVO DE REGISTRO CIVIL'
        ],
        'BUSQUEDA_SISTEMA' => [
            'codigo' => 'BSI',
            'nombre' => 'BÚSQUEDA DE ACTA EN SISTEMA'
        ],
        'ACTA_DIGITALIZADA' => [
            'codigo' => 'DIG',
            'nombre' => 'ACTAS ESCANEADAS O DIGITALIZADAS'
        ],
        'DOCUMENTO_ARCHIVADO' => [
            'codigo' => 'DOC',
            'nombre' => 'DOCUMENTOS ARCHIVADOS'
        ],
        'EXPEDIENTES_PENDIENTES' => [
            'codigo' => 'EPN',
            'nombre' => 'EXPEDIENTES PENDIENTES'
        ],

        // --- 5. CORRECCIONES Y CURP ---
        'CORRECCION_OFICIALES' => [
            'codigo' => 'COR',
            'nombre' => 'CORRECCIONES EN SISTEMA DE OFICIALES'
        ],
        'CORRECCIONES_REALIZADAS' => [
            'codigo' => 'CRZ',
            'nombre' => 'CORRECCIONES REALIZADAS'
        ],
        'CORRECCIONES_CURP' => [
            'codigo' => 'CCU',
            'nombre' => 'CORRECCIONES DE CURP'
        ],
        'ACTUALIZACIONES_CURP' => [
            'codigo' => 'ACU',
            'nombre' => 'ACTUALIZACIONES DE CURP'
        ],
        'CURP_BIOMETRICO' => [
            'codigo' => 'CUR',
            'nombre' => 'REGISTRO BIOMÉTRICO DE DATOS DE CURP'
        ],
        'CORRECCIONES_ADMINISTRATIVAS' => [
            'codigo' => 'CAD',
            'nombre' => 'CORRECCIONES ADMINISTRATIVAS'
        ],

        // --- 6. ATENCIÓN CIUDADANA, OFICIOS Y GESTIÓN INSTITUCIONAL ---
        'ATENCION_PUBLICO' => [
            'codigo' => 'APU',
            'nombre' => 'ATENCIÓN AL PÚBLICO EN GENERAL'
        ],
        'LLAMADAS_ATENDIDAS' => [
            'codigo' => 'LLA',
            'nombre' => 'LLAMADAS ATENDIDAS'
        ],
        'OFICIOS_RECIBIDOS' => [
            'codigo' => 'OFI',
            'nombre' => 'OFICIOS RECIBIDOS'
        ],
        'OFICIOS_MIGRACION' => [
            'codigo' => 'OMI',
            'nombre' => 'OFICIOS DE MIGRACIÓN CONTESTADOS'
        ],
        'CONTESTACION_OFICIOS' => [
            'codigo' => 'COF',
            'nombre' => 'CONTESTACIÓN DE OFICIOS'
        ],
        'CORREOS_OFICIALES' => [
            'codigo' => 'COA',
            'nombre' => 'CORREOS OFICIALES ATENDIDOS'
        ],
        'SOLICITUDES_DIRECCION_GENERAL' => [
            'codigo' => 'SDG',
            'nombre' => 'SOLICITUDES ENVIADAS A LA DIRECCIÓN GENERAL'
        ],
        'ASUNTOS_ADMINISTRATIVOS' => [
            'codigo' => 'ASA',
            'nombre' => 'ASUNTOS ADMINISTRATIVOS ATENDIDOS'
        ],
        'COMUNICACIONES_OTRAS_DIRECCIONES' => [
            'codigo' => 'COD',
            'nombre' => 'COMUNICACIONES Y SOLICITUDES DE OTRAS DIRECCIONES/SECRETARÍAS'
        ],
        'BRIGADAS_ACTIVIDADES' => [
            'codigo' => 'BAI',
            'nombre' => 'BRIGADAS Y ACTIVIDADES INSTITUCIONALES'
        ],
        'INVITACIONES' => [
            'codigo' => 'INV',
            'nombre' => 'INVITACIONES'
        ],
        'EVENTOS' => [
            'codigo' => 'EVE',
            'nombre' => 'EVENTOS'
        ],
        'REUNIONES' => [
            'codigo' => 'REU',
            'nombre' => 'REUNIONES'
        ],
        'CASO_INCIDENCIA' => [
            'codigo' => 'INC',
            'nombre' => 'CASOS DE INCIDENCIAS'
        ],
        'FORMATOS_ENTREGADOS' => [
            'codigo' => 'FMT',
            'nombre' => 'FORMATOS ENTREGADOS'
        ],
        'INCIDENCIA_FORMATOS' => [
            'codigo' => 'INF',
            'nombre' => 'INCIDENCIAS RELACIONADAS CON FORMATOS'
        ],
        'OTRO' => [
            'codigo' => 'PET',
            'nombre' => 'OTRO TRÁMITE / PETICIÓN GENERAL'
        ]
    ];

    /**
     * Retorna la lista de trámites reacomodada automáticamente según su FRECUENCIA HISTÓRICA.
     * Los trámites más usados aparecen al principio.
     * 
     * @return array
     */
    public static function getCatalogoOrdenadoPorFrecuencia(): array {
        $frecuencias = [];
        try {
            $pdo = Database::getReadConnection();
            $stmt = $pdo->query("SELECT tipo_peticion, COUNT(*) as total 
                                 FROM peticiones_ventanilla 
                                 WHERE deleted_at IS NULL 
                                 GROUP BY tipo_peticion");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $frecuencias[$row['tipo_peticion']] = (int)$row['total'];
            }
        } catch (\Throwable $e) {
            $frecuencias = [];
        }

        $lista = [];
        foreach (self::TRAMITES as $clave => $item) {
            $frec = $frecuencias[$clave] ?? 0;
            $badgeFrec = $frec > 0 ? " ({$frec} reg.)" : "";
            $lista[] = [
                'clave'       => $clave,
                'codigo'      => $item['codigo'],
                'nombre'      => $item['nombre'],
                'valor'       => "[{$item['codigo']}] {$item['nombre']}{$badgeFrec}",
                'frecuencia'  => $frec
            ];
        }

        // Ordenar: primero por mayor frecuencia histórica DESC, luego alfabéticamente por nombre ASC
        usort($lista, function ($a, $b) {
            if ($a['frecuencia'] !== $b['frecuencia']) {
                return $b['frecuencia'] <=> $a['frecuencia'];
            }
            return strcmp($a['nombre'], $b['nombre']);
        });

        return $lista;
    }

    /**
     * Genera un folio simplificado y atómico en formato:
     * [3 LETRAS TRAMITE]-[YYMMDD]-[CONSECUTIVO DIARIO 3 DÍGITOS]
     * Ejemplo: FOR-260819-001, BSI-260819-001
     * 
     * @param string $tipoTramite Clave del tipo de trámite
     * @return string
     */
    public static function generarFolioSimplificado(string $tipoTramite): string {
        $tipoTramite = strtoupper(trim($tipoTramite));
        $codigo = self::TRAMITES[$tipoTramite]['codigo'] ?? 'PET';
        
        $fechaCompacta = date('ymd'); // Ej: 260819 (Año 26, Mes 08, Día 19)
        $moduloKey = "pv_{$codigo}_{$fechaCompacta}";
        $prefix = "{$codigo}-{$fechaCompacta}-";
        
        return Database::generateFolio($moduloKey, $prefix, 3);
    }

    /**
     * Valida de manera estricta los campos de la petición de ventanilla.
     * 
     * @param array $data Datos a validar
     * @return array ['valido' => bool, 'error' => string|null, 'data' => array]
     */
    public static function validar(array $data): array {
        $nombre = mb_strtoupper(trim($data['solicitante_nombre'] ?? ''), 'UTF-8');
        $curp = strtoupper(trim($data['solicitante_curp'] ?? ''));
        $telefono = preg_replace('/[^0-9]/', '', trim($data['solicitante_telefono'] ?? ''));
        $tipo = strtoupper(trim($data['tipo_peticion'] ?? ''));
        $detalle = mb_strtoupper(trim($data['detalle'] ?? ''), 'UTF-8');
        $estatus = strtoupper(trim($data['estatus'] ?? 'PENDIENTE'));

        // 1. Validación de Nombre del Solicitante
        if (empty($nombre) || mb_strlen($nombre, 'UTF-8') < 3) {
            return ['valido' => false, 'error' => 'El nombre del solicitante debe tener al menos 3 caracteres.', 'data' => []];
        }
        if (mb_strlen($nombre, 'UTF-8') > 150) {
            return ['valido' => false, 'error' => 'El nombre del solicitante no debe exceder 150 caracteres.', 'data' => []];
        }
        if (!preg_match('/^[A-ZÁÉÍÓÚÑÜ\s\.\,\-]+$/u', $nombre)) {
            return ['valido' => false, 'error' => 'El nombre del solicitante solo debe contener letras y espacios.', 'data' => []];
        }

        // 2. Validación de CURP (Opcional, pero si se captura debe ser válida)
        if (!empty($curp)) {
            if (strlen($curp) !== 18) {
                return ['valido' => false, 'error' => 'La CURP debe tener exactamente 18 caracteres alfanuméricos.', 'data' => []];
            }
            $regexCurp = '/^[A-Z]{4}[0-9]{6}[HM][A-Z]{2}[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9][0-9]$/';
            if (!preg_match($regexCurp, $curp)) {
                return ['valido' => false, 'error' => 'El formato de la CURP ingresada no es válido según RENAPO.', 'data' => []];
            }
        }

        // 3. Validación de Teléfono (Opcional, pero si se captura debe tener 10 dígitos)
        if (!empty($telefono)) {
            if (strlen($telefono) !== 10) {
                return ['valido' => false, 'error' => 'El teléfono de contacto debe contener 10 dígitos numéricos.', 'data' => []];
            }
        }

        // 4. Validación de Tipo de Trámite
        if (empty($tipo) || !isset(self::TRAMITES[$tipo])) {
            return ['valido' => false, 'error' => 'Seleccione un tipo de petición o trámite válido del catálogo.', 'data' => []];
        }

        // 5. Validación de Detalle
        if (empty($detalle) || mb_strlen($detalle, 'UTF-8') < 4) {
            return ['valido' => false, 'error' => 'El detalle o referencia del trámite debe tener al menos 4 caracteres.', 'data' => []];
        }
        if (mb_strlen($detalle, 'UTF-8') > 255) {
            return ['valido' => false, 'error' => 'El detalle no debe exceder los 255 caracteres.', 'data' => []];
        }

        return [
            'valido' => true,
            'error' => null,
            'data' => [
                'solicitante_nombre'   => $nombre,
                // La CURP se almacena cifrada (AES-256) con blind index para búsqueda exacta.
                'solicitante_curp'     => !empty($curp) ? Encryption::encrypt($curp) : null,
                'solicitante_curp_bindex' => !empty($curp) ? Encryption::getBlindIndex($curp) : null,
                'solicitante_telefono' => !empty($telefono) ? $telefono : null,
                'tipo_peticion'        => $tipo,
                'detalle'              => $detalle,
                'estatus'              => in_array($estatus, ['PENDIENTE', 'EN_PROCESO', 'ENTREGADO', 'CANCELADO'], true) ? $estatus : 'PENDIENTE'
            ]
        ];
    }

    /**
     * Genera la estructura consolidada del REPORTE DIARIO para una fecha específica.
     * Calcula los totales procesados en ventanilla y los módulos del ERP.
     * 
     * @param string|null $fecha Fecha en formato YYYY-MM-DD (por defecto hoy)
     * @return array
     */
    public static function getReporteDiario(?string $fecha = null): array {
        if (empty($fecha)) {
            $fecha = date('Y-m-d');
        }

        $pdo = Database::getReadConnection();
        $conteos = [];

        try {
            $stmt = $pdo->prepare("SELECT tipo_peticion, COUNT(*) as total 
                                   FROM peticiones_ventanilla 
                                   WHERE DATE(creado_en) = ? AND deleted_at IS NULL 
                                   GROUP BY tipo_peticion");
            $stmt->execute([$fecha]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $conteos[$row['tipo_peticion']] = (int)$row['total'];
            }
        } catch (\Throwable $e) {
            $conteos = [];
        }

        $filas = [];
        $granTotal = 0;

        foreach (self::TRAMITES as $clave => $item) {
            $total = $conteos[$clave] ?? 0;
            $granTotal += $total;
            $filas[] = [
                'clave'   => $clave,
                'codigo'  => $item['codigo'],
                'nombre'  => $item['nombre'],
                'total'   => $total
            ];
        }

        return [
            'fecha'      => $fecha,
            'fecha_fmt'  => date('d/m/Y', strtotime($fecha)),
            'filas'      => $filas,
            'gran_total' => $granTotal
        ];
    }

    /**
     * Matriz de asignación de trámites de ventanilla a los módulos operativos correspondientes.
     */
    public const MODULOS_MAP = [
        'inexistencias' => [
            'CONSTANCIA_DESCENDENCIA',
            'CONSTANCIA_DEUDOR_MOROSO',
            'CONSTANCIA_INEXISTENCIA_MATRIMONIO',
            'CONSTANCIA_INEXISTENCIA_NACIMIENTO',
            'PASES_CAJA_CONSTANCIAS'
        ],
        'foraneas' => [
            'ACTA_FORANEA',
            'ACTAS_LOCALES_FORANEAS_ENTREGADAS'
        ],
        'actas_locales' => [
            'COPIA_FIEL',
            'COPIAS_CERTIFICADAS',
            'ACTAS_ELABORADAS_ENTREGADAS'
        ],
        'curp' => [
            'CORRECCIONES_CURP',
            'ACTUALIZACIONES_CURP',
            'CURP_BIOMETRICO'
        ],
        'nacimientos' => [
            'REGISTRO_NACIMIENTO',
            'REGISTRO_NACIMIENTO_HG',
            'SOLICITUD_EXTEMPORANEO',
            'ACTA_FIRMADA_EXTEMPORANEO',
            'EXPEDIENTES_NACIMIENTO'
        ],
        'matrimonios' => [
            'EXPEDIENTES_MATRIMONIO',
            'BODAS_REALIZADAS'
        ],
        'divorcios' => [
            'EXPEDIENTES_DIVORCIO',
            'CAPTURA_DIVORCIO',
            'DIVORCIO_ADMINISTRATIVO',
            'DIVORCIO_JUDICIAL'
        ],
        'defunciones' => [
            'REGISTRO_DEFUNCION',
            'ACTA_CERTIFICADA_DEFUNCION'
        ],
        'inscripciones' => [
            'INSCRIPCION_NACIMIENTO'
        ],
        'reconocimientos' => [
            'IDENTIDAD_GENERO'
        ],
        'peticiones' => [
            'CORRECCION_OFICIALES',
            'CORRECCIONES_REALIZADAS',
            'CORRECCIONES_ADMINISTRATIVAS',
            'BUSQUEDA_ARCHIVO',
            'BUSQUEDA_SISTEMA',
            'ACTA_DIGITALIZADA',
            'DOCUMENTO_ARCHIVADO',
            'EXPEDIENTES_PENDIENTES',
            'EXPEDICION_PASES_CAJA',
            'ACTA_CAPTURISTA',
            'ASESORIA_CIUDADANA',
            'ATENCION_MODULO',
            'CANALIZACION_INSTITUCIONAL',
            'RECEPCION_OFICIOS',
            'OFICIOS_RESPUESTA',
            'ATENCION_GENERAL',
            'CORREOS_OFICIALES',
            'SOLICITUDES_DIRECCION_GENERAL',
            'ASUNTOS_ADMINISTRATIVOS',
            'COMUNICACIONES_OTRAS_DIRECCIONES',
            'BRIGADAS_ACTIVIDADES',
            'INVITACIONES',
            'EVENTOS',
            'REUNIONES',
            'CASO_INCIDENCIA',
            'FORMATOS_ENTREGADOS',
            'INCIDENCIA_FORMATOS',
            'OTRO'
        ]
    ];

    /**
     * Retorna los tipos de trámite pertenecientes a un módulo operativo.
     */
    public static function getTramitesPorModulo(string $modulo): array {
        return self::MODULOS_MAP[$modulo] ?? [];
    }

    /**
     * Retorna el nombre del módulo asignado a un tipo de trámite.
     */
    public static function getModuloPorTramite(string $tipoPeticion): string {
        foreach (self::MODULOS_MAP as $modulo => $tramites) {
            if (in_array($tipoPeticion, $tramites, true)) {
                return $modulo;
            }
        }
        return 'peticiones';
    }

    /**
     * Retorna el conteo de peticiones activas (no entregadas/canceladas) para un módulo.
     */
    public static function getConteoPendientesPorModulo(string $modulo): int {
        $tramites = self::getTramitesPorModulo($modulo);
        if (empty($tramites)) {
            return 0;
        }

        try {
            $pdo = Database::getReadConnection();
            $inClause = implode(',', array_fill(0, count($tramites), '?'));
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM peticiones_ventanilla 
                                   WHERE tipo_peticion IN ($inClause) 
                                     AND estatus IN ('PENDIENTE', 'EN_PROCESO') 
                                     AND deleted_at IS NULL");
            $stmt->execute($tramites);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
