<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Lima');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once(__DIR__ . "/../../modelo/modelo_conexion.php");

if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], ['DIRECTOR DE DEPARTAMENTO ACADEMICO','COMITÉ - SUPERVISIÓN'])) {
    die('Acceso no autorizado');
}

$semestre        = $_SESSION['S_SEMESTRE'];
$id_director     = $_SESSION['S_IDUSUARIO'];
$id_car_director = $_SESSION['S_SCHOOL'];

$conexion = new conexion();
$conexion->conectar();

$nombre_escuela = '';
$sqlEscuela = "SELECT nom_car FROM carrera WHERE id_car = ?";
$stmtEscuela = $conexion->conexion->prepare($sqlEscuela);
$stmtEscuela->bind_param("i", $id_car_director);
$stmtEscuela->execute();
$resEscuela = $stmtEscuela->get_result()->fetch_assoc();
$nombre_escuela = $resEscuela['nom_car'] ?? '';

$ciclo_filtro = $_GET['ciclo'] ?? '';
$id_cargalectiva_filtro = $_GET['id_cargalectiva'] ?? '';
$mes_filtro = $_GET['mes'] ?? '';

$sqlCiclos = "SELECT DISTINCT ciclo FROM carga_lectiva WHERE id_car = ? AND id_semestre = ? ORDER BY ciclo ASC";
$stmtCiclos = $conexion->conexion->prepare($sqlCiclos);
$stmtCiclos->bind_param("ii", $id_car_director, $semestre);
$stmtCiclos->execute();
$ciclos = $stmtCiclos->get_result()->fetch_all(MYSQLI_ASSOC);

$asignaturas = [];
if ($ciclo_filtro !== '') {
    $sqlAsig = "
        SELECT DISTINCT cl.id_cargalectiva, a.nom_asi
        FROM carga_lectiva cl
        JOIN asignatura a ON a.id_asi = cl.id_asi
        WHERE cl.ciclo = ? AND cl.id_car = ? AND cl.id_semestre = ?
        ORDER BY a.nom_asi ASC
    ";
    $stmtAsig = $conexion->conexion->prepare($sqlAsig);
    $stmtAsig->bind_param("sii", $ciclo_filtro, $id_car_director, $semestre);
    $stmtAsig->execute();
    $asignaturas = $stmtAsig->get_result()->fetch_all(MYSQLI_ASSOC);
}

$sql = "
SELECT imc.*, d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce,
       a.nom_asi, cl.ciclo, cl.seccion, cl.turno,
       (
         SELECT estado_revision 
         FROM tutoria_revision_director_informe_curso r 
         WHERE r.id_cargalectiva = imc.id_cargalectiva 
           AND LOWER(r.mes_informe) = LOWER(imc.mes_informe)
         LIMIT 1
       ) AS estado_revision,
       (
         SELECT fecha_revision 
         FROM tutoria_revision_director_informe_curso r 
         WHERE r.id_cargalectiva = imc.id_cargalectiva 
           AND LOWER(r.mes_informe) = LOWER(imc.mes_informe)
         LIMIT 1
       ) AS fecha_revision
FROM tutoria_informe_mensual_curso imc
JOIN docente d ON d.id_doce = imc.id_doce
JOIN carga_lectiva cl ON cl.id_cargalectiva = imc.id_cargalectiva
JOIN asignatura a ON cl.id_asi = a.id_asi
WHERE imc.estado_envio = 2
  AND cl.id_semestre = ?
  AND cl.id_car = ?
";


$types = "ii";
$params = [$semestre, $id_car_director];

if (!empty($ciclo_filtro)) {
    $sql .= " AND cl.ciclo = ?";
    $types .= "s";
    $params[] = $ciclo_filtro;
}
if (!empty($id_cargalectiva_filtro)) {
    $sql .= " AND cl.id_cargalectiva = ?";
    $types .= "i";
    $params[] = $id_cargalectiva_filtro;
}
if (!empty($mes_filtro)) {
    $mes_normalizado = strtolower(trim($mes_filtro));
    $sql .= " AND LOWER(TRIM(imc.mes_informe)) = ?";
    $types .= "s";
    $params[] = $mes_normalizado;
}
$sql .= " ORDER BY cl.ciclo ASC, imc.fecha_envio DESC";

$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$filas = $res->fetch_all(MYSQLI_ASSOC);
?>
<style>

.container {
  max-width: 98%;
  margin: 20px auto;
  background: white;
  padding: 15px;
  border-radius: 10px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

thead {
  background-color: #154360;
  color: white;
}

table th, table td {
  padding: 10px;
  border: 1px solid #ccc;
  text-align: center;
}
#formFiltro select,
  #formFiltro button {
    padding: 6px 10px;
    font-size: 14px;
    border-radius: 5px;
    border: 1px solid #ccc;
}

#formFiltro button {
    background-color: #2ecc71;
    color: white;
    cursor: pointer;
    border: none;
}

