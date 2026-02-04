<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../modelo/modelo_conexion.php';

$conexion = new conexion();
$cn = $conexion->conectar();

$id_car   = isset($_GET['id_car']) && $_GET['id_car'] !== '' ? (int)$_GET['id_car'] : null;
$mes      = isset($_GET['mes']) && $_GET['mes'] !== '' ? (int)$_GET['mes'] : null;
$semestre = (int)($_SESSION['S_SEMESTRE'] ?? 0);

$sql = "
SELECT 
  td.id_derivaciones,
  e.id_estu,
  CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS estudiante,

  COALESCE(
    (
      SELECT c.nom_car
      FROM asignacion_estudiante ae
      JOIN carga_lectiva cl ON ae.id_cargalectiva = cl.id_cargalectiva
      JOIN carrera c        ON cl.id_car          = c.id_car
      WHERE ae.id_estu = e.id_estu
        AND ae.id_semestre = ?
      LIMIT 1
    ),
    c_doc.nom_car
  ) AS carrera_estudiante,

  d.id_doce,
  CONCAT(d.abreviatura_doce, ' ', d.apepa_doce, ' ', d.apema_doce, ' ', d.nom_doce) AS docente,
  td.motivo_ref,
  td.estado,
  td.fecha,
  ta.des_area_apo AS oficina,
  CONCAT(ta.ape_encargado, ' ', ta.nombre_encargado) AS profesional,
  ta.email AS correo

FROM tutoria_derivacion_tutorado_f6 td
JOIN estudiante e          ON e.id_estu = td.id_estudiante
JOIN docente d             ON d.id_doce = td.id_docente
LEFT JOIN carrera c_doc    ON c_doc.id_car = d.id_car   -- fallback por la escuela del docente
JOIN tutoria_area_apoyo ta ON ta.idarea_apoyo = td.area_apoyo_id
WHERE td.fecha IS NOT NULL
  AND td.id_rol IN (2,6)
";

$types  = 'i';              // semestre del subquery
$params = [$semestre];

if ($id_car !== null) {
    $sql     .= " AND d.id_car = ?";
    $types   .= 'i';
    $params[] = $id_car;
}

if ($mes !== null) {
    $sql     .= " AND MONTH(td.fecha) = ?";
    $types   .= 'i';
    $params[] = $mes;
}

$sql .= "
ORDER BY
  CASE WHEN td.estado = 'Atendido' THEN 0 ELSE 1 END,
  td.fecha DESC
";

$stmt = $cn->prepare($sql);
if (!$stmt) {
    die('Error en prepare: ' . $cn->error);
}

$stmt->bind_param($types, ...$params);
$stmt->execute();

