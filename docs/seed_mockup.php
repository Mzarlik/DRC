<?php
// docs/seed_mockup.php
// Sembrado de datos ficticios para pruebas y desarrollo (NO usar en producción).
//
// Uso:
//   php docs/seed_mockup.php            # Siembra si las tablas de negocio están vacías
//   php docs/seed_mockup.php --reset    # Limpia los datos de negocio y vuelve a sembrar
//   php docs/seed_mockup.php --usuarios # Solo crea usuarios de prueba (no toca datos)
//
// Reglas de negocio respetadas: MAYÚSCULAS, CURP cifrada AES-256-CBC determinista,
// líneas de pago como string, defunción => estado_vital FINADO, folios vía
// Database::generateFolio(), fecha de llegada vía Utils::calcularFechaLlegada().

if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la consola CLI.\n");
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Encryption.php';
require_once __DIR__ . '/../core/Utils.php';

use Core\Database;
use Core\Encryption;
use Core\Utils;

$args = array_slice($argv, 1);
$reset = in_array('--reset', $args, true);
$soloUsuarios = in_array('--usuarios', $args, true);

$pdo = Database::getConnection();

function out($msg) { echo $msg . "\n"; }
function fail($msg) { out("[ERROR] " . $msg); exit(1); }

function tablaVacia($pdo, $tabla) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $tabla");
        return (int)$stmt->fetchColumn() === 0;
    } catch (Exception $e) {
        return true;
    }
}

