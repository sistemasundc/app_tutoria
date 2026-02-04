<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();
date_default_timezone_set('America/Lima');

/* ================== Inputs & util ================== */
$id_semestre = (int)($_SESSION['S_SEMESTRE'] ?? 33);
$mes_filtro  = $_GET['mes'] ?? 'octubre';

/* semanas del mes (si quieres, ajusta por mes) */
$semanas_mes = 4;

$escuelas = [
  1 => 'ADMINISTRACIÓN',
  2 => 'CONTABILIDAD',
  3 => 'ADMINISTRACIÓN DE TURISMO Y HOTELERÍA',
  4 => 'INGENIERÍA DE SISTEMAS',
  5 => 'AGRONOMÍA'
];

$mes_num_map = [
  'enero'=>1,'febrero'=>2,'marzo'=>3,'abril'=>4,'mayo'=>5,'junio'=>6,
  'julio'=>7,'agosto'=>8,'septiembre'=>9,'octubre'=>10,'noviembre'=>11,'diciembre'=>12
];
$mes_num = (int)($mes_num_map[strtolower($mes_filtro)] ?? 0);

/* ================== DOCENTES ================== */
$docentes = [];
$sql = "
SELECT 
  d.id_doce,
  CONCAT(UPPER(d.abreviatura_doce), ' ', UPPER(d.apepa_doce), ' ', UPPER(d.apema_doce), ', ', UPPER(d.nom_doce)) AS docente,
  d.email_doce, d.condicion, d.celu_doce, cl.id_car
FROM docente d
JOIN carga_lectiva cl ON cl.id_doce = d.id_doce
JOIN asignatura a ON a.id_asi = cl.id_asi
WHERE cl.id_semestre = ?
GROUP BY d.id_doce
ORDER BY docente";
$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("i", $id_semestre);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $docentes[$row['id_doce']] = $row;
}
$stmt->close();

$ids_docentes = array_keys($docentes);

/* Si no hay docentes, renderiza vacío y termina */
if (empty($ids_docentes)) {
  echo "<h3>No hay docentes con carga en el semestre seleccionado.</h3>";
  exit;
}

/* ================== HORAS PIT (respaldo) ================== */
$horas_pit = []; // horas semanales equivalentes (min/45)
$sqlHorasPIT = "
SELECT id_doce, TIMESTAMPDIFF(MINUTE, hrini, hrter) AS minutos
FROM catelec
WHERE id_semestre = ? AND numcate = 11 AND semana = 4";
$stmtH = $conexion->conexion->prepare($sqlHorasPIT);
$stmtH->bind_param("i", $id_semestre);
$stmtH->execute();
$resH = $stmtH->get_result();
while ($rH = $resH->fetch_assoc()) {
  $horas_pit[(int)$rH['id_doce']] = round(((int)$rH['minutos']) / 45, 2);
}
$stmtH->close();

/* ================== NUEVO: HORAS DESDE tutoria_control_horas_aula ==================
   Tomamos el último registro por docente en el semestre (según updated_at o id).
   Guardamos tanto horas_semanales como horas_mensuales (si viene).
================================================================= */
$horas_control = []; // [id_doce] => ['sem'=>x, 'mes'=>y]
$in_ids = implode(',', array_map('intval', $ids_docentes));

$sqlHE = "
SELECT t.id_doce, t.horas_semanales, t.horas_mensuales
FROM tutoria_control_horas_aula t
JOIN (
  SELECT id_doce, MAX(updated_at) AS max_upd
  FROM tutoria_control_horas_aula
  WHERE id_semestre = ? AND id_doce IN ($in_ids)
  GROUP BY id_doce
) ult
  ON ult.id_doce = t.id_doce AND ult.max_upd = t.updated_at
