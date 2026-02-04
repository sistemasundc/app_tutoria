<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['S_IDUSUARIO']) ||
    ($_SESSION['S_ROL'] ?? '') !== 'DEPARTAMENTO ESTUDIOS GENERALES'
) {
    die('Acceso denegado');
}
// Si existe un filtro por carrera (reemplaza $_SESSION['S_SCHOOL'] solo si viene desde GET)
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');
$conexion = new conexion();
$cn = $conexion->conectar();

// Si existe un filtro por carrera
$id_semestre = $_SESSION['S_SEMESTRE'];
$id_car = $_SESSION['S_SCHOOL'];
if (isset($_GET['id_car']) && is_numeric($_GET['id_car'])) {
    $id_car = intval($_GET['id_car']);
}

// Conexión y carreras disponibles
$listaCarreras = [];
$sqlCar = "SELECT id_car, nom_car FROM carrera ORDER BY nom_car";
$resCar = $cn->query($sqlCar);
while ($row = $resCar->fetch_assoc()) {
    $listaCarreras[] = $row;
}

// Meses disponibles
$meses = [];
$sqlMeses = "SELECT DISTINCT LOWER(mes_informe) AS mes 
             FROM tutoria_informe_mensual 
             ORDER BY FIELD(LOWER(mes_informe),
             'abril','mayo','junio',
             'julio','agosto','septiembre','octubre','noviembre','diciembre')";

$res = $cn->query($sqlMeses);
while ($r = $res->fetch_assoc()) {
    $meses[] = $r['mes'];
}

// Mes seleccionado por GET
$mes_seleccionado = 'septiembre';
if (isset($_GET['mes']) && in_array(strtolower($_GET['mes']), $meses)) {
    $mes_seleccionado = strtolower($_GET['mes']);
}

//Obbtener nombrede escuela profesional
$sqlCarrera = "SELECT nom_car FROM carrera WHERE id_car = ?";
$stmt = $cn->prepare($sqlCarrera);
$stmt->bind_param("i", $id_car);
$stmt->execute();
$escuela = $stmt->get_result()->fetch_assoc()['nom_car'] ?? 'Escuela';


// Total tutores CURSO (solo EG)
$sqlTotalCurso = "
SELECT COUNT(DISTINCT cl.id_doce) AS total
FROM carga_lectiva cl
JOIN asignatura a ON cl.id_asi = a.id_asi
WHERE cl.id_semestre = ? 
  AND cl.id_car = ? 
  AND a.tipo_c = 'EG'
";
$stmt = $cn->prepare($sqlTotalCurso);
$stmt->bind_param("ii", $id_semestre, $id_car);
$stmt->execute();
$total_curso = $stmt->get_result()->fetch_assoc()['total'] ?: 1;

// Cumplimiento CURSO (solo EG)
$sqlEnvioCurso = "
SELECT COUNT(DISTINCT cl.id_doce) AS enviados
FROM carga_lectiva cl
INNER JOIN tutoria_informe_mensual_curso imc ON cl.id_cargalectiva = imc.id_cargalectiva
JOIN asignatura a ON cl.id_asi = a.id_asi
WHERE cl.id_semestre = ? 
  AND cl.id_car = ? 
  AND imc.estado_envio = 2 
  AND LOWER(imc.mes_informe) = ? 
  AND a.tipo_c = 'EG'
";
$stmt = $cn->prepare($sqlEnvioCurso);
$stmt->bind_param("iis", $id_semestre, $id_car, $mes_seleccionado);
$stmt->execute();
$enviados_curso = ($stmt->get_result()->fetch_assoc()['enviados'] ?? 0);

// Cálculo final
$porc_curso = round(($enviados_curso / $total_curso) * 100, 2);

//RANKING DE DOCENETS 
$sqlRanking = "SELECT preg_6 FROM tutoria_encuesta_satisfaccion WHERE escuela_profesional = ?";
$stmt = $cn->prepare($sqlRanking);
$stmt->bind_param("s", $escuela);
$stmt->execute();
$res = $stmt->get_result();

$puntajes = [];   // [id_doce => [total, cantidad]]
$idsEncuesta = []; // ids encontrados en encuesta

while ($r = $res->fetch_assoc()) {
    $json = json_decode($r['preg_6'], true);
    if (is_array($json)) {
        foreach ($json as $id_docente => $puntos) {
            $puntajes[$id_docente]['total'] = ($puntajes[$id_docente]['total'] ?? 0) + $puntos;
            $puntajes[$id_docente]['cantidad'] = ($puntajes[$id_docente]['cantidad'] ?? 0) + 1;
            $idsEncuesta[$id_docente] = true;
        }
    }
}