.btn {
  padding: 6px 10px;
  font-size: 13px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-primary { background-color: #2980b9; color: white; }
.btn-success { background-color: #27ae60; color: white; }

@media (max-width: 768px) {
  table, thead, tbody, th, td, tr {
    display: block;
  }

  thead {
    display: none;
  }

  tr {
    margin-bottom: 15px;
    border-radius: 10px;
    background: #f9f9f9;
    padding: 10px;
  }

  td {
    position: relative;
    padding-left: 50%;
    text-align: left;
  }

  td::before {
    position: absolute;
    top: 10px;
    left: 10px;
    font-weight: bold;
    white-space: nowrap;
  }

  td:nth-child(1)::before { content: "Ciclo"; }
  td:nth-child(2)::before { content: "Asignatura"; }
  td:nth-child(3)::before { content: "Docente"; }
  td:nth-child(4)::before { content: "Fecha de Envío"; }
  td:nth-child(5)::before { content: "Informe"; }
  td:nth-child(6)::before { content: "Estado"; }
  td:nth-child(7)::before { content: "Fecha de Conformidad"; }
}

</style>
<div class="container">
  <form method="GET" id="formFiltro">
    <input type="hidden" name="pagina" value="coordinador/vista_informe_mensual_cursos.php">
    <select name="ciclo" onchange="document.getElementById('formFiltro').submit()">
      <option value="">-- Seleccione ciclo --</option>
      <?php foreach ($ciclos as $c): ?>
        <option value="<?= $c['ciclo'] ?>" <?= ($ciclo_filtro == $c['ciclo']) ? 'selected' : '' ?>><?= $c['ciclo'] ?></option>
      <?php endforeach; ?>
    </select>
    <select name="id_cargalectiva">
      <option value="">-- Seleccione asignatura --</option>
      <?php foreach ($asignaturas as $a): ?>
        <option value="<?= $a['id_cargalectiva'] ?>" <?= ($id_cargalectiva_filtro == $a['id_cargalectiva']) ? 'selected' : '' ?>><?= $a['nom_asi'] ?></option>
      <?php endforeach; ?>
    </select>
    <select name="mes" onchange="document.getElementById('formFiltro').submit()">
      <option value="">-- Mes --</option>
      <!-- ?php foreach (["abril", "mayo", "junio", "julio", "agosto"] as $m): ?> -->
      <?php foreach (["agosto", "septiembre", "octubre", "noviembre", "diciembre"] as $m): ?>
        <option value="<?= $m ?>" <?= ($mes_filtro == $m) ? 'selected' : '' ?>><?= ucfirst($m) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Filtrar</button>
  </form>

  <a style="float: right; margin-bottom: 15px;" href="direccion_escuela/reporte_envios_informe_cursos.php" 
   onclick="return abrirVentanaPopup2(this.href);" 
   class="btn btn-success btn-sm">📋 VER REPORTE</a>

  <h3>
    INFORMES MENSUALES - TUTORES DE CURSOS
    <?php if (!empty($nombre_escuela)): ?>
      DE LA ESCUELA PROFESIONAL DE <?= strtoupper(htmlspecialchars($nombre_escuela)) ?>
    <?php endif; ?>
  </h3>
  <table>
    <thead>
      <tr>
        <th>Ciclo</th>
        <th>Asignatura</th>
        <th>Docente</th>
        <th>Fecha de Envío</th>
        <th>Informe</th>
        <th>Estado</th>
        <th>Fecha de Conformidad</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($filas) > 0): ?>
        <?php foreach ($filas as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['ciclo']) ?></td>
            <td><?= htmlspecialchars($row['nom_asi']) ?><br><small><b>Sección:</b> <?= $row['seccion'] ?> | <b>Turno:</b> <?= $row['turno'] ?></small></td>
            <td><?= htmlspecialchars($row['abreviatura_doce'] . ' ' . $row['apepa_doce'] . ' ' . $row['apema_doce'] . ' ' . $row['nom_doce']) ?></td>
            <td><?= !empty($row['fecha_envio']) ? date('d/m/Y H:i', strtotime($row['fecha_envio'])) : '<span class="text-muted">--</span>' ?></td>
            <td><a href="#" onclick="abrirVentanaPopup('docente/vista_prev_informe_mensual.php?id_cargalectiva=<?= $row['id_cargalectiva'] ?>&mes=<?= $row['mes_informe'] ?>'); return false;" class="btn btn-primary">Ver Informe</a></td>
            <td>
              <?php if ($row['estado_revision'] === 'CONFORME'): ?>
                <span class="btn btn-success">CONFORME</span>
              <?php else: ?>
                <span class="btn btn-warning">Pendiente por Dirección</span>
              <?php endif; ?>
            </td>
            <td><?= $row['fecha_revision'] ? date('Y-m-d H:i:s', strtotime($row['fecha_revision'])) : '--' ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="7" style="color:red; text-align:center; font-weight:bold;">No hay informes registrados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<script>
function abrirVentanaPopup(url) {
  const ancho = 900;
  const alto = 700;
  const izquierda = (screen.width - ancho) / 2;
  const arriba = (screen.height - alto) / 2;
  const popup = window.open(url, 'VistaPrevTutoria', `width=${ancho},height=${alto},top=${arriba},left=${izquierda},resizable=yes,scrollbars=yes`);
  return false;
}

function abrirVentanaPopup2(url) {
  const ancho = 900;
  const alto = 700;
  const izquierda = (screen.width - ancho) / 2;
  const arriba = (screen.height - alto) / 2;
  window.open(url, 'VistaReporte', `width=${ancho},height=${alto},top=${arriba},left=${izquierda},resizable=yes,scrollbars=yes`);
  return false;
}
</script>
