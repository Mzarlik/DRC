<?php
// public/turnos.php — pantalla pública de turnos (sin autenticación, solo lectura).
// Muestra el turno en atención, los próximos y la fila de espera. Auto-refresca.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="60">
    <title>Turnos de Atención - Registro Civil</title>
    <link href="../assets/css/fonts.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0f1f38; color: #fff; min-height: 100vh; display: flex; flex-direction: column; }
        .header { background: #ffffff; color: #0f1f38; text-align: center; padding: 18px; }
        .header h1 { font-size: 1.6rem; font-weight: 900; letter-spacing: 1px; }
        .header p { font-size: .95rem; opacity: .8; }
        .main { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 30px; }
        .ahora-label { font-size: 1.4rem; text-transform: uppercase; letter-spacing: 4px; opacity: .9; }
        #folioAhora { font-size: 9rem; font-weight: 900; letter-spacing: 8px; line-height: 1.1; margin: 10px 0 6px; }
        #moduloAhora { font-size: 1.8rem; font-weight: 700; color: #ffd166; }
        #ventanillaAhora { font-size: 1.2rem; margin-top: 6px; opacity: .85; }
        .empty { font-size: 2.2rem; opacity: .45; font-weight: 700; }
        .bottom { display: flex; width: 100%; gap: 24px; padding: 20px 30px 30px; }
        .panel { flex: 1; background: #16294a; border-radius: 14px; padding: 20px 24px; min-height: 190px; }
        .panel h2 { font-size: 1.05rem; text-transform: uppercase; letter-spacing: 2px; opacity: .8; margin-bottom: 14px; border-bottom: 1px solid #2a4170; padding-bottom: 8px; }
        .panel .item { display: flex; justify-content: space-between; align-items: center; font-size: 1.15rem; padding: 6px 0; border-bottom: 1px dashed #2a4170; }
        .panel .item .folio { font-weight: 900; font-size: 1.25rem; letter-spacing: 2px; }
        .panel .item .mod { opacity: .75; font-size: .95rem; }
        .waiting-count { font-size: 3.4rem; font-weight: 900; color: #ffd166; }
        .ultimo { opacity: .55; }
        #reloj { font-size: 1.1rem; font-weight: 600; letter-spacing: 2px; opacity: .8; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>DIRECCIÓN DE REGISTRO CIVIL</h1>
        <p>Sistema de turnos de ventanilla</p>
    </div>

    <div class="main">
        <div class="ahora-label">Ahora en atención</div>
        <div id="folioAhora" class="empty">---</div>
        <div id="moduloAhora">&nbsp;</div>
        <div id="ventanillaAhora">&nbsp;</div>
        <div id="reloj"></div>
    </div>

    <div class="bottom">
        <div class="panel">
            <h2>Próximos turnos</h2>
            <div id="proximos"><p class="empty" style="font-size:1rem;">Sin turnos en espera</p></div>
        </div>
        <div class="panel" style="text-align:center;">
            <h2>Turnos en espera</h2>
            <div class="waiting-count" id="enEspera">0</div>
        </div>
        <div class="panel">
            <h2>Últimos llamados</h2>
            <div id="ultimos"><p class="empty ultimo" style="font-size:1rem;">Aún no hay llamados</p></div>
        </div>
    </div>

    <script>
    const $ = (id) => document.getElementById(id);
    const vacio = { 'folio': null, 'modulo_atencion': null, 'ventanilla': null };

    function actualizarReloj() {
        const ahora = new Date();
        $('reloj').textContent = ahora.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }

    function pintarAtendiendo(a) {
        if (a && a.folio) {
            $('folioAhora').textContent = a.folio;
            $('folioAhora').className = '';
            $('moduloAhora').textContent = a.modulo_atencion || '';
            $('ventanillaAhora').textContent = a.ventanilla ? 'VENTANILLA ' + a.ventanilla : '';
        } else {
            $('folioAhora').textContent = '---';
            $('folioAhora').className = 'empty';
            $('moduloAhora').innerHTML = '&nbsp;';
            $('ventanillaAhora').innerHTML = '&nbsp;';
        }
    }

    function cargar() {
        fetch('api/turnos_pantalla.php')
            .then(r => r.json())
            .then(d => {
                if (d.status !== 'success') return;
                pintarAtendiendo(d.atendiendo);

                $('enEspera').textContent = d.en_espera;

                const prox = $('proximos');
                prox.innerHTML = '';
                if (d.proximos && d.proximos.length) {
                    d.proximos.forEach(t => {
                        const div = document.createElement('div');
                        div.className = 'item';
                        div.innerHTML = `<span class="folio">${t.folio}</span><span class="mod">${t.modulo_atencion}</span>`;
                        prox.appendChild(div);
                    });
                } else {
                    prox.innerHTML = '<p class="empty" style="font-size:1rem;">Sin turnos en espera</p>';
                }

                const ult = $('ultimos');
                ult.innerHTML = '';
                if (d.ultimos && d.ultimos.length) {
                    d.ultimos.forEach(t => {
                        const div = document.createElement('div');
                        div.className = 'item ultimo';
                        div.innerHTML = `<span class="folio">${t.folio}</span><span class="mod">${t.modulo_atencion}</span>`;
                        ult.appendChild(div);
                    });
                } else {
                    ult.innerHTML = '<p class="empty ultimo" style="font-size:1rem;">Aún no hay llamados</p>';
                }
            })
            .catch(() => {});
    }

    actualizarReloj();
    setInterval(actualizarReloj, 1000);
    cargar();
    setInterval(cargar, 10000);
    </script>
</body>
</html>
