<?php
// scripts/seed_demo_data.php
// Sembrador de datos demostrativos para ERP DRC (Dirección de Registro Civil)
// Genera datos institucionales coherentes con cifrado de CURP, blind indexes y FKs.

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Encryption.php';
require_once __DIR__ . '/../core/Utils.php';
require_once __DIR__ . '/../core/Auditoria.php';

use Core\Database;
use Core\Encryption;
use Core\Utils;

try {
    $pdo = Database::getConnection();
    echo "======================================================\n";
    echo " INICIANDO SEED DE DATOS INSTITUCIONALES - ERP DRC\n";
    echo "======================================================\n\n";

    // 1. Obtener usuario administrador para autorías
    $adminUser = $pdo->query("SELECT id FROM usuarios WHERE rol = 'ADMIN' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $adminId = $adminUser ? (int)$adminUser['id'] : 1;

    // 2. Insertar o actualizar Ciudadanos
    echo "[1/8] Sembrando Ciudadanos con CURP Cifrada...\n";
    $ciudadanosData = [
        ['JUAN CARLOS', 'HERNÁNDEZ', 'PÉREZ', 'M', '1990-05-14', 'VIVO', 'HEPJ900514HDFRRN01'],
        ['MARÍA GUADALUPE', 'LÓPEZ', 'GÓMEZ', 'F', '1992-09-22', 'VIVO', 'LOGM920922MDFPZR03'],
        ['ROBERTO', 'MARTÍNEZ', 'SÁNCHEZ', 'M', '1985-03-10', 'VIVO', 'MASR850310HDFNNB02'],
        ['ANA PATRICIA', 'GARCÍA', 'TORRES', 'F', '1988-11-30', 'VIVO', 'GATA881130MDFRNR05'],
        ['CARLOS EDUARDO', 'RAMÍREZ', 'CRUZ', 'M', '1995-07-18', 'VIVO', 'RACC950718HDFMZL08'],
        ['SOFÍA VALENTINA', 'HERNÁNDEZ', 'LÓPEZ', 'F', '2020-01-15', 'VIVO', 'HELS200115MDFRRN09'],
        ['MATEO ALEXANDER', 'MARTÍNEZ', 'GARCÍA', 'M', '2022-08-10', 'VIVO', 'MAGM220810HDFNNB04'],
        ['FRANCISCO JAVIER', 'MORALES', 'VÁZQUEZ', 'M', '1955-12-04', 'FINADO', 'MOVF551204HDFRZS06'],
        ['ELENA', 'CASTILLO', 'RIVERA', 'F', '1960-04-25', 'FINADO', 'CARE600425MDFSTN07'],
        ['DIEGO ALBERTO', 'DOMÍNGUEZ', 'FLORES', 'M', '1998-02-12', 'VIVO', 'DOFD980212HDFMLR01'],
        ['VALERIA', 'ORTIZ', 'MENDOZA', 'F', '1999-06-19', 'VIVO', 'OIMV990619MDFTRD02'],
        ['JORGE LUIS', 'RANGEL', 'ACOSTA', 'M', '1978-10-05', 'VIVO', 'RAAJ781005HDFNRL03']
    ];

    $ciudadanosIds = [];
    foreach ($ciudadanosData as $c) {
        $curpEnc = Encryption::encrypt($c[6]);
        
        // Verificar si ya existe por nombre o curp
        $stmtCheck = $pdo->prepare("SELECT id FROM ciudadanos WHERE nombre = ? AND apellido_paterno = ? AND apellido_materno = ?");
        $stmtCheck->execute([$c[0], $c[1], $c[2]]);
        $existingId = $stmtCheck->fetchColumn();

        if ($existingId) {
            $ciudadanosIds[] = (int)$existingId;
            // Asegurar estado vital
            $pdo->prepare("UPDATE ciudadanos SET estado_vital = ?, curp = ? WHERE id = ?")->execute([$c[5], $curpEnc, $existingId]);
        } else {
            $stmtIns = $pdo->prepare("INSERT INTO ciudadanos (curp, nombre, apellido_paterno, apellido_materno, sexo, fecha_nacimiento, estado_vital, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmtIns->execute([$curpEnc, $c[0], $c[1], $c[2], $c[3], $c[4], $c[5]]);
            $ciudadanosIds[] = (int)$pdo->lastInsertId();
        }
    }
    echo "  -> " . count($ciudadanosIds) . " Ciudadanos listos en BD.\n";

    // 3. Sembrando Registro de Actos (Nacimientos, Matrimonios, Divorcios, Defunciones)
    echo "[2/8] Sembrando Actos del Registro Civil...\n";

    // Nacimiento: Sofía Valentina (hija de Juan Carlos y María Guadalupe)
    $stmtNac = $pdo->prepare("SELECT id FROM nacimientos WHERE numero_acta = 'NAC-2020-00101'");
    if (!$stmtNac->fetchColumn()) {
        $pdo->prepare("INSERT INTO nacimientos (numero_acta, ciudadano_id, padre_id, madre_id, lugar_nacimiento, fecha_registro, usuario_registro) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute(['NAC-2020-00101', $ciudadanosIds[5], $ciudadanosIds[0], $ciudadanosIds[1], 'HOSPITAL MATERNO INFANTIL', '2020-01-20', $adminId]);
    }
    // Nacimiento: Mateo Alexander (hijo de Roberto y Ana Patricia)
    $stmtNac2 = $pdo->prepare("SELECT id FROM nacimientos WHERE numero_acta = 'NAC-2022-00205'");
    if (!$stmtNac2->fetchColumn()) {
        $pdo->prepare("INSERT INTO nacimientos (numero_acta, ciudadano_id, padre_id, madre_id, lugar_nacimiento, fecha_registro, usuario_registro) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute(['NAC-2022-00205', $ciudadanosIds[6], $ciudadanosIds[2], $ciudadanosIds[3], 'CLÍNICA CENTRAL', '2022-08-15', $adminId]);
    }

    // Matrimonio: Juan Carlos Hernández y María Guadalupe López
    $stmtMat = $pdo->prepare("SELECT id FROM matrimonios WHERE numero_acta = 'MAT-2018-00045'");
    if (!$stmtMat->fetchColumn()) {
        $pdo->prepare("INSERT INTO matrimonios (numero_acta, contrayente_1_id, contrayente_2_id, regimen_patrimonial, fecha_registro, usuario_registro) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute(['MAT-2018-00045', $ciudadanosIds[0], $ciudadanosIds[1], 'SOCIEDAD CONYUGAL', '2018-06-12', $adminId]);
    }
    // Matrimonio: Diego Alberto y Valeria
    $stmtMat2 = $pdo->prepare("SELECT id FROM matrimonios WHERE numero_acta = 'MAT-2024-00112'");
    if (!$stmtMat2->fetchColumn()) {
        $pdo->prepare("INSERT INTO matrimonios (numero_acta, contrayente_1_id, contrayente_2_id, regimen_patrimonial, fecha_registro, usuario_registro) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute(['MAT-2024-00112', $ciudadanosIds[9], $ciudadanosIds[10], 'SEPARACIÓN DE BIENES', '2024-02-14', $adminId]);
    }

    // Divorcio: Roberto Martínez y Ana Patricia García
    $stmtDiv = $pdo->prepare("SELECT id FROM divorcios WHERE numero_acta = 'DIV-2025-00018'");
    if (!$stmtDiv->fetchColumn()) {
        $pdo->prepare("INSERT INTO divorcios (numero_acta, ciudadano_1_id, ciudadano_2_id, tipo_divorcio, fecha_registro, usuario_registro) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute(['DIV-2025-00018', $ciudadanosIds[2], $ciudadanosIds[3], 'JUDICIAL', '2025-05-10', $adminId]);
    }

    // Defunción: Francisco Javier Morales
    $stmtDef = $pdo->prepare("SELECT id FROM defunciones WHERE numero_acta = 'DEF-2026-00012'");
    if (!$stmtDef->fetchColumn()) {
        $pdo->prepare("INSERT INTO defunciones (numero_acta, ciudadano_id, fecha_defuncion, causa_muerte, fecha_registro, usuario_registro) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute(['DEF-2026-00012', $ciudadanosIds[7], '2026-01-08', 'INFARTO AGUDO AL MIOCARDIO', '2026-01-10', $adminId]);
    }
    // Defunción: Elena Castillo
    $stmtDef2 = $pdo->prepare("SELECT id FROM defunciones WHERE numero_acta = 'DEF-2026-00034'");
    if (!$stmtDef2->fetchColumn()) {
        $pdo->prepare("INSERT INTO defunciones (numero_acta, ciudadano_id, fecha_defuncion, causa_muerte, fecha_registro, usuario_registro) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute(['DEF-2026-00034', $ciudadanosIds[8], '2026-02-15', 'INSUFICIENCIA RESPIRATORIA AGUDA', '2026-02-17', $adminId]);
    }
    echo "  -> Nacimientos, Matrimonios, Divorcios y Defunciones registrados.\n";

    // 4. Sembrando Actas Foráneas
    echo "[3/8] Sembrando Actas Foráneas Interestatales...\n";
    $foraneasData = [
        ['OAXACA', 'OAX-2015-88912', 'NACIMIENTO', $ciudadanosIds[4], '2026-08-01', 'VALIDADA', 'COTEJADA CON REGISTRO CIVIL DEL ESTADO DE OAXACA.'],
        ['JALISCO', 'JAL-2018-44120', 'MATRIMONIO', $ciudadanosIds[9], '2026-08-10', 'PENDIENTE', 'DOCUMENTO RECIBIDO EN VENTANILLA PARA VERIFICACIÓN DE SELLO DIGITAL.'],
        ['VERACRUZ', 'VER-2012-77319', 'NACIMIENTO', $ciudadanosIds[11], '2026-08-15', 'VALIDADA', 'FOLIO VERIFICADO EN SISTEMA NACIONAL SIDEA.'],
        ['PUEBLA', 'PUE-2021-12004', 'DEFUNCION', $ciudadanosIds[7], '2026-08-18', 'PENDIENTE', 'EXPEDIENTE EN REVISIÓN JURÍDICA.'],
        ['MICHOACÁN', 'MIC-2019-90234', 'DIVORCIO', $ciudadanosIds[2], '2026-08-19', 'PENDIENTE', 'REQUERIMIENTO DE SENTENCIA CERTIFICADA DEL JUZGADO FAMILIAR.']
    ];
    foreach ($foraneasData as $f) {
        $checkF = $pdo->prepare("SELECT id FROM foraneas WHERE numero_acta = ?");
        $checkF->execute([$f[1]]);
        if (!$checkF->fetchColumn()) {
            $pdo->prepare("INSERT INTO foraneas (estado_origen, numero_acta, tipo_acta, ciudadano_id, fecha_recepcion, estatus, observaciones, usuario_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$f[0], $f[1], $f[2], $f[3], $f[4], $f[5], $f[6], $adminId]);
        }
    }
    echo "  -> Actas foráneas sembradas.\n";

    // 5. Sembrando Constancias de Inexistencia
    echo "[4/8] Sembrando Constancias de Inexistencia / No Deudor...\n";
    $inexData = [
        ['INEXISTENCIA_NACIMIENTO', '00260819001289120034', '2026-08-05', '2026-08-15', 'RICARDO SALGADO RÍOS', 'FINALIZADO', 'BÚSQUEDA EXHAUSTIVA DE 1980 A 2000 SIN ANTECEDENTES REGISTRALES.'],
        ['INEXISTENCIA_MATRIMONIO', '00260819001289120035', '2026-08-12', '2026-08-22', 'MONSERRAT VEGA ALDANA', 'PENDIENTE', 'EN PROCESO DE DICTAMEN EN ARCHIVO CENTRAL.'],
        ['NO_DEUDOR', '00260819001289120036', '2026-08-18', '2026-08-25', 'JUAN CARLOS HERNÁNDEZ PÉREZ', 'PENDIENTE', 'SOLICITUD CIUDADANA PARA TRÁMITE DE ADOPCIÓN.'],
        ['INEXISTENCIA_DESCENDENCIA', '00260819001289120037', '2026-08-19', '2026-08-29', 'BEATRIZ ADRIANA CORDERO', 'PENDIENTE', 'REVISIÓN DE EXPEDIENTE SUCESORIO INTESTAMENTARIO.']
    ];
    foreach ($inexData as $in) {
        $checkIn = $pdo->prepare("SELECT id FROM inexistencias WHERE linea_pago = ?");
        $checkIn->execute([$in[1]]);
        if (!$checkIn->fetchColumn()) {
            $pdo->prepare("INSERT INTO inexistencias (tipo_constancia, linea_pago, fecha_tramite, fecha_llegada, nombre_completo, estatus, observaciones, usuario_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$in[0], $in[1], $in[2], $in[3], $in[4], $in[5], $in[6], $adminId]);
        }
    }
    echo "  -> Constancias de inexistencia sembradas.\n";

    // 6. Sembrando Peticiones Rápidas de Ventanilla (peticiones_ventanilla)
    echo "[5/8] Sembrando Peticiones Rápidas de Mostrador...\n";
    $peticionesVentanilla = [
        ['FOR-260819-001', $ciudadanosIds[0], 'JUAN CARLOS HERNÁNDEZ PÉREZ', 'HEPJ900514HDFRRN01', '5512345678', 'ACTA FORÁNEA', 'COPIA CERTIFICADA DE NACIMIENTO PROCEDENTE DE OAXACA (AÑO 1990).', 'PENDIENTE'],
        ['BSI-260819-002', $ciudadanosIds[1], 'MARÍA GUADALUPE LÓPEZ GÓMEZ', 'LOGM920922MDFPZR03', '5523456789', 'BÚSQUEDA EN SISTEMA', 'BÚSQUEDA DE ANTECEDENTE MATRIMONIAL DEL AÑO 2018.', 'PENDIENTE'],
        ['EXP-260819-003', $ciudadanosIds[2], 'ROBERTO MARTÍNEZ SÁNCHEZ', 'MASR850310HDFNNB02', '5534567890', 'EXPEDICIÓN URGENTE', '3 COPIAS CERTIFICADAS DE ACTA DE DIVORCIO CON SELLO INSTITUCIONAL.', 'ENTREGADO'],
        ['COR-260819-004', $ciudadanosIds[3], 'ANA PATRICIA GARCÍA TORRES', 'GATA881130MDFRNR05', '5545678901', 'CORRECCIÓN DE DATOS', 'ACLARACIÓN EN APELLIDO MATERNO (CONCORDANCIA CON CURP NACIONAL).', 'EN_PROCESO'],
        ['CON-260819-005', $ciudadanosIds[4], 'CARLOS EDUARDO RAMÍREZ CRUZ', 'RACC950718HDFMZL08', '5556789012', 'CONSTANCIA', 'CONSTANCIA DE NO DEUDOR ALIMENTARIO PARA CONCURSO PÚBLICO.', 'PENDIENTE'],
        ['OTR-260819-006', null, 'VALENTINA MORALES LUNA', '', '5567890123', 'ASESORÍA CIUDADANA', 'INFORMES SOBRE REQUISITOS PARA MATRIMONIO CIVIL EN OFICIALÍA 01.', 'ENTREGADO']
    ];

    foreach ($peticionesVentanilla as $pv) {
        $checkPv = $pdo->prepare("SELECT id FROM peticiones_ventanilla WHERE folio = ?");
        $checkPv->execute([$pv[0]]);
        if (!$checkPv->fetchColumn()) {
            $pdo->prepare("INSERT INTO peticiones_ventanilla (folio, ciudadano_id, solicitante_nombre, solicitante_curp, solicitante_telefono, tipo_peticion, detalle, estatus, usuario_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$pv[0], $pv[1], $pv[2], $pv[3], $pv[4], $pv[5], $pv[6], $pv[7], $adminId]);
        }
    }
    echo "  -> Peticiones Rápidas sembradas y activas.\n";

    // 7. Sembrando Ventanilla de Seguimiento (peticiones formales)
    echo "[6/8] Sembrando Expedientes de Ventanilla de Seguimiento...\n";
    $peticionesSeguimiento = [
        ['SEG-2026-00001', $ciudadanosIds[3], 'CORRECCION_ACTA', 'SOLICITUD DE RECTIFICACIÓN ADMINISTRATIVA DE ACTA DE NACIMIENTO POR ERROR MECANOGRÁFICO EN EL APELLIDO MATERNO.', 'EN_PROGRESO'],
        ['SEG-2026-00002', $ciudadanosIds[4], 'DIGITALIZACION', 'DIGITALIZACIÓN Y VINCULACIÓN AL SISTEMA SIDEA DE ACTA DEL LIBRO HISTÓRICO DE 1995.', 'ABIERTA'],
        ['SEG-2026-00003', $ciudadanosIds[0], 'ACLARACION', 'ACLARACIÓN DE FECHA DE REGISTRO EN ACTA DE MATRIMONIO CON VALIDACIÓN JURÍDICA.', 'CERRADA'],
        ['SEG-2026-00004', $ciudadanosIds[11], 'CORRECCION_ACTA', 'DICTAMEN PROCEDENTE DE ASIGNACIÓN DE CURP HISTÓRICA.', 'ABIERTA']
    ];

    foreach ($peticionesSeguimiento as $ps) {
        $checkPs = $pdo->prepare("SELECT id FROM peticiones WHERE folio = ?");
        $checkPs->execute([$ps[0]]);
        if (!$checkPs->fetchColumn()) {
            $pdo->prepare("INSERT INTO peticiones (folio, ciudadano_id, tipo_peticion, descripcion, estatus, usuario_asignado) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$ps[0], $ps[1], $ps[2], $ps[3], $ps[4], $adminId]);
        }
    }
    echo "  -> Expedientes de seguimiento sembrados.\n";

    // 8. Sembrando Turnos de Atención
    echo "[7/8] Sembrando Turnos de Atención en Ventanilla...\n";
    $turnosData = [
        ['A-001', 'ACTA DE NACIMIENTO', 'JUAN CARLOS HERNÁNDEZ', 'COMPLETADO', 'VENTANILLA 1'],
        ['A-002', 'ACTA FORÁNEA', 'MARÍA GUADALUPE LÓPEZ', 'ATENDIENDO', 'VENTANILLA 2'],
        ['B-001', 'CONSTANCIA / INEXISTENCIA', 'ROBERTO MARTÍNEZ', 'EN_ESPERA', 'VENTANILLA 3'],
        ['B-002', 'CURP', 'ANA PATRICIA GARCÍA', 'EN_ESPERA', 'VENTANILLA 4'],
        ['C-001', 'PETICIÓN / MESA DE AYUDA', 'CARLOS EDUARDO RAMÍREZ', 'EN_ESPERA', 'VENTANILLA 1']
    ];
    foreach ($turnosData as $t) {
        $checkT = $pdo->prepare("SELECT id FROM turnos WHERE folio = ?");
        $checkT->execute([$t[0]]);
        if (!$checkT->fetchColumn()) {
            $pdo->prepare("INSERT INTO turnos (folio, modulo_atencion, ciudadano_nombre, estado, ventanilla, usuario_atendio) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$t[0], $t[1], $t[2], $t[3], $t[4], $adminId]);
        }
    }
    echo "  -> Turnos de atención sembrados.\n";

    // 9. Sembrando Bitácora de Auditoría
    echo "[8/8] Sembrando Bitácora de Auditoría Institucional...\n";
    $accionesAudit = [
        ['Petición Rápida', 'CREAR', 'Registro de petición de ventanilla con Folio FOR-260819-001'],
        ['Petición Rápida', 'ENTREGAR', 'Entrega de trámite a solicitante Folio EXP-260819-003'],
        ['Ventanilla de Seguimiento', 'CREAR', 'Apertura de expediente formal Folio SEG-2026-00001'],
        ['Actas Foráneas', 'CREAR', 'Recepción de acta foránea de Oaxaca OAX-2015-88912'],
        ['Constancias', 'CREAR', 'Emisión de constancia de inexistencia Línea de pago 00260819001289120034'],
        ['Nacimientos', 'CREAR', 'Registro de acta de nacimiento NAC-2020-00101'],
        ['Matrimonios', 'CREAR', 'Celebración y registro de matrimonio MAT-2024-00112'],
        ['Defunciones', 'CREAR', 'Asentamiento de acta de defunción DEF-2026-00012']
    ];
    foreach ($accionesAudit as $aud) {
        \Core\Auditoria::logAccion($aud[0], $aud[1], $aud[2]);
    }
    echo "  -> Registros de auditoría sembrados exitosamente.\n\n";

    echo "======================================================\n";
    echo " SEED COMPLETADO SATISFACTORIAMENTE (100% FUNCIONAL)\n";
    echo "======================================================\n";

} catch (\Throwable $e) {
    echo "\nERROR AL SEMBRAR DATOS: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
