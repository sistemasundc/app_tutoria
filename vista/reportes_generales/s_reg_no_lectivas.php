<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();
date_default_timezone_set('America/Lima');

/* ➜ Semestre y carrera desde la sesión */
$id_semestre   = (int)($_SESSION['S_SEMESTRE'] ?? 0);
$id_car_actual = (int)($_SESSION['S_SCHOOL']   ?? 0);

/* ➜ Parámetros de vista */
$mes_filtro   = $_GET['mes'] ?? 'septiembre';
$semanas_mes  = 4;

/* ➜ Mapa de escuelas (opcional, por título) */
$escuelas = [
  1 => 'ADMINISTRACIÓN',
  2 => 'CONTABILIDAD',
  3 => 'ADMINISTRACIÓN DE TURISMO Y HOTELERÍA',
  4 => 'INGENIERÍA DE SISTEMAS',
  5 => 'AGRONOMÍA'
];

/* ============================================================================
   1) DOCENTES ASIGNADOS ESTE SEMESTRE Y CARRERA (SIN filtrar por id_rol)
   Fuente: tutoria_docente_asignado
============================================================================ */
$docentes = [];
$sql = "
  SELECT DISTINCT
      d.id_doce,
      CONCAT(UPPER(d.abreviatura_doce), ' ', UPPER(d.apepa_doce), ' ', UPPER(d.apema_doce), ', ', UPPER(d.nom_doce)) AS docente,
      d.email_doce, d.condicion, d.celu_doce
  FROM tutoria_docente_asignado tda
  INNER JOIN docente d ON d.id_doce = tda.id_doce
  WHERE tda.id_car = ? AND tda.id_semestre = ?
  ORDER BY docente
";
$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("ii", $id_car_actual, $id_semestre);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $docentes[(int)$row['id_doce']] = $row;
}

/* Si no hay docentes, mostrar página vacía amigable */
if (empty($docentes)) {
  ?>
  <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>REPORTE DE TUTORÍA</title></head><body>
  <h3 style="font-family:Arial">No hay docentes asignados como Tutor de Aula para la carrera seleccionada y semestre actual.</h3>
  </body></html>
  <?php exit;
}

/* Armar lista para IN (...) segura */
$ids = implode(',', array_map('intval', array_keys($docentes)));

/* ============================================================================
   2) HORAS FIJAS DESDE CATELEC (numcate=11, semana=4) SOLO PARA ESOS DOCENTES
============================================================================ */
$horas_docentes = [];
$sqlHoras = "
  SELECT id_doce, TIMESTAMPDIFF(MINUTE, hrini, hrter) AS minutos
  FROM catelec
  WHERE id_semestre = ? AND numcate = 11 AND semana = 4 AND id_doce IN ($ids)
";
$stmtH = $conexion->conexion->prepare($sqlHoras);
$stmtH->bind_param("i", $id_semestre);
$stmtH->execute();
$resH = $stmtH->get_result();
while ($rH = $resH->fetch_assoc()) {
  $minutos   = (int)$rH['minutos'];
  $horas_sem = round(($minutos / 45), 1);
  $horas_docentes[(int)$rH['id_doce']] = $horas_sem;
}

/* ============================================================================
   3) HORAS EDITADAS MANUALMENTE (desde tutoria_control_horas_aula)
============================================================================ */
$horas_editadas = [];
$mensuales_editadas = [];

$sqlHE = "SELECT id_doce, horas_semanales, horas_mensuales
          FROM tutoria_control_horas_aula
          WHERE id_semestre = ? AND id_car = ? AND id_doce IN ($ids)";
$stmtHE = $conexion->conexion->prepare($sqlHE);
$stmtHE->bind_param("ii", $id_semestre, $id_car_actual);
$stmtHE->execute();
$resHE = $stmtHE->get_result();

while ($r = $resHE->fetch_assoc()) {
  $idD = (int)$r['id_doce'];
  $horas_editadas[$idD]     = (float)$r['horas_semanales'];
  $mensuales_editadas[$idD] = (float)$r['horas_mensuales'];
}
/* ============================================================================
   4) CUMPLIMIENTO DE INFORME MENSUAL (SOLO TUTOR DE AULA)
   Fuente: tutoria_informe_mensual (estado_envio=2)
============================================================================ */
$enviados_docentes = [];
$sqlEnviados = "
  SELECT id_docente, COUNT(*) AS enviados
  FROM tutoria_informe_mensual
  WHERE id_car = ? AND id_semestre = ? AND mes_informe = ? AND estado_envio = 2
    AND id_docente IN ($ids)
  GROUP BY id_docente
";
$stmtEnviados = $conexion->conexion->prepare($sqlEnviados);
$stmtEnviados->bind_param("iis", $id_car_actual, $id_semestre, $mes_filtro);
$stmtEnviados->execute();
$resEnviados = $stmtEnviados->get_result();
while ($r = $resEnviados->fetch_assoc()) {
  $enviados_docentes[(int)$r['id_docente']] = (int)$r['enviados'];
}

/* ============================================================================
   5) HORAS ACUMULADAS Y Nº SESIONES (SOLO AULA: id_rol=6)
============================================================================ */
$mes_num_map = [
  'enero'=>1, 'febrero'=>2, 'marzo'=>3, 'abril'=>4,
  'mayo'=>5, 'junio'=>6, 'julio'=>7, 'agosto'=>8,
  'septiembre'=>9, 'octubre'=>10, 'noviembre'=>11, 'diciembre'=>12
];
$mes_num = (int)($mes_num_map[strtolower($mes_filtro)] ?? 0);

