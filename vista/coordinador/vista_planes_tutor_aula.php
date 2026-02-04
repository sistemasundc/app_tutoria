<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Lima');

/* ====== CONTROL DE ACCESO ====== */
if (
    !isset($_SESSION['S_IDUSUARIO']) ||
    !in_array($_SESSION['S_ROL'], ['DIRECTOR DE DEPARTAMENTO ACADEMICO','COMITÉ - SUPERVISIÓN'])
) {
    die('Acceso no autorizado');
}

require_once(__DIR__ . '/../../modelo/modelo_conexion.php');

$conexion = new conexion();
$conexion->conectar();

/* ====== DATOS DE SESIÓN ====== */
$id_car_sesion    = isset($_SESSION['S_SCHOOL'])   ? (int)$_SESSION['S_SCHOOL']   : 0;
$semestre_sesion  = isset($_SESSION['S_SEMESTRE']) ? (int)$_SESSION['S_SEMESTRE'] : 0;
$nombre_escuela= $_SESSION['S_SCHOOLNAME'];

if ($id_car_sesion <= 0 || $semestre_sesion <= 0) {
    echo "<div style='color:red; text-align:center; padding:20px; font-weight:bold;'>
            ❌ Error: La sesión no contiene ID de carrera o semestre.
          </div>";
    exit;
}

/* ====== SEMESTRE SELECCIONADO (GET o sesión) ====== */
$semestre_sel = (isset($_GET['semestre']) && $_GET['semestre'] !== '')
    ? (int)$_GET['semestre']
    : $semestre_sesion;

/*
  ✅ CLAVE:
  - El duplicado viene por múltiples filas en tutoria_revision_director.
  - Se soluciona trayendo SOLO la ÚLTIMA revisión.
  - Compatible con historial (semestre 32) donde id_cargalectiva/id_docente podían ser NULL.
*/
$sql = "
SELECT
    tpc.id_plan_tutoria,
    tpc.id_cargalectiva,
    tpc.id_docente,
    tpc.id_car,
    tpc.id_semestre,
    tpc.fecha_envio,
    cl.ciclo,
    d.abreviatura_doce,
    d.apepa_doce,
    d.apema_doce,
    d.nom_doce,

    /* prioriza revisión específica; si no hay, usa la global */
    COALESCE(rd_especifica.estado_revision, rd_global.estado_revision) AS estado_revision,
    COALESCE(rd_especifica.fecha_revision,  rd_global.fecha_revision)  AS fecha_revision

FROM tutoria_plan_compartido tpc
JOIN carga_lectiva cl
  ON cl.id_cargalectiva = tpc.id_cargalectiva
JOIN docente d
  ON d.id_doce = tpc.id_docente
JOIN tutoria_plan2 tp
  ON tp.id_plan_tutoria = tpc.id_plan_tutoria

/* 1) ÚLTIMA REVISIÓN ESPECÍFICA (con id_cargalectiva y id_docente) */
LEFT JOIN (
    SELECT r1.*
    FROM tutoria_revision_director r1
    INNER JOIN (
        SELECT
            id_plan_tutoria, id_semestre, id_car, id_cargalectiva, id_docente,
            MAX(fecha_revision) AS max_fecha
        FROM tutoria_revision_director
        WHERE (id_cargalectiva IS NOT NULL OR id_docente IS NOT NULL)
        GROUP BY id_plan_tutoria, id_semestre, id_car, id_cargalectiva, id_docente
    ) mx
      ON mx.id_plan_tutoria = r1.id_plan_tutoria
     AND mx.id_semestre     = r1.id_semestre
     AND mx.id_car          = r1.id_car
     AND (
            (mx.id_cargalectiva <=> r1.id_cargalectiva)
        AND (mx.id_docente     <=> r1.id_docente)
         )
     AND mx.max_fecha       = r1.fecha_revision
) rd_especifica
  ON rd_especifica.id_plan_tutoria = tpc.id_plan_tutoria
 AND rd_especifica.id_semestre     = tpc.id_semestre
 AND rd_especifica.id_car          = tpc.id_car
 AND rd_especifica.id_cargalectiva = tpc.id_cargalectiva
 AND rd_especifica.id_docente      = tpc.id_docente

/* 2) ÚLTIMA REVISIÓN GLOBAL (histórica: id_cargalectiva/id_docente NULL) */
LEFT JOIN (
    SELECT r2.*
    FROM tutoria_revision_director r2
    INNER JOIN (
        SELECT
            id_plan_tutoria, id_semestre, id_car,
            MAX(fecha_revision) AS max_fecha
        FROM tutoria_revision_director
        WHERE id_cargalectiva IS NULL AND id_docente IS NULL
        GROUP BY id_plan_tutoria, id_semestre, id_car
    ) mx2
      ON mx2.id_plan_tutoria = r2.id_plan_tutoria
     AND mx2.id_semestre     = r2.id_semestre
     AND mx2.id_car          = r2.id_car
     AND mx2.max_fecha       = r2.fecha_revision
) rd_global
  ON rd_global.id_plan_tutoria = tpc.id_plan_tutoria
 AND rd_global.id_semestre     = tpc.id_semestre
 AND rd_global.id_car          = tpc.id_car