$res = $stmt->get_result();
if (!$res) {
    die('Error al ejecutar la consulta: ' . $stmt->error);
}
?>
<style>
  .container { max-width: 100%; margin: 20px auto; background: #fff; padding: 20px; box-shadow: 0 0 8px rgba(0,0,0,0.1); border-radius: 8px; font-family: Arial, sans-serif; overflow-x: auto;}
  .table-responsive {  width: 100%; overflow-x: auto;}
  table {  width: 100%;  border-collapse: collapse;  font-size: 14px;  min-width: 800px;}
  th, td { border: 1px solid #ccc; padding: 8px; text-align: left;  vertical-align: middle;}
  th {  background-color: #2c3e50;  color: white;  text-align: center;}
  tr:nth-child(even) {  background-color: #f9f9f9;}
  .btn-formato { display: inline-block;  padding: 4px 8px;  background-color: #007BFF;  color: #fff;  border-radius: 4px;  text-decoration: none;  font-size: 12px;  text-align: center;  white-space: nowrap;}
  .btn-formato:hover { background-color: #0056b3;}
  h3 { text-align: center; background-color: #2c3e50; color: white; padding: 15px; border-radius: 5px; font-size: 20px; margin-bottom: 20px;}
  .grafico{ background: #ee9325ff; color:#fff; border:none; border-radius:6px;padding:8px 12px; cursor:pointer; font-weight:600; box-shadow:0 2px 6px rgba(0,0,0,.1); float: right; margin-top:-8px;}
  .grafico:hover{ background: #eeb167ff; color: #f6f6f6ff;}
  /* Para pantallas pequeñas */
  @media (max-width: 768px) {
    table { font-size: 12px;}
    .btn-formato {font-size: 10px; padding: 3px 6px;}
    h3 {font-size: 18px;}
  }
  .badge-estado{ display:inline-block; padding:3px 10px;  border-radius:6px;  font-weight:600;  font-size:12px; border:1px solid transparent;}
  .badge-estado.atendido{color:#1e7e34; background:#eaf7ed; border-color:#28a745;}
  .badge-estado.pendiente{ color:#b02a37;  background:#fdecec; border-color:#dc3545;}
  .badge-estado.otro{  color:#2c3e50; background:#eef2f7; border-color:#95a5a6;}
</style>

<div class="container">
<h3><strong>REPORTE DE DERIVACIONES</strong>
  <a class="grafico" href="#" onclick="abrirEstadisticas(); return false;"><strong>GRÁFICO</strong></a>
</h3>

<table id="tabla_derivaciones">
<thead>
<tr>
<th>Nº</th>
<th>Escuela Profesional</th>
<th>Estudiante</th>
<th>  Referencia  </th>
<th>Tutor</th>
<th>   Fecha    </th>
<th>Estado</th>
<th>Área</th>
<th>  Responsable </th>
<th>Contra-Referencia</th>
</tr>
</thead>
<tbody>
<?php
$contador = 1;
while ($row = $res->fetch_assoc()) {
?>
<tr>
<td><?= $contador++ ?></td>
<td><?= htmlspecialchars($row['carrera_estudiante']) ?></td>
<td><?= htmlspecialchars($row['estudiante']) ?></td>
<td>
    <a class="btn-formato" href="javascript:abrirF6(<?= htmlspecialchars($row['id_estu']) ?>)">
        📄 Formato
    </a>
</td>
<td><?= htmlspecialchars($row['docente']) ?></td>
<td><?= htmlspecialchars($row['fecha'] ?? '---') ?></td>
<?php
  $estadoRaw  = $row['estado'] ?? '';
  $estadoNorm = strtolower(trim($estadoRaw));
  $clsEstado  = ($estadoNorm === 'atendido') ? 'atendido'
             : (($estadoNorm === 'pendiente') ? 'pendiente' : 'otro');
?>
<td>
  <span class="badge-estado <?= $clsEstado ?>">
    <?= htmlspecialchars($estadoRaw) ?>
  </span>
</td>
<td><?= htmlspecialchars($row['oficina']) ?></td>
<td><?= htmlspecialchars($row['profesional']) ?> <?= htmlspecialchars($row['correo']) ?></td>
<td>
<?php if (strtolower($row['estado']) == 'atendido') { ?>
    <center><a class="btn-formato" href="javascript:abrirF6_2(<?= htmlspecialchars($row['id_estu']) ?>)">
        📄 Formato
    </a></center>
<?php } else {
    echo '---';
} ?>
</td>
</tr>
<?php } ?>
</tbody>
</table>

<br>
<!-- <button onclick="exportTableToExcel('tabla_derivaciones', 'Derivaciones')"> Exportar a Excel</button> -->
</div>
<script>
function exportTableToExcel(tableID, filename = '') {
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    filename = filename ? filename + '.xls' : 'excel_data.xls';
    downloadLink = document.createElement("a");
    document.body.appendChild(downloadLink);
    if (navigator.msSaveOrOpenBlob) {
        var blob = new Blob(['\ufeff', tableHTML], { type: dataType });
        navigator.msSaveOrOpenBlob(blob, filename);
    } else {
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
        downloadLink.download = filename;
        downloadLink.click();
    }
}
function abrirF6(id_estu) {
    window.open(
        '/pdf_ge/report_referencia.php?id_estu=' + id_estu,
        'FormatoF6',
        'width=800,height=600,resizable,scrollbars'
    );
}
function abrirF6_2(id_estu) {
    window.open(
        '/pdf_ge/report_contrareferencia.php?id_estu=' + id_estu,
        'FormatoF6_2',
        'width=800,height=600,resizable,scrollbars'
    );
}
// Pasamos filtros actuales (si existen) para que la estadística use lo mismo
// Pasamos filtros actuales (si existen) para que la estadística use lo mismo
const ID_CAR = <?php echo json_encode($id_car ?? null); ?>;
const MES    = <?php echo json_encode($mes ?? null); ?>;

function abrirEstadisticas() {
  const params = [];
  if (ID_CAR) params.push('id_car=' + encodeURIComponent(ID_CAR));
  if (MES)    params.push('mes=' + encodeURIComponent(MES));
  const qs  = params.length ? ('?' + params.join('&')) : '';
  const url = 'reportes_generales/estadistica_atenciones.php' + qs;

  // ⇩⇩ AJUSTA AQUÍ EL TAMAÑO
  const WIDTH  = 900; // más ancho, se verán los 2 gráficos completos
  const HEIGHT = 450;  // REDUCE este valor si quieres aún menos alto (p.ej. 480)

  // Cálculo para centrar
  const dualLeft = window.screenLeft ?? window.screenX ?? 0;
  const dualTop  = window.screenTop  ?? window.screenY ?? 0;
  const vw = window.innerWidth  || document.documentElement.clientWidth;
  const vh = window.innerHeight || document.documentElement.clientHeight;
  const left = Math.max(0, dualLeft + Math.round((vw - WIDTH)  / 2));
  const top  = Math.max(0, dualTop  + Math.round((vh - HEIGHT) / 2));

  const win = window.open(
    url,
    'VentanaEstadisticas',
    `width=${WIDTH},height=${HEIGHT},left=${left},top=${top},resizable=yes,scrollbars=yes`
  );
  if (win) win.focus();
}
</script>