WHERE t.id_semestre = ? AND t.id_doce IN ($in_ids)";
$stmtHE = $conexion->conexion->prepare($sqlHE);
$stmtHE->bind_param("ii", $id_semestre, $id_semestre);
$stmtHE->execute();
$resHE = $stmtHE->get_result();
while ($r = $resHE->fetch_assoc()) {
  $id = (int)$r['id_doce'];
  $horas_control[$id] = [
    'sem' => (float)$r['horas_semanales'],
    'mes' => (float)$r['horas_mensuales']
  ];
}
$stmtHE->close();

/* ================== CURSOS TIPO M ================== */
$cursos_M_docente = [];
$sqlCursosM = "
SELECT cl.id_doce, COUNT(*) AS cursos_M
FROM carga_lectiva cl
JOIN asignatura a ON a.id_asi = cl.id_asi
WHERE cl.id_semestre = ? AND cl.tipo = 'M'
GROUP BY cl.id_doce";
$stmtCursosM = $conexion->conexion->prepare($sqlCursosM);
$stmtCursosM->bind_param("i", $id_semestre);
$stmtCursosM->execute();
$resCursosM = $stmtCursosM->get_result();
while ($r = $resCursosM->fetch_assoc()) {
  $cursos_M_docente[(int)$r['id_doce']] = (int)$r['cursos_M'];
}
$stmtCursosM->close();

/* ================== INFORMES ENVIADOS (AULA: tutoria_informe_mensual) ================== */
/* Cuenta 1+ informes enviados (estado_envio=2) por docente en el mes y semestre actual */
$enviados_docentes = [];
$sqlEnviados = "
SELECT id_docente, COUNT(*) AS enviados
FROM tutoria_informe_mensual
WHERE id_semestre = ?
  AND LOWER(TRIM(mes_informe)) = ?
  AND estado_envio = 2
GROUP BY id_docente";
$stmtEnviados = $conexion->conexion->prepare($sqlEnviados);
$mes_filtro_lc = strtolower($mes_filtro);
$stmtEnviados->bind_param("is", $id_semestre, $mes_filtro_lc);
$stmtEnviados->execute();
$resEnviados = $stmtEnviados->get_result();
while ($r = $resEnviados->fetch_assoc()) {
  $enviados_docentes[(int)$r['id_docente']] = (int)$r['enviados'];
}
$stmtEnviados->close();

/* ================== HORAS ACUMULADAS (F78) ================== */
$horas_acumuladas = [];
$sqlHorasAcum = "
SELECT id_doce, fecha, horainicio, horafin
FROM tutoria_sesiones_tutoria_f78
WHERE id_doce IN ($in_ids)
  AND MONTH(fecha) = ?
  AND LOWER(TRIM(color)) = '#00a65a'";
$stmtAcum = $conexion->conexion->prepare($sqlHorasAcum);
$stmtAcum->bind_param("i", $mes_num);
$stmtAcum->execute();
$resAcum = $stmtAcum->get_result();
while ($r = $resAcum->fetch_assoc()) {
  $inicio = strtotime($r['horainicio']);
  $fin    = strtotime($r['horafin']);
  if ($inicio && $fin && $fin > $inicio) {
    $min = ($fin - $inicio) / 60;
    $hrs = round($min / 45, 2);
    $id  = (int)$r['id_doce'];
    $horas_acumuladas[$id] = ($horas_acumuladas[$id] ?? 0) + $hrs;
  }
}
$stmtAcum->close();

/* ================== SESIONES (F78) ================== */
$sesiones_docentes = [];
$sqlSesiones = "
SELECT id_doce, COUNT(*) AS sesiones
FROM tutoria_sesiones_tutoria_f78
WHERE id_doce IN ($in_ids)
  AND MONTH(fecha) = ?
  AND LOWER(TRIM(color)) = '#00a65a'
