<?php  
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Lima');

require_once(__DIR__ . "/../../modelo/modelo_conexion.php");
$conexion = new conexion();
$conexion->conectar();

if (
    !isset($_SESSION['S_IDUSUARIO']) ||
    ($_SESSION['S_ROL'] ?? '') !== 'DEPARTAMENTO ESTUDIOS GENERALES'
) {
    die('Acceso denegado');
}

$mes_filtro = $_GET['mes'] ?? 'septimbre';
$id_car_filtro = $_GET['id_car'] ?? '';

// Escuelas para filtro
$escuelas = $conexion->conexion->query("SELECT DISTINCT id_car, nom_car FROM carrera ORDER BY nom_car");

// Consulta
$sql = "
SELECT 
    cl.id_cargalectiva,
    cl.ciclo,
    cl.turno,
    cl.seccion,
    asi.nom_asi,
    d.id_doce,
    CONCAT(d.abreviatura_doce, ' ', d.apepa_doce, ' ', d.apema_doce, ', ', d.nom_doce) AS nombre_docente,
    ca.nom_car,
    im.fecha_envio,
    im.mes_informe,
    rev.fecha_revision
FROM tutoria_informe_mensual_curso im
JOIN carga_lectiva cl ON cl.id_cargalectiva = im.id_cargalectiva
JOIN docente d ON d.id_doce = im.id_doce
JOIN asignatura asi ON asi.id_asi = cl.id_asi
JOIN carrera ca ON ca.id_car = cl.id_car
LEFT JOIN tutoria_revision_director_informe_curso rev 
       ON rev.id_cargalectiva = im.id_cargalectiva 
      AND rev.estado_revision = 'CONFORME' 
      AND rev.mes_informe = im.mes_informe
WHERE im.estado_envio = 2
  AND asi.tipo_c = 'EG'
";

if ($id_car_filtro !== '') {
    $sql .= " AND cl.id_car = " . intval($id_car_filtro);
}
if ($mes_filtro !== '') {
    $sql .= " AND LOWER(im.mes_informe) = '" . strtolower($mes_filtro) . "'";
}

$sql .= " ORDER BY cl.ciclo, asi.nom_asi";

$res = $conexion->conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Informes Mensuales - Tutores de Cursos</title>
<style>
body { background-color: #f0f2f5; font-family: Arial, sans-serif; }
.contenedor { background: white; margin: 40px auto; padding: 10px; border-radius: 10px; max-width: 1300px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
h3 { text-align: center; color: #2c3e50; margin-bottom: 20px; }
.filtro { text-align: center; margin-bottom: 20px; }
select, button { padding: 6px 10px; font-size: 14px; margin-right: 10px; }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
th, td { border: 1px solid #ccc; padding: 8px; text-align: center; vertical-align: middle; }
th { background-color: #154360; color: white; }
.btn-ver { background-color: #2980b9; color: white; padding: 6px 10px; text-decoration: none; display: inline-block; border-radius: 4px; font-size: 13px; }

.overlay {
  position: fixed;
  top: 0; left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}
.alerta-central {
  background: #fff;
  padding: 20px 30px;
  border-radius: 8px;
  text-align: center;
  max-width: 400px;
  width: 90%;
  position: relative;
  box-shadow: 0 0 10px rgba(0,0,0,0.3);
  animation: fadeIn 0.3s ease;
}
.alerta-central strong {
  font-size: 16px;
  color: #e74c3c;
}
.alerta-central .cerrar {
  position: absolute;
  top: 8px;
  right: 12px;
  font-size: 18px;
  color: #999;
  cursor: pointer;
}
.alerta-central .cerrar:hover {
  color: #000;
}
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.9); }
  to { opacity: 1; transform: scale(1); }
}
</style>
</head>
<body>
<div class="contenedor" style="overflow-x:auto;">
<h3><strong> INFORMES MENSUALES - ESTUDIOS GENERALES - TUTORES DE CURSOS</strong></h3>

<div class="filtro">
<form method="GET" action="">
<input type="hidden" name="pagina" value="estudios_generales/eg_informe_mensual_cursos.php">
<label>Escuela Profesional:</label>
<select name="id_car">
<option value="">-- Todas --</option>
<?php $escuelas->data_seek(0); while ($e = $escuelas->fetch_assoc()): ?>
<option value="<?= $e['id_car'] ?>" <?= $id_car_filtro == $e['id_car'] ? 'selected' : '' ?>>
<?= htmlspecialchars($e['nom_car']) ?>
</option>
<?php endwhile; ?>
</select>
<label>Mes:</label>
<select name="mes" id="mes">
<option value="">-- Seleccione --</option>
<!--  foreach (["abril","mayo","junio","julio","agosto"] as $mes): ?> -->
<?php foreach (['septiembre', 'octubre', 'noviembre', 'diciembre'] as $mes): ?>
<option value="<?= $mes ?>" <?= $mes_filtro == $mes ? 'selected' : '' ?>><?= ucfirst($mes) ?></option>
<?php endforeach; ?>
</select>
<button type="submit">Filtrar</button>
</form>
</div>
<table>
<thead>
<tr>
<th>Ciclo</th>
<th>Asignatura</th>
<th>Docente</th>
<th>Fecha de Envío</th>
<th>Acción</th>
<th>Fecha de Conformidad</th>
</tr>
</thead>
<tbody>
<?php if ($res && $res->num_rows > 0): ?>
<?php while ($row = $res->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['ciclo']) ?></td>
<td>
<div class="asig-label"><?= htmlspecialchars($row['nom_asi']) ?></div>
<div class="asig-detalle">
Sección: <?= htmlspecialchars($row['seccion']) ?> |
Turno: <?= strtoupper($row['turno']) ?>
</div>
</td>
<td><?= htmlspecialchars($row['nombre_docente']) ?></td>
<td><?= $row['fecha_envio'] ?? '—' ?></td>
<td>
<form onsubmit="return abrirVentanaPopup(this);" method="GET" action="https://tutoria.undc.edu.pe/vista/docente/vista_prev_informe_mensual.php">
<input type="hidden" name="id_cargalectiva" value="<?= $row['id_cargalectiva'] ?>">
<input type="hidden" name="mes" value="<?= $mes_filtro ?>">
<button type="submit" class="btn-ver">Ver informe</button>
</form>
</td>
<td><?= $row['fecha_revision'] ?? '—' ?></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="6">No hay informes conformes registrados.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<div id="overlay" class="overlay">
  <div class="alerta-central">
    <span class="cerrar" onclick="cerrarAlerta()">×</span>
    <strong>Por favor, seleccione un mes primero.</strong>
  </div>
</div>

<script>
function abrirVentanaPopup(form) {
  const mes = document.querySelector('select[name="mes"]').value.trim();

  if (!mes) {
    mostrarAlerta();
    return false; // no abre el informe
  }

  const ancho = 900;
  const alto = 700;
  const izquierda = (screen.width - ancho) / 2;
  const arriba = (screen.height - alto) / 2;

  const url = new URL(form.action);
  const params = new URLSearchParams(new FormData(form));
  url.search = params.toString();

  window.open(
    url.toString(),
    'VistaPrevInformeCurso',
    `width=${ancho},height=${alto},top=${arriba},left=${izquierda},resizable=yes,scrollbars=yes`
  );

  return false;
}

function mostrarAlerta() {
  document.getElementById('overlay').style.display = 'flex';
}

function cerrarAlerta() {
  document.getElementById('overlay').style.display = 'none';
}
</script>
</body>
</html>
