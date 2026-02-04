<?php   
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Lima');

if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], [
    'SUPERVISION',
    'COORDINADOR GENERAL DE TUTORIA',
    'VICEPRESIDENCIA ACADEMICA'
])) {
    die('Acceso no autorizado');
}

require_once(__DIR__ . '/../../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();

$id_semestre = $_SESSION['S_SEMESTRE'];
$mes_filtro = $_GET['mes'] ?? 'diciembre';

$sql_carreras = "SELECT id_car, nom_car FROM carrera ORDER BY nom_car";
$carreras = $conexion->conexion->query($sql_carreras);

function porcentaje($parcial, $total) {
    return $total > 0 ? round(($parcial / $total) * 100, 2) : 0;
}
function colorFondoCarrera($id_car) {
    switch ($id_car) {
        case 1: return '#fbd9b1';
        case 2: return '#f6bebe';
        case 3: return '#d8bbf3';
        case 4: return '#bdd7fd';
        case 5: return '#a5f0ad';
        default: return '#dddddd';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Mensual - Progreso por Carrera</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f6f8fc; margin: 0; }
        .container { max-width: 1500px; margin: auto; padding: 10px; }
        h3 { text-align: center; margin-bottom: 20px; background-color: #2c3e50; color: white; padding: 15px; border-radius: 5px; }
        .filtro { text-align: center; margin-bottom: 20px; }
        .contenedor-grilla { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
        .bloque-carrera { padding: 15px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); color: #2c3e50; position: relative; }
        .titulo-carrera { font-weight: bold; font-size: 16px; margin-bottom: 15px; }
        .barra-contenedor { margin: 16px 0; }
        .barra-progreso { width: 100%; height: 24px; background-color: #e0e0e0; border-radius: 12px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.2); }
        .barra-fill { height: 100%; text-align: center; line-height: 24px; font-size: 13px; font-weight: bold; color:rgb(37, 38, 46); white-space: nowrap; }
        .naranja { background-color: #e67e22; }
        .amarillo { background-color: #f1c40f; }
        .verde { background-color: #27ae60; }
        .btn-ver { position: absolute; top: 10px; right: 10px; background: #1a5276; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 12px; }
        select {
            padding: 6px 10px;
            font-size: 14px;
        }
    </style>
    <script>
    function verDetalle(id_car) {
        const mes = document.getElementById("mes_filtro").value.trim();
        if (!mes) {
            alert("\u26a0\ufe0f Seleccione un mes antes de continuar.");
            return;
        }
        const url = `reportes_generales/lista_envio_informe_aula.php?id_car=${id_car}&mes=${encodeURIComponent(mes)}`;
        window.open(url, '_blank', 'width=1000,height=700');
    }
    function filtrarMes() {
        const mes = document.getElementById("mes_filtro").value;
        const paginaActual = 'index.php?pagina=reportes_generales/report_informe_aula.php';
        window.location.href = paginaActual + '&mes=' + mes;
    }
    </script>
</head>
<body>
<div class="container">
    <h3>CUMPLIMIENTO DE INFORMES MENSUALES - TUTOR DE AULA</h3>

    <div class="filtro">
        <label for="mes_filtro">Seleccionar mes:</label>
        <select id="mes_filtro" onchange="filtrarMes()">
            <option value="">----</option>
           <!--  <option value="abril" ?= $mes_filtro == 'abril' ? 'selected' : '' ?>>Abril</option>
            <option value="mayo" ?= $mes_filtro == 'mayo' ? 'selected' : '' ?>>Mayo</option>
            <option value="junio" ?= $mes_filtro == 'junio' ? 'selected' : '' ?>>Junio</option>
            <option value="julio" ?= $mes_filtro == 'julio' ? 'selected' : '' ?>>Julio</option>
            <option value="agosto" ?= $mes_filtro == 'agosto' ? 'selected' : '' ?>>Agosto</option>--> 
            <option value="septiembre" <?= $mes_filtro == 'septiembre' ? 'selected' : '' ?>>Septiembre</option>
            <option value="octubre" <?= $mes_filtro == 'octubre' ? 'selected' : '' ?>>Octubre</option>
            <option value="noviembre" <?= $mes_filtro == 'noviembre' ? 'selected' : '' ?>>Noviembre</option>
            <option value="diciembre" <?= $mes_filtro == 'diciembre' ? 'selected' : '' ?>>Diciembre</option>
        </select>
    </div>

    <?php $hay_resultados = false; ?>
    <div class="contenedor-grilla">
    <?php while ($car = $carreras->fetch_assoc()):
        $id_car = $car['id_car'];
        $bg_color = colorFondoCarrera($id_car);

        $sql_docentes = "SELECT id_doce FROM tutoria_docente_asignado WHERE id_car = $id_car AND id_semestre = $id_semestre";
        $res_docentes = $conexion->conexion->query($sql_docentes);
        $docentes = [];
        while ($d = $res_docentes->fetch_assoc()) {
            $docentes[] = $d['id_doce'];
        }

        $total = count($docentes);
        $en_proceso = 0;
        $enviado = 0;
        $conforme = 0;

        foreach ($docentes as $id_doce) {
            $sql_cargas = "SELECT id_cargalectiva FROM carga_lectiva WHERE id_doce = $id_doce AND id_semestre = $id_semestre";
            $res_cargas = $conexion->conexion->query($sql_cargas);

            while ($c = $res_cargas->fetch_assoc()) {
                $id_carga = $c['id_cargalectiva'];

                $sql_inf = "SELECT id_informe, estado_envio, id_plan_tutoria FROM tutoria_informe_mensual 
                            WHERE id_cargalectiva = $id_carga AND id_car = $id_car";
                if ($mes_filtro !== '') {
                    $sql_inf .= " AND mes_informe = '$mes_filtro'";
                }
                $sql_inf .= " ORDER BY id_informe DESC LIMIT 1";

                $res_inf = $conexion->conexion->query($sql_inf);

                if ($res_inf && $row_inf = $res_inf->fetch_assoc()) {
                    $estado = intval($row_inf['estado_envio']);
                    $id_plan = intval($row_inf['id_plan_tutoria']);

                    if (in_array($estado, [1, 2])) $en_proceso++;  // En proceso = guardado o enviado
                    if ($estado === 2) {
                        $enviado++;
                        $sql_conf = "SELECT 1 FROM tutoria_revision_director_informe 
                                    WHERE id_plan_tutoria = $id_plan 
                                    AND estado_revision = 'CONFORME' 
                                    AND mes_informe = '$mes_filtro'";
                        $res_conf = $conexion->conexion->query($sql_conf);
                        if ($res_conf && $res_conf->num_rows > 0) {
                            $conforme++;
                        }
                    }
                }
            }
        }

        if ($en_proceso > 0 || $enviado > 0 || $conforme > 0) {
            $hay_resultados = true;
        }

        $p1 = porcentaje($en_proceso, $total);
        $p2 = porcentaje($enviado, $total);
        $p3 = porcentaje($conforme, $total);
    ?>
    <div class="bloque-carrera" style="background-color: <?= $bg_color ?>">
        <button class="btn-ver" onclick="verDetalle(<?= $id_car ?>)">VER</button>
        <div class="titulo-carrera"><?= htmlspecialchars($car['nom_car']) ?></div>
        <div class="barra-contenedor">
            <div class="barra-progreso">
                <div class="barra-fill naranja" style="width: <?= $p1 ?>%">EN PROCESO (<?= $p1 ?>%)</div>
            </div>
        </div>
        <div class="barra-contenedor">
            <div class="barra-progreso">
                <div class="barra-fill amarillo" style="width: <?= $p2 ?>%">ENVIADOS (<?= $p2 ?>%)</div>
            </div>
        </div>
        <div class="barra-contenedor">
            <div class="barra-progreso">
                <div class="barra-fill verde" style="width: <?= $p3 ?>%">CONFORMIDAD (<?= $p3 ?>%)</div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
    </div>

    <?php if (!$hay_resultados): ?>
        <div style="text-align: center; margin-top: 40px; font-size: 18px; color: #c0392b;">
            ⚠️ Aún no se cuenta con informes registrados 
            <?= $mes_filtro ? "en el mes de <strong>" . strtoupper($mes_filtro) . "</strong>" : "actualmente" ?>.
        </div>
    <?php endif; ?>
</div>
</body>
</html>
