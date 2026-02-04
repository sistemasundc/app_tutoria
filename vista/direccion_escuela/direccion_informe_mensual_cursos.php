<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Lima');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once(__DIR__ . "/../../modelo/modelo_conexion.php");

if (!isset($_SESSION['S_IDUSUARIO']) || $_SESSION['S_ROL'] !== 'DIRECCION DE ESCUELA') {
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

// =================== CARGA CICLOS ===================
$sqlCiclos = "SELECT DISTINCT ciclo FROM carga_lectiva WHERE id_car = ? AND id_semestre = ? ORDER BY ciclo ASC";
$stmtCiclos = $conexion->conexion->prepare($sqlCiclos);
$stmtCiclos->bind_param("ii", $id_car_director, $semestre);
$stmtCiclos->execute();
$ciclos = $stmtCiclos->get_result()->fetch_all(MYSQLI_ASSOC);

// =================== CARGA ASIGNATURAS ===================
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

// =================== CONSULTA PRINCIPAL ===================
$sql = "
SELECT imc.*, d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce,
       a.nom_asi, cl.ciclo, cl.seccion, cl.turno,
       (
        SELECT estado_revision 
        FROM tutoria_revision_director_informe_curso r 
        WHERE r.id_cargalectiva = imc.id_cargalectiva AND r.id_director = ?
          AND LOWER(r.mes_informe) = LOWER(imc.mes_informe)
        LIMIT 1
       ) AS estado_revision,
       (
        SELECT fecha_revision 
        FROM tutoria_revision_director_informe_curso r 
        WHERE r.id_cargalectiva = imc.id_cargalectiva AND r.id_director = ?
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

$types = "iiii";
$params = [$id_director, $id_director, $semestre, $id_car_director];

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
    $sql .= " AND LOWER(imc.mes_informe) = ?";
    $types .= "s";
    $params[] = strtolower($mes_filtro);
}

$sql .= " ORDER BY cl.ciclo ASC, imc.fecha_envio DESC";

$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$filas = $res->fetch_all(MYSQLI_ASSOC);
?>

<style>
  body {
    font-family: Arial, sans-serif;
    background-color: #ecf0f1;
    margin: 0;
    padding: 0;
  }

  .container {
    max-width: 100%;
    margin: 40px auto;
    padding: 15px;
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    width: 95%;
    overflow-x: hidden; /* importante para evitar desborde fuera del contenedor */

  }

  h3 {
    margin-bottom: 20px;
    color: #2c3e50;
  }

  /* FORMULARIO DE FILTRO */
  #formFiltro {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
    align-items: center;
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

  table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
  }

  th, td {
    padding: 10px;
    border: 1px solid #ccc;
    text-align: center;
  }

  th {
    background-color: #154360;
    color: white;
  }

  .btn {
    padding: 6px 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
  }

  .btn-primary { background-color: #2980b9; color: white; }
  .btn-success { background-color: #27ae60; color: white; }
  .btn-danger { background-color: #c0392b; color: white; }
  .btn-warning { background-color: #f39c12; color: white; }

  textarea {
    width: 100%;
    font-size: 13px;
  }

  .docentes-a-cargo {
    text-align: left;
    font-size: 13px;
  }

  .docentes-a-cargo ul {
    padding-left: 18px;
    margin: 0;
    list-style-type: disc;
  }

  .table-responsive-custom {
    overflow-x: auto;
    width: 100%;
    -webkit-overflow-scrolling: touch;
  }

  .table-responsive-custom {
    min-width: 900px;
    border-collapse: collapse;
  }

  /* =================== RESPONSIVE PARA MOVILES ===================== */
  @media (max-width: 768px) {
    #formFiltro {
      flex-direction: column;
      align-items: stretch;
    }

    table, thead, tbody, th, td, tr {
      display: block;
    }

    thead {
      display: none;
    }

    tr {
      margin-bottom: 15px;
      background: #fff;
      padding: 10px;
      border-radius: 10px;
      box-shadow: 0 0 5px #ccc;
    }

    td {
      text-align: left !important;
      padding-left: 45%;
      position: relative;
      border: none;
      border-bottom: 1px solid #eee;
    }

    td::before {
      position: absolute;
      top: 10px;
      left: 10px;
      width: 35%;
      font-weight: bold;
      white-space: nowrap;
    }

    /* Ajusta los nombres si necesitas mostrar diferentes columnas */
    td:nth-child(1)::before { content: "Ciclo"; }
    td:nth-child(2)::before { content: "Asignatura"; }
    td:nth-child(3)::before { content: "Docente"; }
    td:nth-child(4)::before { content: "Fecha de Envío"; }
    td:nth-child(5)::before { content: "Informe"; }
    td:nth-child(6)::before { content: "Estado"; }
    td:nth-child(7)::before { content: "Fecha de Conformidad"; }
    td:nth-child(8)::before { content: "Acciones"; }
  }
  .table-responsive-wrapper {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .table-responsive-custom {
    min-width: 950px; /* puede ser 1000px o más si tienes muchas columnas */
  }
</style>


<!-- ======================= HTML + SELECT ============================ -->

<div class="container">
  <form method="GET" id="formFiltro">
    <input type="hidden" name="pagina" value="direccion_escuela/direccion_informe_mensual_cursos.php">

    <select name="ciclo" onchange="document.getElementById('formFiltro').submit()">
      <option value="">-- Seleccione ciclo --</option>
      <?php foreach ($ciclos as $c): ?>
        <option value="<?= $c['ciclo'] ?>" <?= ($ciclo_filtro == $c['ciclo']) ? 'selected' : '' ?>><?= $c['ciclo'] ?></option>
      <?php endforeach; ?>
    </select>

    <select name="id_cargalectiva">
      <option value="">-- Seleccione asignatura --</option>
      <?php foreach ($asignaturas as $a): ?>
        <option value="<?= $a['id_cargalectiva'] ?>" <?= ($id_cargalectiva_filtro == $a['id_cargalectiva']) ? 'selected' : '' ?>>
          <?= $a['nom_asi'] ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="mes" onchange="document.getElementById('formFiltro').submit()">
      <option value="">-- Mes --</option>
     <!--   foreach (["abril", "mayo", "junio", "julio", "agosto"] as $m): ?> -->
      <?php foreach (["agosto","septiembre", "octubre", "noviembre", "diciembre"] as $m): ?>
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
  <div class="table-responsive-wrapper">
  <div class="table-responsive-custom">
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
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($filas) > 0): ?>
          <?php foreach ($filas as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['ciclo']) ?></td>
              <td>
                <?= htmlspecialchars($row['nom_asi']) ?><br>
                <small><b>Sección:</b> <?= $row['seccion'] ?> | <b>Turno:</b> <?= $row['turno'] ?></small>
              </td>
              <td><?= htmlspecialchars($row['abreviatura_doce'] . ' ' . $row['apepa_doce'] . ' ' . $row['apema_doce'] . ' ' . $row['nom_doce']) ?></td>
              <td><?= !empty($row['fecha_envio']) ? date('d/m/Y H:i', strtotime($row['fecha_envio'])) : '<span class="text-muted">--</span>' ?></td>
              <td>
                <a href="#" onclick="abrirVentanaPopup('docente/vista_prev_informe_mensual.php?id_cargalectiva=<?= $row['id_cargalectiva'] ?>&mes=<?= $row['mes_informe'] ?>'); return false;" class="btn btn-primary">Ver Informe</a>
              </td>
              <td>
                <?php if ($row['estado_revision']): ?>
                  <span class="btn <?= $row['estado_revision'] === 'CONFORME' ? 'btn-success' : 'btn-danger' ?>">
                    <?= $row['estado_revision'] ?>
                  </span>
                <?php else: ?>
                  <span style="color: #7f8c8d;">Pendiente</span>
                <?php endif; ?>
              </td>
              <td>
                <?= $row['fecha_revision'] ? date('Y-m-d H:i:s', strtotime($row['fecha_revision'])) : '--' ?>
              </td>
              <td>
                <?php if ($row['estado_envio'] == 2 && !$row['estado_revision']): ?>
                  <form method="POST" action="direccion_escuela/direccion_guardar_revision_informe_cursos.php" onsubmit="return confirm('¿Confirmar esta acción?')">
                    <input type="hidden" name="id_cargalectiva" value="<?= $row['id_cargalectiva'] ?>">
                    <input type="hidden" name="mes_informe" value="<?= $row['mes_informe'] ?>">
                    <input type="hidden" name="id_director" value="<?= $id_director ?>">
                    <button type="submit" name="accion" value="CONFORME" class="btn btn-success">Conforme</button>
                  </form>
                <?php else: ?>
                  --
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" style="text-align: center; color: #c0392b; font-weight: bold;">
              <?php if (!empty($mes_filtro)): ?>
                No hay informes registrados para el mes seleccionado.
              <?php else: ?>
                No hay informes registrados.
              <?php endif; ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
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
