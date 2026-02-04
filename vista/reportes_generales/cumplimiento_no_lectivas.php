<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();
date_default_timezone_set('America/Lima');

/* Parámetros base */
$id_semestre   = $_SESSION['S_SEMESTRE'] ??'';
$mes_filtro    = $_GET['mes']     ?? 'noviembre';
$id_car_filtro = $_GET['carrera'] ?? 1;
$semanas_mes   = 4;

$escuelas = [
  1 => 'ADMINISTRACIÓN',
  2 => 'CONTABILIDAD',
  3 => 'ADMINISTRACIÓN DE TURISMO Y HOTELERÍA',
  4 => 'INGENIERÍA DE SISTEMAS',
  5 => 'AGRONOMÍA'
];

/* ----------------------------
   1) DOCENTES (TUTOR DE AULA)
   ---------------------------- */
$docentes = [];
$sql = "
SELECT DISTINCT
    d.id_doce,
    CONCAT(UPPER(d.abreviatura_doce), ' ', UPPER(d.apepa_doce), ' ', UPPER(d.apema_doce), ', ', UPPER(d.nom_doce)) AS docente,
    d.email_doce, d.condicion, d.celu_doce
FROM tutoria_docente_asignado tda
JOIN docente d ON d.id_doce = tda.id_doce
WHERE tda.id_car = ?
  AND tda.id_semestre = ?
ORDER BY docente";
$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("ii", $id_car_filtro, $id_semestre);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $docentes[$row['id_doce']] = $row;
}

/* ---------------------------------
   2) HORAS BASE (PIT como respaldo)
   --------------------------------- */
$sqlHoras = "
SELECT id_doce, TIMESTAMPDIFF(MINUTE, hrini, hrter) AS minutos
FROM catelec
WHERE id_semestre = ? AND numcate = 11 AND semana = 4";
$stmtH = $conexion->conexion->prepare($sqlHoras);
$stmtH->bind_param("i", $id_semestre);
$stmtH->execute();
$resH = $stmtH->get_result();

$horas_docentes = [];
while ($rH = $resH->fetch_assoc()) {
    $horas_docentes[$rH['id_doce']] = round(($rH['minutos'] / 45), 1);
}

/* ----------------------------------------------
   3) HORAS EDITADAS (tutoria_control_horas_aula)
   ---------------------------------------------- */
$sqlHE = "
SELECT id_doce, horas_semanales, horas_mensuales
FROM tutoria_control_horas_aula
WHERE id_semestre = ? AND id_car = ?";
$stmtHE = $conexion->conexion->prepare($sqlHE);
$stmtHE->bind_param("ii", $id_semestre, $id_car_filtro);
$stmtHE->execute();
$resHE = $stmtHE->get_result();

$horas_editadas = [];        // semanal
$horas_mensuales_edit = [];  // mensual
while ($r = $resHE->fetch_assoc()) {
    $horas_editadas[$r['id_doce']]       = (float)$r['horas_semanales'];
    $horas_mensuales_edit[$r['id_doce']] = (float)$r['horas_mensuales'];
}

/* ---------------------------------------
   4) INFORMES ENVIADOS SOLO AULA (estado 2)
   --------------------------------------- */
$sqlEnviados = "
SELECT tim.id_docente AS id_doce, COUNT(*) AS enviados
FROM tutoria_informe_mensual tim
WHERE tim.id_car = ?
  AND tim.id_semestre = ?
  AND tim.mes_informe = ?
  AND tim.estado_envio = 2
GROUP BY tim.id_docente";
$stmtEnviados = $conexion->conexion->prepare($sqlEnviados);
$stmtEnviados->bind_param("iis", $id_car_filtro, $id_semestre, $mes_filtro);
$stmtEnviados->execute();
$resEnviados = $stmtEnviados->get_result();

$enviados_docentes = [];
while ($r = $resEnviados->fetch_assoc()) {
    $enviados_docentes[$r['id_doce']] = (int)$r['enviados'];
}

/* ------------------------------------------
   5) HORAS ACUMULADAS Y Nº SESIONES DEL MES
   ------------------------------------------ */