WHERE cl.id_car = ?
  AND cl.id_semestre = ?
  AND tpc.estado_envio = 2

ORDER BY cl.ciclo, d.apepa_doce, d.apema_doce, d.nom_doce
";

$stmt = $conexion->conexion->prepare($sql);
if(!$stmt){
    die("Error prepare: ".$conexion->conexion->error);
}
$stmt->bind_param("ii", $id_car_sesion, $semestre_sel);
$stmt->execute();
$resultado = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Planes de Tutoría</title>
<link rel="stylesheet" href="../../css/bootstrap.min.css">
<style>
  body{background:#f8f9fc}
  .card-custom{
    background:#fff;
    padding:20px;
    border-radius:12px;
    box-shadow:0 4px 16px rgba(0,0,0,.08);
    max-width:1100px;
    margin:40px auto
  }
  h3 {
    text-align: center;
    background-color: #154360;
    color: white;
    padding: 15px;
    border-radius: 5px;
    font-size: 20px;
    margin-bottom: 20px;
  }
  table{width:100%}
  th{background:#154360;color:#fff;text-align:center}
  th,td{vertical-align:middle}
  .badge-pendiente{color:#b45f06;font-weight:700}
  .filtros-bar{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    flex-wrap:wrap;
    margin-bottom:10px;
  }
  .filtro-semestre{
    display:flex;
    gap:10px;
    align-items:center;
    margin-bottom:10px;
  }
</style>
</head>
<body>
<div class="card-custom">

  <div class="filtros-bar">
    <h3 style="margin:0;"><strong>PLANES DE TUTORÍA - TUTORES DE AULA</strong></h3>

    <div style="text-align:right;">
      <div class="filtro-semestre">
        <label for="semestre" style="font-weight:bold;">Semestre:</label>
        <select id="semestre" name="semestre" class="form-control form-control-sm" style="max-width:180px;"
                onchange="cambiarSemestre()">
          <option value="32" <?= ($semestre_sel == 32 ? 'selected' : '') ?>>2025-I</option>
          <option value="33" <?= ($semestre_sel == 33 ? 'selected' : '') ?>>2025-II</option>
        </select>
      </div>

      <form onsubmit="return abrirVentanaPopup(this);" method="GET"
            action="direccion_escuela/reporte_envios_planes.php"
            style="margin:0;">
        <button type="submit" class="btn btn-success btn-sm">
          📋 VER REPORTE
        </button>
      </form>
    </div>
  </div>

  <table class="table table-bordered table-hover">
    <thead>
      <tr>
        <th style="width:10%">Ciclo</th>
        <th style="width:38%">Tutor</th>
        <th style="width:18%">Fecha de Envío</th>
        <th style="width:14%">Acción</th>
        <th style="width:20%">Conformidad</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $resultado->fetch_assoc()): 
        $docente = trim(
          ($row['abreviatura_doce'] ? $row['abreviatura_doce'].' ' : '').
          $row['apepa_doce'].' '.$row['apema_doce'].', '.$row['nom_doce']
        );
        $pendiente = empty($row['estado_revision']) || strtoupper($row['estado_revision']) !== 'CONFORME';
      ?>
      <tr>
        <td class="text-center"><?= htmlspecialchars($row['ciclo']) ?></td>
        <td class="docente"><?= htmlspecialchars($docente) ?></td>
        <td class="text-center">
          <?= $row['fecha_envio'] ? htmlspecialchars($row['fecha_envio']) : '—' ?>
        </td>
        <td class="text-center">
          <form onsubmit="return abrirVentanaPopup(this);" method="GET"
                action="https://tutoria.undc.edu.pe/vista/tutor_aula/vista_prev_plan_tutoria.php">
            <input type="hidden" name="id_cargalectiva" value="<?= (int)$row['id_cargalectiva'] ?>">
            <input type="hidden" name="id_plan"         value="<?= (int)$row['id_plan_tutoria'] ?>">
            <input type="hidden" name="id_semestre"     value="<?= (int)$semestre_sel ?>">
            <button type="submit" class="btn btn-primary btn-sm"><strong>VER PLAN</strong></button>
          </form>
        </td>
        <td class="text-center">
          <?php if ($pendiente): ?>
            <span class="badge-pendiente">Conformidad Pendiente</span>
          <?php else: ?>
            <?= htmlspecialchars($row['fecha_revision']) ?>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<script>
function abrirVentanaPopup(form){
  const w=900,h=700,l=(screen.width-w)/2,t=(screen.height-h)/2;
  const url=new URL(form.action);
  url.search=new URLSearchParams(new FormData(form)).toString();
  window.open(
    url.toString(),
    'VistaPrevTutoria',
    `width=${w},height=${h},top=${t},left=${l},resizable=yes,scrollbars=yes,toolbar=no,location=no,status=no,menubar=no`
  );
  return false;
}

function cambiarSemestre() {
  const semestre = document.getElementById('semestre').value;
  window.location.href =
    "/vista/index.php?pagina=coordinador/vista_planes_tutor_aula.php"
    + "&semestre=" + encodeURIComponent(semestre);
}
</script>
</body>
</html>
