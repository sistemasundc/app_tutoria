<?php
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');
date_default_timezone_set('America/Lima');

if (
    !isset($_SESSION['S_IDUSUARIO']) ||
    ($_SESSION['S_ROL'] ?? '') !== 'DEPARTAMENTO ESTUDIOS GENERALES'
) {
    die('Acceso denegado');
}
$conexion = new conexion();
$conexion->conectar();

$id_semestre = $_SESSION['S_SEMESTRE'];
$mes_filtro = $_GET['mes'] ?? 'septiembre';

$sql_carreras = "SELECT id_car, nom_car FROM carrera ORDER BY nom_car";
$res_car = $conexion->conexion->query($sql_carreras);

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
    <title>Informe Cursos por Carrera</title>
    <style>
        body { font-family: Arial; background: #f6f8fc; margin: 0; }
        .container { max-width: 1400px; margin: auto; padding: 10px; }
        h3 { text-align: center; background-color: #2c3e50; color: white; padding: 15px; border-radius: 5px; }
        .filtro { text-align: center; margin-bottom: 20px; }
        select { padding: 6px 10px; font-size: 14px; }
        .contenedor-grilla { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
        .bloque-carrera { padding: 15px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); color: #2c3e50; position: relative; }
        .titulo-carrera { font-weight: bold; font-size: 16px; margin-bottom: 15px; }
        .barra-contenedor { margin: 12px 0; }
        .barra-progreso { width: 100%; height: 24px; background-color: #e0e0e0; border-radius: 12px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.2); }
        .barra-fill { height: 100%; text-align: center; line-height: 24px; font-size: 13px; font-weight: bold; color:rgb(37, 38, 46); white-space: nowrap; }
        .naranja { background-color: #e67e22; }
        .amarillo { background-color: #f1c40f; }
        .verde { background-color: #27ae60; }
        .btn-ver { position: absolute; top: 10px; right: 10px; background: #1a5276; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 12px; }
    </style>
</head>
<body>
<div class="container">
    <h3>VISTA DEL PROCESO DE INFORMES MENSUALES - ESTUDIOS GENERALES</h3>

    <div class="filtro">
        <label for="mes_filtro">Seleccionar mes:</label>
        <select id="mes_filtro" onchange="filtrarMes()">
            <option value="">-- Mes --</option>
            <!-- foreach (['abril','mayo','junio','julio','agosto'] as $m): ?> -->
            <?php foreach (['septiembre', 'octubre', 'noviembre', 'diciembre'] as $m): ?>
                <option value="<?= $m ?>" <?= $mes_filtro == $m ? 'selected' : '' ?>><?= ucfirst($m) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="contenedor-grilla">
    <?php while ($car = $res_car->fetch_assoc()):
        $id_car = $car['id_car'];
        $bg_color = colorFondoCarrera($id_car);

        // Obtener asignaturas del semestre actual por carrera
        $stmt = $conexion->conexion->prepare("
            SELECT cl.id_cargalectiva
            FROM carga_lectiva cl
            JOIN asignatura a ON cl.id_asi = a.id_asi
            WHERE cl.id_car = ? AND cl.id_semestre = ? AND cl.tipo = 'M' AND a.tipo_c = 'EG'
        ");
        $stmt->bind_param("ii", $id_car, $id_semestre);
        $stmt->execute();
        $resAsig = $stmt->get_result();
        $total_asig = $resAsig->num_rows;

        $en_proceso = $enviados = $conforme = 0;

        while ($fila = $resAsig->fetch_assoc()) {
            $id_cargalectiva = $fila['id_cargalectiva'];

            $sqlInf = "SELECT estado_envio FROM tutoria_informe_mensual_curso 
                       WHERE id_cargalectiva = $id_cargalectiva AND id_car = $id_car";
            if (!empty($mes_filtro)) {
                $sqlInf .= " AND LOWER(mes_informe) = '" . strtolower($mes_filtro) . "'";
            }
            $sqlInf .= " ORDER BY id_informe DESC LIMIT 1";
            $resInf = $conexion->conexion->query($sqlInf);
            $estado = null;
            if ($resInf && $rowInf = $resInf->fetch_assoc()) {
                $estado = intval($rowInf['estado_envio']);
                if (in_array($estado, [1, 2])) $en_proceso++;
                if ($estado === 2) $enviados++;

                // Validar conformidad
                $sqlC = "SELECT 1 FROM tutoria_revision_director_informe_curso 
                         WHERE id_cargalectiva = $id_cargalectiva AND estado_revision = 'CONFORME'";
                if (!empty($mes_filtro)) {
                    $sqlC .= " AND LOWER(mes_informe) = '" . strtolower($mes_filtro) . "'";
                }
                $resC = $conexion->conexion->query($sqlC);
                if ($resC && $resC->num_rows > 0) $conforme++;
            }
        }

        $p1 = porcentaje($en_proceso, $total_asig);
        $p2 = porcentaje($enviados, $total_asig);
        $p3 = porcentaje($conforme, $total_asig);
    ?>
    <div class="bloque-carrera" style="background-color: <?= $bg_color ?>;">
        <button class="btn-ver" onclick="verDetalle(<?= $id_car ?>)">VER</button>
        <div class="titulo-carrera"><?= htmlspecialchars($car['nom_car']) ?></div>
        <div class="barra-contenedor">
            <div class="barra-progreso"><div class="barra-fill naranja" style="width: <?= $p1 ?>%">EN PROCESO (<?= $p1 ?>%)</div></div>
        </div>
        <div class="barra-contenedor">
            <div class="barra-progreso"><div class="barra-fill amarillo" style="width: <?= $p2 ?>%">ENVIADOS (<?= $p2 ?>%)</div></div>
        </div>
        <div class="barra-contenedor">
            <div class="barra-progreso"><div class="barra-fill verde" style="width: <?= $p3 ?>%">CONFORMIDAD (<?= $p3 ?>%)</div></div>
        </div>
    </div>
    <?php endwhile; ?>
    </div>
</div>

<script>
function filtrarMes() {
    const mes = document.getElementById("mes_filtro").value;
    window.location.href = "index.php?pagina=estudios_generales/eg_report_general_informe_mensual.php&mes=" + mes;
}
function verDetalle(id_car) {
    const mes = document.getElementById("mes_filtro").value;
    const url = `estudios_generales/eg_reporte_envios_informe.php?id_car=${id_car}&mes=${encodeURIComponent(mes)}`;
    window.open(url, '_blank', 'width=1000,height=700');
}
</script>
</body>
</html>