// Si no hay respuestas, salir
if (empty($idsEncuesta)) {
    $dataRanking = [];
} else {
    // 2️⃣ filtrar los que tienen asignaturas EG
    $ids = implode(',', array_map('intval', array_keys($idsEncuesta)));

    $sqlEG = "
        SELECT DISTINCT cl.id_doce
        FROM carga_lectiva cl
        JOIN asignatura a ON cl.id_asi = a.id_asi
        WHERE cl.id_doce IN ($ids)
          AND cl.id_semestre = ?
          AND cl.id_car = ?
          AND a.tipo_c = 'EG'
    ";
    $stmt = $cn->prepare($sqlEG);
    $stmt->bind_param("ii", $id_semestre, $id_car);
    $stmt->execute();

    $resEG = $stmt->get_result();
    $docentesEG = [];
    while ($row = $resEG->fetch_assoc()) {
        $docentesEG[$row['id_doce']] = true;
    }

    // 3️⃣ calcular promedios solo de docentes EG
    $promedios = [];
    foreach ($puntajes as $id => $data) {
        if (!isset($docentesEG[$id])) continue; // solo EG
        $prom = $data['total'] / $data['cantidad'];
        $porcentaje = round(($prom / 5) * 100, 1);
        $promedios[$id] = $porcentaje;
    }

    // 4️⃣ obtener nombres de los docentes
    $nombres = [];
    if (!empty($promedios)) {
        $idsValidos = implode(',', array_map('intval', array_keys($promedios)));
        $sqlDocentes = "
            SELECT id_doce, CONCAT(UPPER(abreviatura_doce), ' ', UPPER(apepa_doce), ' ', UPPER(apema_doce), ', ', UPPER(nom_doce)) AS nombre 
            FROM docente WHERE id_doce IN ($idsValidos)
        ";
        $resDoc = $cn->query($sqlDocentes);

        while ($row = $resDoc->fetch_assoc()) {
            $nombres[$row['id_doce']] = $row['nombre'];
        }
    }

    // 5️⃣ ordenar descendente
    arsort($promedios);

    // 6️⃣ armar data para gráfico
    $dataRanking = [];
    foreach ($promedios as $id => $porcentaje) {
        $dataRanking[] = [
            'docente' => $nombres[$id] ?? "Docente $id",
            'promedio' => $porcentaje
        ];
    }
}

