<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once(__DIR__ . "/../../modelo/modelo_conexion.php");
$cx = new conexion();
$cn = $cx->conectar();

if (!$cn) { die("No hay conexión a BD"); }

$id_semestre   = $_SESSION['S_SEMESTRE'] ?? null;
$id_usuario    = $_SESSION['S_IDUSUARIO'] ?? null;  // coordinador que registra
$id_rol        = 6; // TUTOR DE AULA (ajústalo si corresponde)

if (!$id_semestre || !$id_usuario) {
  die("Faltan datos de sesión (semestre/usuario).");
}

/*
  Lista de tutores registrados por este usuario (coordinador) en el semestre actual.
  Tabla base: tutoria_docente_asignado  (muestra en tus capturas: id_doce, id_semestre, id_rol, id_coordinador, id_car)
  Traemos nombres del docente y (opcional) la escuela.
*/

$sql = "
SELECT DISTINCT
    d.id_doce,
    CONCAT(d.abreviatura_doce, ' ', d.apepa_doce, ' ', d.apema_doce, ', ', d.nom_doce) AS nombre_docente,
    COALESCE(aulas.ciclos,'')    AS ciclos,
    COALESCE(aulas.secciones,'') AS secciones,
    COALESCE(aulas.turno,'') AS turno
FROM tutoria_docente_asignado da
JOIN docente d ON d.id_doce = da.id_doce
LEFT JOIN (
    SELECT 
        tat.id_docente,
        GROUP_CONCAT(DISTINCT cl.ciclo   ORDER BY cl.ciclo   SEPARATOR ' / ') AS ciclos,
        GROUP_CONCAT(DISTINCT cl.seccion ORDER BY cl.seccion SEPARATOR ' / ') AS secciones,
        GROUP_CONCAT(DISTINCT cl.turno ORDER BY cl.turno SEPARATOR ' / ') AS turno
    FROM tutoria_asignacion_tutoria tat
    JOIN carga_lectiva cl 
         ON cl.id_cargalectiva = tat.id_carga
        AND cl.id_semestre     = tat.id_semestre
        AND cl.id_doce         = tat.id_docente
    WHERE tat.id_semestre  = ?
      AND tat.id_coodinador = ?
    GROUP BY tat.id_docente
) aulas ON aulas.id_docente = da.id_doce
WHERE da.id_semestre    = ?
  AND da.id_coordinador = ?
  AND da.id_rol IN (2,6)
ORDER BY nombre_docente ASC
";

$stmt = $cn->prepare($sql);
if (!$stmt) {
    die("Error en prepare: " . $cn->error);
}

if (!$stmt->bind_param('iiii', $id_semestre, $id_usuario, $id_semestre, $id_usuario)) {
    die("Error en bind_param: " . $stmt->error);
}

if (!$stmt->execute()) {
    die("Error en execute: " . $stmt->error);
}

$res = $stmt->get_result();

$tutores = [];
while ($row = $res->fetch_assoc()) { $tutores[] = $row; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Lista de Tutores</title>
<style>
  body{font-family: Arial, Helvetica, sans-serif; margin:20px;}
  h2{margin:0 0 10px 0; font-size:20px;}
  .meta{font-size:13px; margin-bottom:15px; color:#555;}
  table{border-collapse: collapse; width:100%;}
  th, td{border:1px solid #1f4e79; padding:7px 8px; font-size:13px;}
  th{background:#cfe2f3; text-transform:uppercase; letter-spacing:.3px;}
  td:nth-child(1){text-align:center; width:48px;}
  .right{ text-align:right; }
  .btn-print{padding:6px 10px; border:1px solid #888; background:#f4f4f4; cursor:pointer; border-radius:4px;}
  @media print{ .acciones{ display:none; } }
  .boton-imprimir {
    position: fixed;
    top: 20px;
    right: 20px; /* ahora esquina superior derecha */
    z-index: 9999;
    background-color: #007bff;
    color: white;
    padding: 10px 12px;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    transition: background 0.3s;
  }

  .boton-imprimir:hover {
    background-color: #0056b3;
  }

  @media print {
    .boton-imprimir {
      display: none !important;
    }
  }
</style>
</head>
<body>

<div class="boton-imprimir" id="btn-imprimir" onclick="window.print()" title="Imprimir documento">
        🖨️
</div>

<h2>SISTECU - UNDC </h2>
<div class="meta">
  <b>Reporte:</b> Lista de Tutores de Aula<br>
  <!-- <b>Semestre:</b> ?=htmlspecialchars($id_semestre) ?><br>
  <b>Generado por usuario (ID):</b> ?=htmlspecialchars($id_usuario) ?> -->
</div>

<table>
  <thead>
    <tr>
      <th>N°</th>
      <th>TUTORES DE AULA</th>
      <th>CICLO</th>
      <th>TURNO</th>
      <th>SECCIÓN</th>
    </tr>
  </thead>
  <tbody>
    <?php if(empty($tutores)): ?>
      <tr><td colspan="5" style="text-align:center">No hay tutores registrados por usted en el semestre actual.</td></tr>
    <?php else: ?>
      <?php $i=1; foreach($tutores as $t): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars(mb_strtoupper($t['nombre_docente'],'UTF-8')) ?></td>
          <td style="text-align:center"><?= htmlspecialchars($t['ciclos']) ?></td>
          <td style="text-align:center"><?= htmlspecialchars($t['turno']) ?></td>
          <td style="text-align:center"><?= htmlspecialchars($t['secciones']) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

</body>
</html>