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
    <title>Reporte Planes de Tutoría por Carrera</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f6f8fc; margin: 0; }
        .container { max-width: 1500px; margin: auto; padding: 10px; }
        h3 { text-align: center; margin-bottom: 30px; background-color: #2c3e50; color: white; padding: 15px; border-radius: 5px; }
        .contenedor-grilla { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
        .bloque-carrera { padding: 15px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); color: #2c3e50; position: relative; }
        .titulo-carrera { font-weight: bold; font-size: 16px; margin-bottom: 15px;  }
        .barra-contenedor { margin: 16px 0; }
        .barra-progreso { width: 100%; height: 24px; background-color: #e0e0e0; border-radius: 12px; overflow: hidden; box-shadow: inset 0 1px 3px rgba(0,0,0,0.2); }
        .barra-fill { height: 100%; text-align: center; line-height: 24px; font-size: 13px; font-weight: bold; color:rgb(37, 38, 46); white-space: nowrap; }
        .naranja { background-color:rgb(245, 158, 83); }
        .amarillo { background-color: #f1c40f; }
        .verde { background-color:rgb(144, 214, 63); }
        .btn-ver { position: absolute; top: 10px; right: 10px; background: #1a5276; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 12px; }
    </style>
    <script>
    function verDetalle(id_car) {
        window.open('reportes_generales/lista_envio_planes.php?id_car=' + id_car, '_blank', 'width=1000,height=700');
    }
    </script>
</head>
<body>
<div class="container">
    <h3>PROCESO DE REGISTRO Y ENVÍO DE PLANES DE TUTORÍA</h3>
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
            $proceso_guardado = false;
            $ya_enviado = false;
            $ya_conforme = false;

            $sql_cargas = "SELECT id_cargalectiva FROM carga_lectiva WHERE id_doce = $id_doce AND id_semestre = $id_semestre";
            $res_cargas = $conexion->conexion->query($sql_cargas);

            while ($c = $res_cargas->fetch_assoc()) {
                $id_carga = $c['id_cargalectiva'];

                $sql_plan = "SELECT id_plan_tutoria, estado_envio FROM tutoria_plan_compartido WHERE id_cargalectiva = $id_carga ORDER BY id_comp DESC LIMIT 1";
                $res_plan = $conexion->conexion->query($sql_plan);

                if ($res_plan && $row_plan = $res_plan->fetch_assoc()) {
                    $estado = intval($row_plan['estado_envio']);
                    $id_plan = $row_plan['id_plan_tutoria'];

                    if ($estado == 1) {
                        $proceso_guardado = true;
                    }
                    if ($estado == 2) {
                        $proceso_guardado = true;
                        $ya_enviado = true;

                        $sql_conf = "SELECT 1 FROM tutoria_revision_director WHERE id_plan_tutoria = $id_plan AND estado_revision = 'CONFORME'";
                        $res_conf = $conexion->conexion->query($sql_conf);
                        if ($res_conf && $res_conf->num_rows > 0) {
                            $ya_conforme = true;
                        }
                    }
                }
            }

            if ($proceso_guardado) $en_proceso++;
            if ($ya_enviado) $enviado++;
            if ($ya_conforme) $conforme++;
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
</div>
</body>
</html>