// Top 5
$top5 = array_slice($dataRanking, 0, 5);
$completo = json_encode($dataRanking);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte de Tutores</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      margin: 0;
    }

    h2 {
      text-align: center;
      background-color: #2c3e50;
      color: white;
      padding: 15px;
      font-size: 18px;
      font-weight: bold;
    }

    .contenedor {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 30px;
    }

    .grafico-box {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        text-align: center;
        max-width: 700px; /* ajusta según tu gusto */
        width: 90%;       /* o 100% si prefieres */
        position: relative;
    }

    .titulo {
      font-size: 16px;
      font-weight: bold;
      margin-bottom: 15px;
    }

    .dona {
    --valor: 0;
    width: 180px; /* antes 120px */
    height: 180px;
    margin: auto;
    border-radius: 50%;
      background: conic-gradient(
rgb(19, 150, 84) calc(var(--valor) * 1%),
rgb(243, 59, 59) 0
      );
      position: relative;
    }

    .dona::before {
        content: '';
        position: absolute;
        top: 35px; /* ajusta para que el agujero sea proporcional */
        left: 35px;
        width: 110px;
        height: 110px;
        background: white;
        border-radius: 50%;
        }

    .porcentajes {
      margin-top: 12px;
      font-size: 14px;
      line-height: 1.6;
    }

    .porcentajes span {
      display: inline-block;
      margin: 3px 5px;
    }

    .cuadro {
      display: inline-block;
      width: 12px;
      height: 12px;
      border-radius: 2px;
      margin-right: 5px;
      vertical-align: middle;
    }

    .azul { background-color: rgb(19, 150, 84) ; }
    .naranja { background-color: rgb(243, 59, 59); }
    .ranking-box {
      background: white;
      padding: 20px;
      margin: 0px 30px;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }

    .ranking-title {
      font-size: 16px;
      font-weight: bold;
      margin-bottom: 15px;
      text-align: center;
    }

    .ranking-item {
      display: flex;
      align-items: center;
      margin-bottom: 8px;
    }

    .ranking-label {
      width: 40%;
      font-size: 14px;
      font-weight: 500;
    }

    .ranking-bar-container {
      flex: 1;
      background: #eee;
      border-radius: 5px;
      overflow: hidden;
      height: 20px;
    }

    .ranking-bar {
      height: 20px;
      background: #2e86de;
      text-align: right;
      padding-right: 5px;
      color: #fff;
      font-size: 12px;
      line-height: 20px;
    }

    .ver-completo-btn {
      display: block;
      margin: 10px auto 0;
      padding: 6px 12px;
      background:rgb(230, 121, 20);
      color: white;
      border: none;
      cursor: pointer;
      border-radius: 5px;
    }
    .btn-ver { position: absolute; top: 10px; right: 10px; background: #1a5276; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; font-size: 12px; }
  </style>
</head>
<body>
<h2>
  REPORTE DE ACTIVIDAD DE TUTORES - ESTUDIOS GENERALES- <?= strtoupper($escuela) ?> - <?= strtoupper($mes_seleccionado) ?> - 2025-2
</h2>
  <form method="GET" action="index.php" style="text-align:center; margin-top: 10px;">
		 <input type="hidden" name="pagina" value="estudios_generales/eg_porcentaje_tutores.php">

		<label for="id_car">Escuela Profesional:</label>
		<select name="id_car" onchange="this.form.submit()" style="padding: 5px; margin-right: 10px;">
			<?php
			foreach ($listaCarreras as $carrera) {
				$selected = ($carrera['id_car'] == $id_car) ? 'selected' : '';
				echo "<option value='{$carrera['id_car']}' $selected>" . strtoupper($carrera['nom_car']) . "</option>";
			}
			?>
		</select>

		<label for="mes">Mes:</label>
		<select name="mes" onchange="this.form.submit()" style="padding: 5px;">
			<?php
			foreach ($meses as $mes) {
				$sel = ($mes === $mes_seleccionado) ? 'selected' : '';
				echo "<option value='$mes' $sel>" . ucfirst($mes) . "</option>";
			}
			?>
		</select>
	</form>

<div class="contenedor">

  <div class="grafico-box">
    <button class="btn-ver" onclick="verDetalle(<?= $id_car ?>, '<?= $mes_seleccionado ?>')">VER</button>
    <div class="titulo">Cumplimiento entrega de informes - Tutor de Curso</div>
    <div class="dona" style="--valor: <?= $porc_curso ?>;"></div>
    <div class="porcentajes">
      <span><span class="cuadro azul"></span><strong> CUMPLIMIENTO: <?= $porc_curso ?>%</strong></span><br>
      <span><span class="cuadro naranja"></span><strong> INCUMPLIMIENTO: <?= round(100 - $porc_curso, 2) ?>%</strong></span>
    </div>
  </div>

</div>
<!-- <div class="ranking-box" id="rankingBox">
  <div class="ranking-title">RANKING DE DOCENTES (Encuesta de Satisfacción Estudiantil)</div>
  <div id="rankingContent"></div>
  <button class="ver-completo-btn" id="toggleBtn" onclick="toggleRanking()">Ver Completo</button>
</div> -->

<script>
const rankingData = <?= json_encode($dataRanking) ?>;
let showingAll = false;

function renderRanking(data) {
  const container = document.getElementById('rankingContent');
  container.innerHTML = '';
  data.forEach((d, idx) => {
    const div = document.createElement('div');
    div.classList.add('ranking-item');
    div.innerHTML = `
      <div class="ranking-label">${idx + 1}º - ${d.docente}</div>
      <div class="ranking-bar-container">
        <div class="ranking-bar" style="width:${d.promedio}%; ">
          ${d.promedio.toFixed(1)}%
        </div>
      </div>
    `;
    container.appendChild(div);
  });
}

// Mostrar Top 5 al cargar
renderRanking(rankingData.slice(0,5));

function toggleRanking() {
  const btn = document.getElementById('toggleBtn');
  if (!showingAll) {
    renderRanking(rankingData);
    btn.textContent = 'Mostrar Menos';
    showingAll = true;
  } else {
    renderRanking(rankingData.slice(0,5));
    btn.textContent = 'Ver Completo';
    showingAll = false;
  }
}
function verDetalle(id_car, mes) {
    const url = `estudios_generales/eg_reporte_envios_informe.php?id_car=${id_car}&mes=${encodeURIComponent(mes)}`;
    window.open(url, '_blank', 'width=1000,height=700');
}
</script>
</body>
</html>