$horas_acumuladas = [];
$sqlHorasAcum = "
  SELECT id_doce, horainicio, horafin
  FROM tutoria_sesiones_tutoria_f78
  WHERE id_doce IN ($ids)
    AND id_semestre = ?
    AND id_rol = 6
    AND MONTH(fecha) = ?
    AND TRIM(LOWER(color)) = '#00a65a'
";
$stmtAcum = $conexion->conexion->prepare($sqlHorasAcum);
$stmtAcum->bind_param("ii", $id_semestre, $mes_num);
$stmtAcum->execute();
$resAcum = $stmtAcum->get_result();
while ($r = $resAcum->fetch_assoc()) {
  $inicio    = strtotime($r['horainicio']);
  $fin       = strtotime($r['horafin']);
  $minutos   = max(0, ($fin - $inicio) / 60);
  $horas     = round($minutos / 45, 2);
  $idD       = (int)$r['id_doce'];
  $horas_acumuladas[$idD] = ($horas_acumuladas[$idD] ?? 0) + $horas;
}

$sesiones_docentes = [];
$sqlSesiones = "
  SELECT id_doce, COUNT(*) AS sesiones
  FROM tutoria_sesiones_tutoria_f78
  WHERE id_doce IN ($ids)
    AND id_semestre = ?
    AND id_rol = 6
    AND MONTH(fecha) = ?
    AND TRIM(LOWER(color)) = '#00a65a'
  GROUP BY id_doce
";
$stmtSesiones = $conexion->conexion->prepare($sqlSesiones);
$stmtSesiones->bind_param("ii", $id_semestre, $mes_num);
$stmtSesiones->execute();
$resSesiones = $stmtSesiones->get_result();
while ($r = $resSesiones->fetch_assoc()) {
  $sesiones_docentes[(int)$r['id_doce']] = (int)$r['sesiones'];
}
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
.si { background-color: rgb(7, 240, 104); }
.no { background-color: rgb(244, 48, 30); }
td.SI { background-color: rgb(143, 243, 184); }
td.NO { background-color: rgb(239, 123, 113); }
button { background:none; border:none; cursor:pointer; }
</style>
</head>
<body>
<div class="contenedor">
  <h2>CONTROL DE HORAS NO LECTIVAS DE TUTORÍA — <?=strtoupper($mes_filtro)?> — <?=$escuelas[$id_car_actual] ?? 'CARRERA'?></h2>

    <form method="GET"
            action="/vista/index.php"
            style="display:flex; align-items:center; gap:10px; justify-content:center; flex-wrap:wrap;">
        <input type="hidden" name="pagina" value="reportes_generales/s_reg_no_lectivas.php">
        Mes:
        <select name="mes" onchange="this.form.submit()">
            <?php foreach (['septiembre','octubre','noviembre','diciembre'] as $m): ?>
            <option value="<?=$m?>" <?=$mes_filtro===$m?'selected':''?>><?=ucfirst($m)?></option>
            <?php endforeach; ?>
        </select>
    </form>

  <table>
    <tr>
      <th>ID</th><th>Docente</th><th>Correo</th><th>Condición</th><th>Celular</th>
      <th>H.Semanales</th><th>H.Mensuales</th><th>NºSesiones</th><th>H. Acumuladas</th><th>Informe</th>
    </tr>
    <?php foreach ($docentes as $id_doce => $row):
        // horas: primero lo guardado en control_horas_aula, si no existe => catelec
        $horas = $horas_editadas[$id_doce] ?? ($horas_docentes[$id_doce] ?? 0);

        // mensuales: si hay guardado en control_horas_aula úsalo; si no, calcula x4
        $mensuales = isset($mensuales_editadas[$id_doce])
                        ? $mensuales_editadas[$id_doce]
                        : round($horas * $semanas_mes, 1);

        $horas_display = ($horas == 0) ? "<span class='si'>NO TIENE</span>" : $horas;

        $enviados = $enviados_docentes[$id_doce] ?? 0;
        $cumple   = ($enviados > 0) ? 'SI' : 'NO';
        $clase    = ($cumple === 'SI') ? 'si' : 'no';

        $acumuladas = $horas_acumuladas[$id_doce] ?? 0;
        $color_horas = ($acumuladas >= $mensuales && $mensuales > 0) ? 'SI' : 'NO';
    ?>
    <tr>
      <td><?=$id_doce?></td>
      <td><?=$row['docente']?></td>
      <td><?=$row['email_doce']?></td>
      <td><?=$row['condicion']?></td>
      <td><?=$row['celu_doce']?></td>
      <td><span id="h_<?=$id_doce?>"><?=$horas_display?></span> <button onclick="editar(<?=$id_doce?>)">✏️</button></td>
      <td><?=$mensuales?></td>
      <td><?= $sesiones_docentes[$id_doce] ?? '---' ?></td>
      <td class="<?=$color_horas?>"><?= $acumuladas > 0 ? $acumuladas : '---' ?></td>
      <td class="<?=$clase?>"><?=$cumple?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<script>
function editar(id) {
  const span  = document.getElementById('h_'+id);
  const valor = span.textContent.trim();
  span.innerHTML = `<input type="number" id="input_${id}" value="${valor.replace('NO TIENE','0')}" step="0.1" style="width:60px;">
                    <button onclick="guardar(${id})">💾</button>`;
}
function guardar(id) {
  const nuevo = document.getElementById('input_'+id).value;
  fetch('reportes_generales/s_guardar_no_lectivas.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `id_doce=${id}&horas=${encodeURIComponent(nuevo)}&mes=<?=urlencode($mes_filtro)?>`
  }).then(r => r.text())
    .then(() => {
    window.location.href = '/vista/index.php?pagina=reportes_generales/s_reg_no_lectivas.php&mes=<?=urlencode($mes_filtro)?>';
    });
}
</script>
</body>
</html>
