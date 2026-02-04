<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once(__DIR__ . '/../../modelo/modelo_conexion.php');
$conexion = new conexion();
$cn = $conexion->conectar();

$semestre = $_SESSION['S_SEMESTRE'] ?? null;

/* ========= ESTADO GLOBAL (doughnut) ========= */
$sqlEstado = "
SELECT 
  CASE WHEN td.estado IS NULL OR td.estado = '' THEN 'Pendiente' ELSE td.estado END AS estado,
  COUNT(*) AS total
FROM tutoria_derivacion_tutorado_f6 td
WHERE td.id_rol IN (2,6)
  AND td.fecha IS NOT NULL
  AND EXISTS (
    SELECT 1 FROM asignacion_estudiante ae
    WHERE ae.id_estu = td.id_estudiante
      AND ae.id_semestre = ?
  )
GROUP BY 1
";
$stmt = $cn->prepare($sqlEstado) or die('Error prepare ESTADO: '.$cn->error);
$stmt->bind_param('i', $semestre);
$stmt->execute();
$res = $stmt->get_result();
$statsEstado = [];
while ($r = $res->fetch_assoc()) { $statsEstado[$r['estado']] = (int)$r['total']; }
$stmt->close();

/* ========= POR ÁREA (apilado atendido/pendiente) ========= */
$sqlAreasApilado = "
SELECT
  CASE ta.idarea_apoyo
    WHEN 6 THEN 'OFICINA DE DEFENSORÍA UNIVERSITARIA'
    WHEN 4 THEN 'DIRECCIÓN DE BIENESTAR UNIVERSITARIO'
    WHEN 3 THEN 'SERVICIOS DE PSICOPEDAGOGÍA'
    WHEN 2 THEN 'SERVICIO SOCIAL'
    WHEN 1 THEN 'SERVICIOS MÉDICOS'
    ELSE 'OTROS'
  END AS area,
  SUM(CASE WHEN COALESCE(td.estado,'')='Atendido' THEN 1 ELSE 0 END) AS atendido,
  SUM(CASE WHEN COALESCE(td.estado,'')='' OR td.estado='Pendiente' THEN 1 ELSE 0 END) AS pendiente
FROM tutoria_derivacion_tutorado_f6 td
JOIN tutoria_area_apoyo ta 
  ON ta.idarea_apoyo = td.area_apoyo_id
WHERE td.id_rol IN (2,6)
  AND td.fecha IS NOT NULL
  AND EXISTS (
    SELECT 1 FROM asignacion_estudiante ae
    WHERE ae.id_estu = td.id_estudiante
      AND ae.id_semestre = ?
  )
GROUP BY area
ORDER BY FIELD(area,
  'OFICINA DE DEFENSORÍA UNIVERSITARIA',
  'DIRECCIÓN DE BIENESTAR UNIVERSITARIO',
  'SERVICIOS DE PSICOPEDAGOGÍA',
  'SERVICIO SOCIAL',
  'SERVICIOS MÉDICOS',
  'OTROS'
);
";
$stmt = $cn->prepare($sqlAreasApilado) or die('Error prepare AREA: '.$cn->error);
$stmt->bind_param('i', $semestre);
$stmt->execute();
$res = $stmt->get_result();

$areas = []; $serieAtendido = []; $seriePendiente = [];
while ($r = $res->fetch_assoc()) {
  $areas[] = $r['area'];
  $serieAtendido[] = (int)$r['atendido'];
  $seriePendiente[] = (int)$r['pendiente'];
}
$stmt->close();

