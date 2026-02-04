<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Lima');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once(__DIR__ . "/../../modelo/modelo_conexion.php");

/* ===== CONTROL DE ACCESO (igual que arreglamos en planes) ===== */
if (!isset($_SESSION['S_IDUSUARIO'])) {
    die('Acceso no autorizado');
}
$rol = isset($_SESSION['S_ROL']) ? trim($_SESSION['S_ROL']) : '';
$roles_permitidos = [
    'DIRECCION DE ESCUELA',
    'DIRECTOR DE DEPARTAMENTO ACADEMICO',
    'COMITÉ - SUPERVISIÓN'
];
if (!in_array($rol, $roles_permitidos, true)) {
    die('Acceso no autorizado');
}

/* ===== PARÁMETROS DE SESIÓN ===== */
$semestre_sesion   = (int)($_SESSION['S_SEMESTRE'] ?? 0);
$id_director       = (int)$_SESSION['S_IDUSUARIO'];
$id_car_director   = (int)$_SESSION['S_SCHOOL'];

/* ===== SEMESTRE SELECCIONADO (GET o sesión) ===== */
$semestre_sel = isset($_GET['semestre']) && $_GET['semestre'] !== ''
    ? (int)$_GET['semestre']
    : $semestre_sesion;

/* ===== CONEXIÓN ===== */
$conexion = new conexion();
$conexion->conectar();

/* ===== CONSULTA PRINCIPAL =====
   NOTA: usamos $semestre_sel en lugar de $semestre para filtrar informes finales.
*/
$sql = "
SELECT  
    ia.id_informe_final,
    ia.id_doce,
    ia.id_cargalectiva,
    cl.ciclo,
    MONTH(ia.fecha_presentacion) AS mes_informe,
    ia.fecha_presentacion,
    ia.estado_envio,
    d.abreviatura_doce,
    d.apepa_doce,
    d.apema_doce,
    d.nom_doce,

    (
        SELECT estado_revision
        FROM tutoria_revision_director_informe_final ri
        WHERE ri.id_cargalectiva = ia.id_cargalectiva
          AND ri.id_director = ?
          AND ri.id_semestre = ?
        ORDER BY ri.fecha_revision DESC
        LIMIT 1
    ) AS estado_revision,

    (
        SELECT fecha_revision
        FROM tutoria_revision_director_informe_final ri
        WHERE ri.id_cargalectiva = ia.id_cargalectiva
          AND ri.id_director = ?
          AND ri.id_semestre = ?
        ORDER BY ri.fecha_revision DESC
        LIMIT 1
    ) AS fecha_revision

FROM tutoria_informe_final_aula ia
INNER JOIN carga_lectiva cl ON cl.id_cargalectiva = ia.id_cargalectiva
INNER JOIN docente d        ON d.id_doce = ia.id_doce
WHERE ia.estado_envio = 2
  AND ia.semestre_id = ?
  AND ia.id_car      = ?
ORDER BY cl.ciclo ASC, ia.fecha_presentacion DESC
";

/* armamos parámetros */
$params = [
    $id_director,   // ri.id_director
    $semestre_sel,  // ri.id_semestre
    $id_director,   // ri.id_director (2da subquery)
    $semestre_sel,  // ri.id_semestre  (2da subquery)
    $semestre_sel,  // ia.semestre_id
    $id_car_director // ia.id_car
];
$types = "iiiiii";