function columnaExiste($pdo, $tabla, $columna) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$tabla` LIKE '$columna'");
        return (bool)$stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

// ---- Generador sintético de CURP (18 caracteres, solo para pruebas) ----
function limpiarPalabra($w) {
    return strtoupper(preg_replace('/[^A-ZÑ]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $w) ?: $w));
}
function makeCurp($paterno, $materno, $nombre, $sexo, $fechaNac) {
    $p = limpiarPalabra($paterno);
    $m = limpiarPalabra($materno);
    $n = limpiarPalabra($nombre);
    $c1 = substr($p, 0, 1);
    if (in_array($c1, ['A', 'E', 'I', 'O', 'U'])) $c1 = 'X';
    $c2 = substr($m, 0, 1);
    $c3 = substr($n, 0, 1);
    // Primera vocal interna del primer apellido (4to carácter)
    $vocalInterna = 'X';
    foreach (str_split(substr($p, 1)) as $ch) {
        if (in_array($ch, ['A', 'E', 'I', 'O', 'U'])) { $vocalInterna = $ch; break; }
    }
    $fecha = date('ymd', strtotime($fechaNac));
    $g = ($sexo === 'M') ? 'H' : 'M';
    $entidad = ['DF', 'JA', 'NL', 'MC', 'SL', 'VC', 'YP', 'JC', 'GT', 'BC', 'BS', 'CH', 'CO'][abs(crc32($p)) % 12];
    $cons = function ($w) {
        $w = substr($w, 1);
        foreach (str_split($w) as $ch) {
            if (!in_array($ch, ['A', 'E', 'I', 'O', 'U'])) return $ch;
        }
        return 'X';
    };
    $hash = strtoupper(substr(hash('sha1', $p . $m . $n . $fecha), 0, 6));
    return $c1 . $c2 . $c3 . $vocalInterna . $fecha . $g . $entidad . $cons($p) . $cons($m) . $cons($n) . $hash[0] . $hash[1];
}

// ============================================================================
// 1. USUARIOS DE PRUEBA
// ============================================================================
function seedUsuarios($pdo) {
    out("== Usuarios de prueba ==");
    $usuarios = [
        ['SUPERVISOR DE PRUEBA',   'supervisor@drc.gob.mx',   'Supervisor123!', 'SUPERVISOR'],
        ['OPERADOR DE PRUEBA UNO', 'operador1@drc.gob.mx',    'Operador123!',   'OPERADOR'],
        ['OPERADOR DE PRUEBA DOS', 'operador2@drc.gob.mx',    'Operador123!',   'OPERADOR'],
    ];
    // Permisos por rol de prueba: [tickets, constancias, nacimientos, defunciones, curp, resto]
    $perfiles = [
        'SUPERVISOR' => [1, 1, 1, 1, 1, 1],
        'OPERADOR'   => [1, 1, 1, 1, 1, 0],
    ];
    $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE correo = :correo");
    $stmtIns = $pdo->prepare("INSERT INTO usuarios (nombre, correo, password_hash, rol, estatus,
        permiso_registro_nacimientos, permiso_registro_matrimonios, permiso_registro_divorcios,
        permiso_registro_defunciones, permiso_registro_inscripciones, permiso_registro_reconocimientos,
        permiso_actas_locales, permiso_actas_foraneas, permiso_constancias, permiso_curp, permiso_tickets)
        VALUES (:nombre, :correo, :hash, :rol, 1, :p1, :p2, :p3, :p4, :p5, :p6, :p7, :p8, :p9, :p10, :p11)");
    foreach ($usuarios as [$nombre, $correo, $pass, $rol]) {
        $stmtCheck->execute([':correo' => $correo]);
        $existente = $stmtCheck->fetchColumn();
        if ($existente) {
            $ids[$rol][] = (int)$existente;
            out("  Usuario existente: $correo (id $existente)");
            continue;
        }
        $p = $perfiles[$rol];
        $stmtIns->execute([
            ':nombre' => mb_strtoupper($nombre, 'UTF-8'),
            ':correo' => $correo,
            ':hash' => password_hash($pass, PASSWORD_BCRYPT),
            ':rol' => $rol,
            // p1=nacimientos p2=matrimonios p3=divorcios p4=defunciones p5=inscripciones
            // p6=reconocimientos p7=actas_locales p8=actas_foraneas p9=constancias p10=curp p11=tickets
            ':p1' => $p[2], ':p2' => $p[5], ':p3' => $p[5], ':p4' => $p[3], ':p5' => $p[5],
            ':p6' => $p[5], ':p7' => $p[5], ':p8' => $p[5], ':p9' => $p[1], ':p10' => $p[4], ':p11' => $p[0],
        ]);
        $ids[$rol][] = (int)$pdo->lastInsertId();
        out("  Creado: $correo / $pass (rol $rol)");
    }
    return $ids;
}

// ============================================================================
// 2. CATÁLOGOS DINÁMICOS (tablas si no existen + opciones)
// ============================================================================
function seedCatalogos($pdo) {
    out("== Catálogos dinámicos ==");
    $pdo->exec("CREATE TABLE IF NOT EXISTS catalogos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_interno VARCHAR(50) NOT NULL UNIQUE,
        nombre_visible VARCHAR(100),
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS catalogo_opciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        catalogo_id INT NOT NULL,
        clave VARCHAR(50) NOT NULL,
        valor VARCHAR(255) NOT NULL,
        orden INT DEFAULT 0,
        activo TINYINT(1) DEFAULT 1,
        UNIQUE KEY uq_catalogo_clave (catalogo_id, clave),
        FOREIGN KEY (catalogo_id) REFERENCES catalogos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Compatibilidad: la tabla catalogos puede existir con otro esquema (sin nombre_visible)
    $tieneVisible = (bool)$pdo->query("SHOW COLUMNS FROM catalogos LIKE 'nombre_visible'")->fetch();

    $catalogos = [
        'tipo_constancia' => ['RECTIFICACIÓN', 'Tipo de constancia de inexistencia', [
            ['INEXISTENCIA_NACIMIENTO',  'CONSTANCIA DE INEXISTENCIA DE NACIMIENTO', 1],
            ['INEXISTENCIA_MATRIMONIO',  'CONSTANCIA DE INEXISTENCIA DE MATRIMONIO', 2],
            ['INEXISTENCIA_DESCENDENCIA','CONSTANCIA DE INEXISTENCIA DE DESCENDENCIA', 3],
            ['NO_DEUDOR',                'CONSTANCIA DE NO DEUDOR', 4],
        ]],
        'tipo_peticion' => ['RECTIFICACIÓN', 'Tipo de petición/ticket', [
            ['CORRECCION_ACTA', 'CORRECCIÓN DE ACTA', 1],
            ['DIGITALIZACION',  'DIGITALIZACIÓN', 2],
            ['ACLARACION',      'ACLARACIÓN', 3],
            ['OTRO',            'OTRO', 4],
        ]],
        'regimen_patrimonial' => ['RECTIFICACIÓN', 'Régimen patrimonial de matrimonios', [
            ['SOCIEDAD_CONYUGAL', 'SOCIEDAD CONYUGAL', 1],
            ['SEPARACION_BIENES', 'SEPARACIÓN DE BIENES', 2],
        ]],
    ];

    foreach ($catalogos as $interno => [$vis, $desc, $opciones]) {
        if ($tieneVisible) {
            $pdo->prepare("INSERT IGNORE INTO catalogos (nombre_interno, nombre_visible) VALUES (?, ?)")
                ->execute([$interno, $vis]);
        } else {
            $stmtIns = $pdo->prepare("INSERT IGNORE INTO catalogos (nombre_interno) VALUES (?)");
            $stmtIns->execute([$interno]);
        }
        $catId = $pdo->query("SELECT id FROM catalogos WHERE nombre_interno = '$interno'")->fetchColumn();
        foreach ($opciones as [$clave, $valor, $orden]) {
            $pdo->prepare("INSERT IGNORE INTO catalogo_opciones (catalogo_id, clave, valor, orden, activo) VALUES (?, ?, ?, ?, 1)")
                ->execute([$catId, $clave, $valor, $orden]);
        }
        out("  Catálogo '$interno' listo (" . count($opciones) . " opciones).");
    }
}

// ============================================================================
// 3. CIUDADANOS + TRÁMITES
// ============================================================================
function seedDatos($pdo, $ids) {
    out("== Ciudadanos y trámites de prueba ==");

    // [nombre, apellido_paterno, apellido_materno, sexo, fecha_nacimiento, rolDeVida]
    // rolDeVida: A=adulto común, P=padre/madre, B=bebé (nacimiento), F=finado, S=baja lógica, E=sin CURP
    $personas = [
        ['JUAN CARLOS', 'HERNANDEZ', 'GARCIA', 'M', '1985-04-12', 'A'],
        ['MARIA FERNANDA', 'LOPEZ', 'MARTINEZ', 'F', '1988-09-23', 'A'],
        ['PEDRO ANTONIO', 'GONZALEZ', 'RODRIGUEZ', 'M', '1979-01-30', 'P'],
        ['ANA LUCIA', 'RAMIREZ', 'FLORES', 'F', '1982-07-15', 'P'],
        ['LUIS MIGUEL', 'TORRES', 'MENDOZA', 'M', '1990-11-05', 'A'],
        ['SOFIA ISABEL', 'CASTILLO', 'VARGAS', 'F', '1992-03-18', 'A'],
        ['CARLOS ALBERTO', 'REYES', 'SANTOS', 'M', '1975-12-02', 'P'],
        ['GUADALUPE', 'MORALES', 'CRUZ', 'F', '1980-06-27', 'P'],
        ['JOSE MANUEL', 'ORTIZ', 'PEREZ', 'M', '1995-08-14', 'A'],
        ['DIANA PAOLA', 'CHAVEZ', 'RAMOS', 'F', '1997-02-09', 'A'],
        ['MIGUEL ANGEL', 'NAVARRO', 'SILVA', 'M', '1968-05-21', 'P'],
        ['ELENA PATRICIA', 'DOMINGUEZ', 'AGUILAR', 'F', '1972-10-11', 'P'],
        ['RICARDO JAVIER', 'VEGA', 'RIOS', 'M', '1987-01-25', 'A'],
        ['KARLA VANESSA', 'SOTO', 'MEDINA', 'F', '1993-07-07', 'A'],
        ['FERNANDO', 'IBARRA', 'CORDOVA', 'M', '2000-04-03', 'A'],
        ['LAURA CRISTINA', 'PAZ', 'SALINAS', 'F', '1998-12-19', 'A'],
        ['ARTURO', 'BECERRA', 'ROJAS', 'M', '1983-02-14', 'A'],
        ['VERONICA', 'HERRERA', 'LUNA', 'F', '1986-08-08', 'A'],
        ['ALBERTO', 'CAMACHO', 'VILLANUEVA', 'M', '1960-03-01', 'P'],
        ['ROSA MARIA', 'SALAZAR', 'GOMEZ', 'F', '1963-09-17', 'P'],
        ['SERGIO', 'DELGADO', 'FUENTES', 'M', '1977-05-30', 'A'],
        ['PAULINA', 'ESCOBAR', 'NIETO', 'F', '1991-11-22', 'A'],
        ['HECTOR', 'FONSECA', 'BLANCO', 'M', '1955-06-01', 'F'],
        ['CRISTINA', 'ALVAREZ', 'SERRANO', 'F', '1958-01-15', 'F'],
        ['RAUL', 'QUINTERO', 'BARRAGAN', 'M', '1949-10-10', 'F'],
        ['BEATRIZ', 'RUIZ', 'CORONA', 'F', '1990-05-05', 'A'],
        ['OSCAR EDGARDO', 'MEJIA', 'PARRA', 'M', '1994-09-29', 'A'],
        ['MONICA', 'GUTIERREZ', 'TREJO', 'F', '1989-12-12', 'A'],
        ['VALENTINA', 'ACOSTA', 'ROMERO', 'F', '2003-02-27', 'A'],
        ['DIEGO', 'CABRERA', 'OLIVARES', 'M', '2001-06-16', 'A'],
        ['FELIPE', 'CORDERO', 'AVILA', 'M', '2026-01-10', 'B'],
        ['XIMENA', 'PAREDES', 'IBARRA', 'F', '2026-02-20', 'B'],
        ['RAFAEL', 'VILLALOBOS', 'PONCE', 'M', '2025-07-11', 'B'],
        ['MARISOL', 'SANTANA', 'GUZMAN', 'F', '1996-04-04', 'A'],
        ['EDUARDO', 'BARRIOS', 'ESPARZA', 'M', '1984-08-19', 'A'],
        ['PATRICIA', 'MEZA', 'ONTIVEROS', 'F', '1981-03-13', 'E'],
        ['JORGE', 'ALANIS', 'PERALTA', 'M', '1978-11-02', 'S'],
        ['IRENE', 'ZAMORA', 'QUEZADA', 'F', '1985-06-25', 'S'],
    ];

    $stmtIns = $pdo->prepare("INSERT INTO ciudadanos (curp, nombre, apellido_paterno, apellido_materno, sexo, fecha_nacimiento, estado_vital, estado)
        VALUES (:curp, :nombre, :ap, :am, :sexo, :fnac, :vital, :estado)");
    $ciudIds = [];
    $bebes = [];
    $padres = [];
    $finados = [];
    $sinCurp = 0;
    foreach ($personas as [$nombre, $ap, $am, $sexo, $fnac, $vida]) {
        $curp = null;
        if ($vida !== 'E') {
            $curp = makeCurp($ap, $am, explode(' ', $nombre)[0], $sexo, $fnac);
            // Evitar colisiones: añadir sufijo determinístico si ya se usó
            $i = 0;
            $base = $curp;
            $stmtDu = $pdo->prepare("SELECT COUNT(*) FROM ciudadanos WHERE curp = :curp");
            while (true) {
                $stmtDu->execute([':curp' => Encryption::encrypt($curp)]);
                if ((int)$stmtDu->fetchColumn() === 0) break;
                $i++;
                $curp = substr($base, 0, 16) . str_pad((string)($i), 2, '0', STR_PAD_LEFT);
            }
            $curp = Encryption::encrypt($curp);
            $sinCurp++;
        }
        $vital = ($vida === 'F') ? 'FINADO' : 'VIVO';
        $estado = ($vida === 'S') ? 0 : 1;
        $stmtIns->execute([
            ':curp' => $curp,
            ':nombre' => $nombre,
            ':ap' => $ap,
            ':am' => $am,
            ':sexo' => $sexo,
            ':fnac' => $fnac,
            ':vital' => $vital,
            ':estado' => $estado,
        ]);
        $id = (int)$pdo->lastInsertId();
        // Soft-delete: si la columna deleted_at existe (migración aplicada), marcarla en bajas
        if ($vida === 'S' && columnaExiste($pdo, 'ciudadanos', 'deleted_at')) {
            $pdo->prepare("UPDATE ciudadanos SET deleted_at = NOW() WHERE id = ?")->execute([$id]);
        }
        $ciudIds[] = $id;
        if ($vida === 'B') $bebes[] = $id;
        if ($vida === 'P') $padres[] = $id;
        if ($vida === 'F') $finados[] = $id;
    }
    out("  " . count($ciudIds) . " ciudadanos sembrados (" . count($finados) . " finados, " . count($bebes) . " bebés, 2 bajas lógicas).");

    $operadorId = $ids['OPERADOR'][0] ?? 1;
    $supervisorId = $ids['SUPERVISOR'][0] ?? 1;

    // ---- Nacimientos (bebés -> padres) ----
    out("  Trámites...");
    $insNac = $pdo->prepare("INSERT INTO nacimientos (numero_acta, ciudadano_id, padre_id, madre_id, lugar_nacimiento, fecha_registro, usuario_registro)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $lugares = ['CIUDAD VICTORIA, TAMAULIPAS', 'MATAMOROS, TAMAULIPAS', 'REYNOSA, TAMAULIPAS', 'MANTE, TAMAULIPAS'];
    foreach (array_keys($bebes) as $i) {
        $insNac->execute([
            '1' . str_pad((string)($i + 1), 5, '0', STR_PAD_LEFT),
            $bebes[$i],
            $padres[$i % 2],
            $padres[($i % 2) + 2],
            $lugares[$i % 4],
            date('Y-m-d', strtotime("-" . (rand(3, 40)) . " days")),
            $operadorId,
        ]);
    }
    out("    " . count($bebes) . " nacimientos.");

    // ---- Matrimonios / Divorcios ----
    $pares = [[5, 6], [9, 10], [13, 14], [20, 28], [22, 27]];
    $insMat = $pdo->prepare("INSERT INTO matrimonios (numero_acta, contrayente_1_id, contrayente_2_id, regimen_patrimonial, fecha_registro, usuario_registro) VALUES (?, ?, ?, ?, ?, ?)");
    $matIds = [];
    foreach ($pares as $i => [$c1, $c2]) {
        $insMat->execute([
            '2' . str_pad((string)($i + 1), 5, '0', STR_PAD_LEFT),
            $ciudIds[$c1 - 1], $ciudIds[$c2 - 1],
            ($i % 2 === 0) ? 'SOCIEDAD_CONYUGAL' : 'SEPARACION_BIENES',
            date('Y-m-d', strtotime("-" . (365 * ($i + 2)) . " days")),
            $supervisorId,
        ]);
        $matIds[] = (int)$pdo->lastInsertId();
    }
    out("    " . count($matIds) . " matrimonios.");

    $insDiv = $pdo->prepare("INSERT INTO divorcios (numero_acta, ciudadano_1_id, ciudadano_2_id, tipo_divorcio, fecha_registro, usuario_registro) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ([0, 4] as $i => $idx) {
        $insDiv->execute([
            '3' . str_pad((string)($i + 1), 5, '0', STR_PAD_LEFT),
            $ciudIds[$pares[$idx][0] - 1], $ciudIds[$pares[$idx][1] - 1],
            ($i === 0) ? 'ADMINISTRATIVO' : 'JUDICIAL',
            date('Y-m-d', strtotime("-" . (90 * ($i + 1)) . " days")),
            $supervisorId,
        ]);
    }
    out("    2 divorcios.");

    // ---- Defunciones (finados) ----
    $causas = ['PARO CARDIORESPIRATORIO', 'NEUMONÍA ATÍPICA', 'INSUFICIENCIA RENAL CRÓNICA'];
    $insDef = $pdo->prepare("INSERT INTO defunciones (numero_acta, ciudadano_id, fecha_defuncion, causa_muerte, fecha_registro, usuario_registro) VALUES (?, ?, ?, ?, ?, ?)");
    foreach (array_keys($finados) as $i) {
        $insDef->execute([
            '4' . str_pad((string)($i + 1), 5, '0', STR_PAD_LEFT),
            $finados[$i],
            date('Y-m-d', strtotime("-" . (rand(10, 200)) . " days")),
            $causas[$i % 3],
            date('Y-m-d', strtotime("-" . rand(8, 190) . " days")),
            $operadorId,
        ]);
    }
    out("    " . count($finados) . " defunciones (estado_vital FINADO).");

    // ---- Inscripciones (extranjeros) ----
    $extranjeros = [
        [14, 'ESPAÑA', 'ACTA DE NACIMIENTO ESPAÑOLA NÚM. 2024/0331, APOSTILLADA EN MADRID'],
        [16, 'ESTADOS UNIDOS', 'CERTIFICADO DE NACIMIENTO DE TEXAS, APOSTILLA HOUSTON NÚM. A-88231'],
        [34, 'VENEZUELA', 'ACTA DE NACIMIENTO VENEZOLANA, LEGALIZADA EN CARACAS NÚM. 45912'],
    ];
    $insIns = $pdo->prepare("INSERT INTO inscripciones (numero_acta, ciudadano_id, pais_origen, documento_extranjero, fecha_registro, usuario_registro) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($extranjeros as $i => [$ci, $pais, $doc]) {
        $insIns->execute([
            '5' . str_pad((string)($i + 1), 5, '0', STR_PAD_LEFT),
            $ciudIds[$ci - 1], $pais, $doc,
            date('Y-m-d', strtotime("-" . rand(5, 60) . " days")),
            $operadorId,
        ]);
    }
    out("    3 inscripciones.");

    // ---- Reconocimientos ----
    $insRec = $pdo->prepare("INSERT INTO reconocimientos (numero_acta, reconocido_id, reconocedor_id, fecha_registro, usuario_registro) VALUES (?, ?, ?, ?, ?)");
    foreach ([[15, 3], [30, 11]] as $i => [$rec, $recRecon]) {
        $insRec->execute([
            '6' . str_pad((string)($i + 1), 5, '0', STR_PAD_LEFT),
            $ciudIds[$rec - 1], $ciudIds[$recRecon - 1],
            date('Y-m-d', strtotime("-" . rand(20, 120) . " days")),
            $supervisorId,
        ]);
    }
    out("    2 reconocimientos.");

    // ---- Actas foráneas ----
    $foraneas = [
        [12, 'GUANAJUATO', 'NACIMIENTO', 'VALIDADA', 'ACTA ENVIADA POR LA OFICINA DE LEÓN, GTO.'],
        [17, 'VERACRUZ', 'MATRIMONIO', 'PENDIENTE', 'ACTA EN REVISIÓN PARA SU VALIDACIÓN.'],
        [21, 'NUEVO LEÓN', 'DEFUNCION', 'PENDIENTE', 'VERIFICAR DATOS DEL FINADO CON LA OFICINA ORIGINARIA.'],
        [25, 'JALISCO', 'DIVORCIO', 'RECHAZADA', 'DOCUMENTO ILEGIBLE; SE SOLICITÓ NUEVO ENVÍO.'],
    ];
    $insFor = $pdo->prepare("INSERT INTO foraneas (numero_acta, estado_origen, tipo_acta, ciudadano_id, fecha_recepcion, estatus, observaciones, usuario_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($foraneas as $i => [$ci, $edo, $tipo, $estatus, $obs]) {
        $insFor->execute([
            '7' . str_pad((string)($i + 1), 5, '0', STR_PAD_LEFT),
            $edo, $tipo, $ciudIds[$ci - 1],
            date('Y-m-d', strtotime("-" . rand(2, 45) . " days")),
            $estatus, $obs, $operadorId,
        ]);
    }
    out("    4 actas foráneas.");

    // ---- Inexistencias (fecha de llegada con regla de negocio) ----
    $tipos = ['INEXISTENCIA_NACIMIENTO', 'INEXISTENCIA_MATRIMONIO', 'INEXISTENCIA_DESCENDENCIA', 'NO_DEUDOR'];
    $stmtCfg = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'DIAS_ESPERA_INEXISTENCIA'");
    $diasEspera = (int)(($stmtCfg && ($v = $stmtCfg->fetchColumn())) ? $v : 15);
    $lineas = ['PAGO-2026-000001', 'PAGO-2026-000002', 'PAGO-2026-000003', 'PAGO-2026-000004', 'PAGO-2026-000005', 'PAGO-2026-000006'];
    $insInex = $pdo->prepare("INSERT INTO inexistencias (tipo_constancia, linea_pago, fecha_tramite, fecha_llegada, nombre_completo, estatus, observaciones, usuario_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $nombres = [
        $personas[0][0] . ' ' . $personas[0][1] . ' ' . $personas[0][2],
        $personas[1][0] . ' ' . $personas[1][1] . ' ' . $personas[1][2],
        $personas[6][0] . ' ' . $personas[6][1] . ' ' . $personas[6][2],
        $personas[12][0] . ' ' . $personas[12][1] . ' ' . $personas[12][2],
        $personas[13][0] . ' ' . $personas[13][1] . ' ' . $personas[13][2],
        $personas[24][0] . ' ' . $personas[24][1] . ' ' . $personas[24][2],
    ];
    foreach ($nombres as $i => $nombreCompleto) {
        $fechaTramite = date('Y-m-d', strtotime("-" . rand(3, 90) . " days"));
        $fechaLlegada = Utils::calcularFechaLlegada($fechaTramite, $diasEspera);
        $estatus = ['PENDIENTE', 'FINALIZADO', 'PENDIENTE', 'CANCELADO', 'PENDIENTE', 'FINALIZADO'][$i];
        $obs = ($estatus === 'CANCELADO') ? 'SOLICITUD CANCELADA POR EL INTERESADO.' : null;
        $insInex->execute([
            $tipos[$i % 4], $lineas[$i], $fechaTramite, $fechaLlegada,
            mb_strtoupper($nombreCompleto, 'UTF-8'), $estatus, $obs, $operadorId,
        ]);
    }
    out("    6 inexistencias (llegada = trámite + $diasEspera días).");

    // ---- Trámites CURP ----
    $insCurp = $pdo->prepare("INSERT INTO tramites_curp (ciudadano_id, tipo_solicitud, estatus, fecha_registro, usuario_registro) VALUES (?, ?, ?, ?, ?)");
    $curpTramites = [
        [16, 'ALTA', 'PROCESADO'], [3, 'CORRECCION', 'PENDIENTE'], [19, 'ALTA', 'RECHAZADO'],
        [23, 'BAJA', 'PROCESADO'], [36, 'ALTA', 'PENDIENTE'],
    ];
    foreach ($curpTramites as [$ci, $tipo, $estatus]) {
        $insCurp->execute([
            $ciudIds[$ci - 1], $tipo, $estatus,
            date('Y-m-d', strtotime("-" . rand(1, 30) . " days")), $operadorId,
        ]);
    }
    out("    5 trámites CURP.");

    // ---- Peticiones (folio secuencial real) ----
    $insPet = $pdo->prepare("INSERT INTO peticiones (folio, ciudadano_id, tipo_peticion, descripcion, estatus, usuario_asignado, fecha_cierre) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $peticiones = [
        [5, 'CORRECCION_ACTA', 'EL NOMBRE DEL ACTA DE NACIMIENTO ESTÁ MAL ESCRITO; SE SOLICITA CORRECCIÓN ADMINISTRATIVA.', 'CERRADA', $supervisorId],
        [9, 'DIGITALIZACION', 'SE REQUIERE LA DIGITALIZACIÓN DEL ACTA DE MATRIMONIO PARA TRÁMITE BANCARIO.', 'EN_PROGRESO', $operadorId],
        [13, 'ACLARACION', 'INCONSISTENCIA ENTRE EL ACTA Y LA CURP; SE SOLICITA ACLARACIÓN.', 'ABIERTA', $supervisorId],
        [27, 'CORRECCION_ACTA', 'CORRECCIÓN DEL APELLIDO MATERNO EN EL ACTA DE NACIMIENTO.', 'ABIERTA', null],
        [31, 'DIGITALIZACION', 'SE SOLICITA COPIA CERTIFICADA DIGITAL DEL ACTA DE NACIMIENTO.', 'EN_PROGRESO', $operadorId],
    ];
    foreach ($peticiones as [$ci, $tipo, $desc, $estatus, $asignado]) {
        $folio = Database::generateFolio('peticiones_' . date('Y'), 'TK-' . date('Y') . '-');
        $fechaCierre = ($estatus === 'CERRADA') ? date('Y-m-d H:i:s', strtotime("-2 days")) : null;
        $insPet->execute([
            $folio, $ciudIds[$ci - 1], $tipo, $desc, $estatus, $asignado, $fechaCierre,
        ]);
    }
    out("    5 peticiones (folios TK-" . date('Y') . ").");
}

// ============================================================================
// MAIN
// ============================================================================
try {
    if ($soloUsuarios) {
        seedUsuarios($pdo);
        out("Listo. Solo usuarios.");
        exit(0);
    }

    $tieneDatos = !tablaVacia($pdo, 'ciudadanos') || !tablaVacia($pdo, 'nacimientos') || !tablaVacia($pdo, 'peticiones');
    if ($tieneDatos && !$reset) {
        fail("Ya existen datos en las tablas de negocio. Ejecuta: php docs/seed_mockup.php --reset");
    }

    if ($reset) {
        out("== Limpiando datos de negocio ==");
        $orden = ['peticiones', 'tramites_curp', 'inexistencias', 'foraneas', 'inscripciones', 'reconocimientos', 'divorcios', 'matrimonios', 'defunciones', 'nacimientos', 'ciudadanos'];
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        foreach ($orden as $tabla) {
            $pdo->exec("DELETE FROM `$tabla`");
            $pdo->exec("ALTER TABLE `$tabla` AUTO_INCREMENT = 1");
        }
        $pdo->exec("DELETE FROM folios_secuencia WHERE modulo LIKE 'peticiones_%'");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        out("  Datos de negocio limpiados.");
    }

    seedCatalogos($pdo);
    $ids = seedUsuarios($pdo);
    seedDatos($pdo, $ids);

    out("");
    out("== Resumen ==");
    $tablas = ['usuarios', 'ciudadanos', 'nacimientos', 'matrimonios', 'divorcios', 'defunciones',
               'inscripciones', 'reconocimientos', 'foraneas', 'inexistencias', 'peticiones', 'tramites_curp'];
    foreach ($tablas as $t) {
        $c = (int)$pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        out("  $t: $c");
    }
    out("");
    out("Usuarios de prueba:");
    out("  supervisor@drc.gob.mx / Supervisor123!");
    out("  operador1@drc.gob.mx / Operador123!");
    out("  operador2@drc.gob.mx / Operador123!");
    out("  admin@drc.gob.mx / Admin123! (existente en database.sql)");
    out("");
    out("Seed completado con éxito.");

} catch (Exception $e) {
    fail($e->getMessage());
}