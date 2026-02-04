<?php 
session_start();

if (!isset($_SESSION['S_IDUSUARIO']) || !isset($_SESSION['S_USER']) || !isset($_SESSION['S_ROL'])) {
    header('Location: ../../Login/index.php');
    exit();
}

$id_semestre = $_SESSION['S_SEMESTRE'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_estu'])) {
    $id_estu = intval($_POST['id_estu']);
    $id_cargalectiva = isset($_POST['id_cargalectiva']) ? intval($_POST['id_cargalectiva']) : 0;

    require_once('../../modelo/modelo_conexion.php');
    $bd = new conexion();
    $bd->conectar();

    // Datos del estudiante
    $query2 = "SELECT e.cod_estu, e.apepa_estu, e.apema_estu, e.nom_estu,
                      d.id_car, c.nom_car, d.id_plan, p.nom_plan
               FROM estudiante e 
               JOIN detestudiante d ON e.id_estu = d.id_estu
               JOIN carrera c ON d.id_car = c.id_car
               JOIN plan_estudio p ON d.id_plan = p.id_plan
               WHERE e.id_estu = '$id_estu' AND d.activo = 'SI'";

    $consulta2 = $bd->conexion->query($query2);
    $row2 = $consulta2->fetch_assoc();

    $cod_estu = $row2["cod_estu"];
    $apepa_estu = $row2["apepa_estu"];
    $apema_estu = $row2["apema_estu"];
    $nom_estu  = $row2["nom_estu"];
    $nom_car   = $row2["nom_car"];
    $id_plan   = $row2["id_plan"];
    $nom_plan  = $row2["nom_plan"];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Asistencia</title>
<link rel="stylesheet" href="../../css/css1.css" type="text/css">
<style>
    .table-style {
        width: 700px;
        border-collapse: collapse;
    }
    .table-style td,
    .table-style th {
        padding: 2px 2px;
        border: 1px solid #000;
    }
    .table-header {
        background-color: #f2f2f2;
        font-weight: bold;
    }
    .arribaabajo {
        padding: 0px 1px;
    }
</style>
</head>
<body>
<br>

<table class="table-style">
  <tr class="table-header">
    <td colspan="22" align="center" class="arribaabajo"><strong>ASISTENCIA</strong></td>
  </tr>
  <tr class="table-header">
    <td colspan="6" align="center" class="arribaabajo"><strong>ESTUDIANTE:</strong></td>
    <td colspan="16" align='left' class="arribaabajo"><?php echo "$apepa_estu $apema_estu, $nom_estu"; ?></td>
  </tr>
  <tr class="table-header">
    <td colspan="22" align="center" class="arribaabajo">&nbsp;</td>
  </tr>
  <tr class="table-header">
    <td width="30" align="center"><strong>CIC</strong></td>
    <td width="30" align="center"><strong>TUR</strong></td>
    <td width="30" align="center"><strong>SEC</strong></td>
    <td width="30" align="center"><strong>COD</strong></td>
    <td width="150" align="left"><strong>&nbsp;ASIGNATURA</strong></td>
    <?php for ($i = 1; $i <= 16; $i++) echo "<td align='left'><strong>{$i}s&nbsp;</strong></td>"; ?>
    <td align='left'><strong>% FAL&nbsp;</strong></td>
  </tr>

<?php
// Obtener info de la asignatura del estudiante en esta carga
$query4 = "SELECT 
              ae.id_aestu,
              a.ciclo_asi,
              ae.turno_a,
              ae.seccion,
              a.cod_asi,
              a.nom_asi
           FROM asignacion_estudiante ae
           INNER JOIN asignatura a ON ae.id_asi = a.id_asi
           WHERE ae.id_estu = $id_estu 
             AND ae.id_cargalectiva = $id_cargalectiva
             AND ae.id_semestre = $id_semestre 
             AND ae.anulado = 0 
             AND ae.borrado = 0 
             AND ae.convalidado = 'NO'";

$consulta4 = $bd->conexion->query($query4);

while ($row4 = $consulta4->fetch_assoc()) {
    $id_aestu = $row4['id_aestu'];
    $asistencia_por_semana = array_fill(1, 16, '');
    $porcentaje_total = 0;

    $queryAsist = "SELECT semana, condicion, porcentaje 
                   FROM asistencias 
                   WHERE id_aestu = $id_aestu AND id_semestre = $id_semestre AND anulado = 0";
    $resAsist = $bd->conexion->query($queryAsist);

    while ($asis = $resAsist->fetch_assoc()) {
        $sem = intval($asis['semana']);
        $cond = strtoupper($asis['condicion']);
        if ($sem >= 1 && $sem <= 16) {
            $asistencia_por_semana[$sem] = $cond;
            if ($cond == 'F') {
                $porcentaje_total += floatval($asis['porcentaje']);
            }
        }
    }

    echo "<tr>";
    echo "<td align='center'>{$row4['ciclo_asi']}</td>";
    echo "<td align='center'>" . substr($row4['turno_a'], 0, 1) . "</td>";
    echo "<td align='center'>{$row4['seccion']}</td>";
    echo "<td align='left'>" . substr($nom_plan, 2, 2) . "&nbsp;{$row4['cod_asi']}</td>";
    echo "<td align='left'>{$row4['nom_asi']}</td>";

    for ($i = 1; $i <= 16; $i++) {
        $valor = $asistencia_por_semana[$i];
        echo "<td align='center'>$valor</td>";
    }

    echo "<td align='center'>" . number_format($porcentaje_total, 2) . " %</td>";
    echo "</tr>";
}
?>
</table>
<br>
</body>
</html>

<?php
$bd->cerrar();
?>