GROUP BY id_doce";
$stmtSesiones = $conexion->conexion->prepare($sqlSesiones);
$stmtSesiones->bind_param("i", $mes_num);
$stmtSesiones->execute();
$resSesiones = $stmtSesiones->get_result();
while ($r = $resSesiones->fetch_assoc()) {
  $sesiones_docentes[(int)$r['id_doce']] = (int)$r['sesiones'];
}
$stmtSesiones->close();

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>REPORTE DE TUTORÍA</title>
<style>
  body { font-family: Arial; background: #f8f9fa; }
  .contenedor { max-width: 1200px; margin: auto; padding: 20px; background: #fff; box-shadow: 0 0 10px #ccc; }
  h2 { text-align: center; background-color: #2c3e50; color: white; padding: 15px; border-radius: 5px; font-size: 20px; }
  form { margin-bottom: 20px; text-align: center; }
  table { width: 100%; border-collapse: collapse; font-size: 12px; }
  th, td { padding: 6px; border: 1px solid #ccc; text-align: center; }
  th { background: #34495e; color: white; }
  .SI { background-color: rgb(143, 243, 184); }
  .NO { background-color: rgb(239, 123, 113); }
</style>
</head>
<body>
<div class="contenedor">
  <h2>CONTROL DE HORAS NO LECTIVAS DE TUTORÍA — <?=strtoupper($mes_filtro)?></h2>

  <form method="GET">
    Mes:
    <select name="mes" onchange="this.form.submit()">
      <?php foreach (['septiembre','octubre','noviembre','diciembre'] as $m): ?>
        <option value="<?=$m?>" <?=$mes_filtro==$m?'selected':''?>><?=ucfirst($m)?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <table>
    <tr>
      <th>ID</th>
      <th>Docente</th>
      <th>Correo</th>
      <th>Condición</th>
      <th>Celular</th>
      <th>H. Semanales</th>
      <th>H. Mensuales</th>
      <th>Nº Sesiones</th>
      <th>H. Acumuladas</th>
      <th>Informe</th>
      <th>Carrera</th>
    </tr>

    <?php foreach ($docentes as $id_doce => $row):
      // 1) horas semanales/mensuales desde la tabla nueva
      $sem_control = $horas_control[$id_doce]['sem'] ?? null;
      $mes_control = $horas_control[$id_doce]['mes'] ?? null;

      // 2) respaldo: PIT (si no hay control)
      $sem_final = $sem_control !== null ? (float)$sem_control : (float)($horas_pit[$id_doce] ?? 0.0);

      // si no hay horas, no se muestra
      if ($sem_final <= 0) continue;

      // mensual: si la tabla trae horas_mensuales, úsala, si no, calcula por semanas del mes
      $mensuales = $mes_control !== null && $mes_control > 0 ? (float)$mes_control : round($sem_final * $semanas_mes, 2);

        $enviados   = $enviados_docentes[$id_doce] ?? 0;
        $acumuladas = $horas_acumuladas[$id_doce] ?? 0;

        // Informe: SI si existe al menos 1 informe enviado para el mes/semestre
        $estado_informe = ($enviados > 0) ? 'SI' : 'NO';

        // Horas: SI si llegó a la meta mensual
        $estado_horas = ($acumuladas >= $mensuales) ? 'SI' : 'NO';

        // si ambos son SI, ocultar
        if ($estado_informe === 'SI' && $estado_horas === 'SI') continue;
    ?>
    <tr>
        <td><?=$id_doce?></td>
        <td><?=$row['docente']?></td>
        <td><?=$row['email_doce']?></td>
        <td><?=$row['condicion']?></td>
        <td><?=$row['celu_doce']?></td>
        <td><?=number_format($sem_final, 2)?></td>
        <td><?=number_format($mensuales, 2)?></td>
        <td><?= $sesiones_docentes[$id_doce] ?? '---' ?></td>
        <td class="<?=$estado_horas?>"><?= $acumuladas ? number_format($acumuladas,2) : '---' ?></td>
        <td class="<?=$estado_informe?>"><?=$estado_informe?></td>
        <td><?=$escuelas[$row['id_car']] ?? '-'?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
</body>
</html>