/* ejecutamos */
if (!$stmt = $conexion->conexion->prepare($sql)) {
    die("❌ Error en prepare(): " . $conexion->conexion->error . "<br><pre>$sql</pre>");
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res    = $stmt->get_result();
$filas  = $res->fetch_all(MYSQLI_ASSOC);

/* ==== (opcional) helper, lo dejo igual que tenías ==== */
function obtenerDocentesDelPlan($conexion, $ciclo, $id_plan_tutoria, $semestre) {
    $sql = "
        SELECT DISTINCT d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce
        FROM tutoria_docente_asignado tda
        JOIN docente d ON d.id_doce = tda.id_doce
        JOIN carga_lectiva cl ON cl.id_doce = d.id_doce AND cl.id_semestre = tda.id_semestre
        JOIN tutoria_plan_compartido tpc ON tpc.id_cargalectiva = cl.id_cargalectiva
        WHERE cl.ciclo = ?
          AND tda.id_semestre = ?
          AND tpc.id_plan_tutoria = ?
          AND tpc.estado_envio = 2
    ";
    $stmt = $conexion->conexion->prepare($sql);
    $stmt->bind_param("iii", $ciclo, $semestre, $id_plan_tutoria);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<style>
    body {
      font-family: Arial, sans-serif;
      background-color: #ecf0f1;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 98%;
      margin: 40px auto;
      padding: 20px;
      background-color: white;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
  h3 {
    text-align: center;
    background-color: #154360;
    color: white;
    padding: 15px;
    border-radius: 5px;
    font-size: 20px;
    margin-bottom: 20px;
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
    .btn-danger  { background-color: #c0392b; color: white; }
    .btn-warning { background-color: #f39c12; color: white; }

  @media (max-width: 768px) {
    table, thead, tbody, th, td, tr { display: block; }
    thead { display: none; }
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
    td:nth-child(1)::before { content: "Ciclo"; }
    td:nth-child(2)::before { content: "Tutor(a)"; }
    td:nth-child(3)::before { content: "Fecha de Envío"; }
    td:nth-child(4)::before { content: "Informe"; }
    td:nth-child(5)::before { content: "Estado"; }
    td:nth-child(6)::before { content: "Fecha de Conformidad"; }
    td:nth-child(7)::before { content: "Acciones"; }
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
  .table-responsive-custom::-webkit-scrollbar {
    height: 6px;
  }
  .table-responsive-custom::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
  }
  .table-responsive-custom::-webkit-scrollbar-thumb:hover {
    background: #555;
  }
  .table-responsive-custom table {
    min-width: 900px;
    border-collapse: collapse;
  }
</style>

<div class="container">

  <!-- FILA DE CONTROLES: semestre + botón reporte -->
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap;">

    <div style="display:flex; gap:10px; align-items:center;">
      <label for="semestre" style="font-weight:bold;">Semestre:</label>
      <select id="semestre" name="semestre" class="form-control" style="max-width:180px;"
              onchange="cambiarSemestre()">
        <!-- ajusta valores reales de tus semestres -->
        <option value="32" <?= ($semestre_sel == 32 ? 'selected' : '') ?>>2025-I</option>
        <option value="33" <?= ($semestre_sel == 33 ? 'selected' : '') ?>>2025-II</option>
      </select>
    </div>

    <a style="white-space:nowrap;"
       href="direccion_escuela/reporte_envios_informe_final.php"
       onclick="return abrirVentanaPopup2(this.href);"
       class="btn btn-success btn-sm">
       📋 VER REPORTE
    </a>
  </div>

  <h3><strong>INFORMES FINALES - TUTORES DE AULA</strong></h3>

  <div class="table-responsive-custom">
    <table>
      <thead>
        <tr>
          <th>Ciclo</th>
          <th>Tutor(a)</th>
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

            <td class="docentes-a-cargo">
              <ul>
                <?= $row['abreviatura_doce'] . ' ' . $row['apepa_doce'] . ' ' . $row['apema_doce'] . ', ' . $row['nom_doce'] ?>
              </ul>
            </td>

            <td>
              <?= !empty($row['fecha_presentacion'])
                    ? date('d/m/Y', strtotime($row['fecha_presentacion']))
                    : '<span class="text-muted">--</span>' ?>
            </td>

            <td>
              <?php if ((int)$row['estado_envio'] === 2): ?>
                <a href="#"
                   onclick="return abrirVentanaPopup('tutor_aula/vista_prev_informe_final.php?id_cargalectiva=<?= (int)$row['id_cargalectiva'] ?>&id_docente=<?= (int)$row['id_doce'] ?>');"
                   class="btn btn-primary">
                   Ver Informe
                </a>
              <?php else: ?>
                <span class="text-muted">No enviado</span>
              <?php endif; ?>
            </td>

            <td>
              <?php if (!empty($row['estado_revision'])): ?>
                <span class="btn <?= $row['estado_revision'] === 'CONFORME' ? 'btn-success' : 'btn-danger' ?>">
                  <?= htmlspecialchars($row['estado_revision']) ?>
                </span>
              <?php else: ?>
                <span style="color: #7f8c8d;">Pendiente</span>
              <?php endif; ?>
            </td>

            <td>
              <?= !empty($row['fecha_revision'])
                    ? date('d/m/Y H:i', strtotime($row['fecha_revision']))
                    : '--' ?>
            </td>

            <td style="text-align:center;">
              <?php if ((int)$row['estado_envio'] === 2 && empty($row['estado_revision'])): ?>
                <form method="POST"
                      action="direccion_escuela/direccion_guardar_revision_informe_final.php"
                      onsubmit="return confirm('¿Confirmar esta acción?')">

                  <input type="hidden" name="id_cargalectiva" value="<?= (int)$row['id_cargalectiva'] ?>">
                  <input type="hidden" name="id_director"    value="<?= (int)$id_director ?>">
                  <input type="hidden" name="mes_informe"    value="<?= (int)$row['mes_informe'] ?>">
                  <input type="hidden" name="id_semestre"    value="<?= (int)$semestre_sel ?>">

                  <button type="submit" name="accion" value="CONFORME" class="btn btn-success">
                    Conforme
                  </button>
                </form>
              <?php else: ?>
                --
              <?php endif; ?>
            </td>

          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="7" style="text-align:center;color:#c0392b;font-weight:bold;">
            No hay informes registrados.
          </td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function cambiarSemestre() {
  const semestre = document.getElementById('semestre').value;
  // esta vista también se carga vía index.php?pagina=...
  window.location.href =
    "/vista/index.php?pagina=direccion_escuela/direccion_informe_final_aula.php"
    + "&semestre=" + encodeURIComponent(semestre);
}

function abrirVentanaPopup2(url) {
  const w=900,h=700,l=(screen.width-w)/2,t=(screen.height-h)/2;
  window.open(url,'VentanaReporte',`width=${w},height=${h},top=${t},left=${l},resizable=yes,scrollbars=yes`);
  return false;
}

function abrirVentanaPopup(url) {
  const w=900,h=700,l=(screen.width-w)/2,t=(screen.height-h)/2;

  const popup = window.open(
    '',
    'VistaPrevTutoria',
    `width=${w},height=${h},top=${t},left=${l},resizable=yes,scrollbars=yes`
  );

  if (!popup || popup.closed || typeof popup.closed == 'undefined') {
    alert('Por favor, habilita los popups en tu navegador.');
    return false;
  }

  popup.document.write('<html><head><title>Cargando...</title></head><body><p style="font-family:sans-serif;">Cargando informe...</p></body></html>');
  popup.document.close();

  setTimeout(function () {
    popup.location.href = url;
  }, 150);

  return false;
}
</script>