$mes_num_map = [
  'enero'=>1, 'febrero'=>2, 'marzo'=>3, 'abril'=>4,
  'mayo'=>5, 'junio'=>6, 'julio'=>7, 'agosto'=>8,
  'septiembre'=>9, 'octubre'=>10, 'noviembre'=>11, 'diciembre'=>12
];
$mes_num = $mes_num_map[strtolower($mes_filtro)] ?? 0;

$horas_acumuladas = [];
$sesiones_docentes = [];

$ids = array_keys($docentes);
if (!empty($ids)) {
    $ids_list = implode(',', array_map('intval', $ids));

    // Horas acumuladas (solo sesiones validadas color #00a65a)
    $sqlHorasAcum = "
    SELECT id_doce, fecha, horainicio, horafin
    FROM tutoria_sesiones_tutoria_f78
    WHERE id_doce IN ($ids_list)
      AND id_semestre = ?
      AND MONTH(fecha) = ?
      AND TRIM(LOWER(color)) = '#00a65a'";
    $stmtAcum = $conexion->conexion->prepare($sqlHorasAcum);
    $stmtAcum->bind_param("ii", $id_semestre, $mes_num);
    $stmtAcum->execute();
    $resAcum = $stmtAcum->get_result();
    while ($r = $resAcum->fetch_assoc()) {
        $inicio  = strtotime($r['horainicio']);
        $fin     = strtotime($r['horafin']);
        $minutos = max(0, ($fin - $inicio) / 60);
        $horas   = round($minutos / 45, 2);
        $horas_acumuladas[$r['id_doce']] = ($horas_acumuladas[$r['id_doce']] ?? 0) + $horas;
    }

    // Número de sesiones
    $sqlSesiones = "
    SELECT id_doce, COUNT(*) AS sesiones
    FROM tutoria_sesiones_tutoria_f78
    WHERE id_doce IN ($ids_list)
      AND id_semestre = ?
      AND MONTH(fecha) = ?
      AND TRIM(LOWER(color)) = '#00a65a'
    GROUP BY id_doce";
    $stmtSesiones = $conexion->conexion->prepare($sqlSesiones);
    $stmtSesiones->bind_param("ii", $id_semestre, $mes_num);
    $stmtSesiones->execute();
    $resSesiones = $stmtSesiones->get_result();
    while ($r = $resSesiones->fetch_assoc()) {
        $sesiones_docentes[$r['id_doce']] = (int)$r['sesiones'];
    }
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
h2 { text-align: center; background-color: #2c3e50; color: white; padding: 15px; border-radius: 5px; font-size: 20px;  }
form { margin-bottom: 20px; text-align: center; }
table { width: 100%; border-collapse: collapse; font-size: 12px; }
th, td { padding: 6px; border: 1px solid #ccc; text-align: center; }
th { background: #34495e; color: white; }
.si { background-color: rgb(7, 240, 104); }
.no { background-color: rgb(244, 48, 30); }
button { background:none; border:none; cursor:pointer; }
td.SI { background-color:rgb(143, 243, 184); }
td.NO { background-color:rgb(239, 123, 113); }
</style>
</head>
<body>
<div class="contenedor">
<h2><strong> CONTROL DE HORAS NO LECTIVAS DE TUTORÍA — <?=strtoupper($mes_filtro)?> — <?=$escuelas[$id_car_filtro]?></strong></h2>

<div style="float: right;">
    <a href="https://tutoria.undc.edu.pe/vista/reportes_generales/lista_critico.php" 
       target="_blank"
       style="background-color: rgb(211, 28, 28); color: white; padding: 4px 4px; border-radius: 4px; text-decoration: none; font-weight: bold;">
       CRÍTICO
    </a>
</div>

<form method="GET" style="display: flex; align-items: center; gap: 10px; justify-content: center; flex-wrap: wrap;">
  <input type="hidden" name="pagina" value="reportes_generales/cumplimiento_no_lectivas.php">

  Mes:
  <select name="mes" onchange="this.form.submit()">
    <?php foreach (['septiembre','octubre','noviembre','diciembre'] as $m): ?>
      <option value="<?=$m?>" <?=$mes_filtro==$m?'selected':''?>><?=ucfirst($m)?></option>
    <?php endforeach; ?>
  </select>

  Carrera:
  <select name="carrera" onchange="this.form.submit()">
    <?php foreach ($escuelas as $id => $nom): ?>
      <option value="<?=$id?>" <?=$id==$id_car_filtro?'selected':''?>><?=$nom?></option>
    <?php endforeach; ?>
  </select>
</form>

<table>
<tr>
  <th>ID</th><th>Docente</th><th>Correo</th><th>Condición</th><th>Celular</th>
  <th>H.Semanales</th><th>H.Mensuales</th><th>NºSesiones</th><th>H. Acumuladas</th><th>Informe</th>
</tr>

<?php foreach ($docentes as $id_doce => $row):
    $docente = $row['docente'];

    // horas semanales desde tabla nueva; si no existe, caer a PIT
    $horas = $horas_editadas[$id_doce] ?? ($horas_docentes[$id_doce] ?? 0);

    // mensual: respeta lo guardado; si no, semanal x semanas
    $mensuales = (isset($horas_mensuales_edit[$id_doce]) && $horas_mensuales_edit[$id_doce] > 0)
                  ? $horas_mensuales_edit[$id_doce]
                  : round($horas * $semanas_mes, 1);

    // cumplimiento: informe AULA enviado (al menos 1)
    $enviados = $enviados_docentes[$id_doce] ?? 0;
    $cumple   = ($enviados > 0) ? 'SI' : 'NO';
    $clase    = $cumple === 'SI' ? 'si' : 'no';

    $horas_display = ($horas == 0) ? "<span class='si'>NO TIENE</span>" : $horas;

    $acumuladas  = $horas_acumuladas[$id_doce] ?? 0;
    $color_horas = ($acumuladas >= $mensuales) ? 'SI' : 'NO';
?>
<tr>
  <td><?=$id_doce?></td>
  <td><?=$docente?></td>
  <td><?=$row['email_doce']?></td>
  <td><?=$row['condicion']?></td>
  <td><?=$row['celu_doce']?></td>

  <td>
    <span id="h_<?=$id_doce?>"><?=$horas_display?></span>
    <button onclick="editar(<?=$id_doce?>)">✏️</button>
  </td>

  <td><?=$mensuales?></td>
  <td><?= isset($sesiones_docentes[$id_doce]) ? $sesiones_docentes[$id_doce] : '---' ?></td>

  <td class="<?=$color_horas?>"><?= $acumuladas > 0 ? $acumuladas : '---' ?></td>
  <td class="<?=$clase?>"><?=$cumple?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<script>
function editar(id) {
  let span = document.getElementById('h_'+id);
  let valor = span.textContent.trim();
  // Si el span tiene "NO TIENE", setea 0 por defecto
  if (valor.toUpperCase().includes('NO TIENE')) valor = 0;
  span.innerHTML = `<input type="number" step="0.1" min="0" id="input_${id}" value="${valor}" style="width:60px;">
                    <button onclick="guardar(${id})">💾</button>`;
}

function guardar(id) {
  const nuevo = document.getElementById('input_'+id).value;

  const body = new URLSearchParams({
    id_doce: id,
    horas: nuevo,
    mes: '<?= $mes_filtro ?>',
    id_car: '<?= (int)$id_car_filtro ?>'
  });

  fetch('reportes_generales/guardar_no_lectivas.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: body.toString()
  })
  .then(r => {
    if (!r.ok) throw new Error('No se pudo guardar');
    return r.text();
  })
  .then(() => {
    // Redirige de vuelta a /vista/index.php manteniendo el mismo shell
    const url = new URL(window.location.href); // p.ej. /vista/index.php?pagina=...
    url.searchParams.set('pagina', 'reportes_generales/cumplimiento_no_lectivas.php');
    url.searchParams.set('mes', '<?= $mes_filtro ?>');
    url.searchParams.set('carrera', '<?= (int)$id_car_filtro ?>');

    // Si no estás ya en /vista/index.php, fuerza esa ruta:
    url.pathname = url.pathname.replace(/\/vista\/.*$/, '/vista/index.php');

    // Usa replace para no dejar la página intermedia en el historial
    window.location.replace(url.toString());
  })
  .catch(err => alert(err.message));
}

</script>
</body>
</html>