/* JSON para JS */
$STATS_ESTADO_JSON = json_encode($statsEstado, JSON_UNESCAPED_UNICODE);
$AREAS_JSON        = json_encode($areas, JSON_UNESCAPED_UNICODE);
$ATENDIDO_JSON     = json_encode($serieAtendido, JSON_UNESCAPED_UNICODE);
$PENDIENTE_JSON    = json_encode($seriePendiente, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Estadísticas de Atenciones</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: Arial, sans-serif; margin:0; padding:0; background:#f4f6f9; }
    .header {
      background:#2c3e50; color:#fff; padding:15px 20px;
      display:flex; justify-content:space-between; align-items:center;
    }
    .header h2 { margin:0; font-size:22px; letter-spacing:.5px; text-align:center;}
    .btn-back { background:#0d6efd; color:#fff; text-decoration:none; padding:8px 14px; border-radius:8px; }

    .container { 
      display:grid; 
      grid-template-columns: repeat(2, 1fr); 
      gap:20px; 
      padding:20px; 
    }
    @media (max-width: 1100px){
      .container{ grid-template-columns: 1fr; }
    }

    .card { 
      background:#fff; border-radius:12px; 
      box-shadow:0 2px 10px rgba(0,0,0,.08); 
      padding:16px 16px 24px; 
    }
    .card h3 { margin:6px 0 8px; font-size:18px; color:#333; text-align:center; }

    /* ===== Contenedores para que la dona llene el card ===== */
    .chart-box-square{
      width:100%;
      aspect-ratio: 1 / 1;       /* cuadrado: alto = ancho del card */
      max-height: 400px;         /* tope opcional */
      margin: 0 auto;
    }
    .chart-box-rect{
      height: 400px;             /* altura cómoda para las barras */
    }
    /* Canvas ocupan todo su contenedor */
    #chartEstado, #chartArea{
      width: 100% !important;
      height: 100% !important;
      display: block;
    }
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
  <div class="header">
    <h2>ESTADÍSTICAS DE ATENCIONES</h2>
    <!-- <a class="boton-imprimir" id="btn-imprimir" onclick="window.print()" title="Imprimir documento">Imprimir</a> -->
  </div>
  <div class="container">
    <div class="card">
      <h3><strong>ESTADO DE ATENCIONES</strong></h3>
      <div class="chart-box-square">
        <canvas id="chartEstado"></canvas>
      </div>
    </div>

    <div class="card">
      <h3><strong>ATENCIONES POR ÁREA</strong></h3>
      <div class="chart-box-rect">
        <canvas id="chartArea"></canvas>
      </div>
    </div>
  </div>

<script>
const STATS_ESTADO = <?php echo $STATS_ESTADO_JSON ?? '{}' ?>;
const AREAS        = <?php echo $AREAS_JSON        ?? '[]' ?>;
const DAT_ATENDIDO = <?php echo $ATENDIDO_JSON     ?? '[]' ?>;
const DAT_PENDIENTE= <?php echo $PENDIENTE_JSON    ?? '[]' ?>;

/* ===== Doughnut: estado global ===== */
(() => {
  const labels = Object.keys(STATS_ESTADO);
  const values = labels.map(k => STATS_ESTADO[k]);
  new Chart(document.getElementById('chartEstado'), {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data: values,
        backgroundColor: ['#13b118', '#dc3545'] // Atendido / Pendiente
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,  // respeta el contenedor cuadrado
      radius: '98%',               // máximo seguro sin recortarse
      cutout: '58%',               // grosor del aro
      layout: { padding: 0 },
      plugins: { legend: { position: 'bottom' } }
    }
  });
})();

/* ===== Barras apiladas por área (verde/rojo) ===== */
function wrapLabel(label, max=22){
  const words = String(label).split(' ');
  const lines = [];
  let line = '';
  for (const w of words){
    const test = (line ? line + ' ' : '') + w;
    if (test.length > max){ lines.push(line); line = w; }
    else { line = test; }
  }
  if (line) lines.push(line);
  return lines;
}

new Chart(document.getElementById('chartArea'), {
  type: 'bar',
  data: {
    labels: AREAS,
    datasets: [
      { label:'Atendido', data: DAT_ATENDIDO, backgroundColor: '#13b118', stack: 'estado' },
      { label:'Pendiente', data: DAT_PENDIENTE, backgroundColor: '#dc3545', stack: 'estado' }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'top' } },
    scales: {
      x: {
        stacked: true,
        ticks: {
          callback: function(value){ return wrapLabel(this.getLabelForValue(value), 22); },
          maxRotation: 0,
          autoSkip: false
        }
      },
      y: {
        stacked: true,
        beginAtZero: true,
        ticks: { precision: 0 }
      }
    }
  }
});
</script>
</body>
</html>
