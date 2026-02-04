<?php  
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('America/Lima');

if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], ['DIRECTOR DE DEPARTAMENTO ACADEMICO', 'COMITÉ - SUPERVISIÓN'])) {
    die('Acceso no autorizado');
}

require_once(__DIR__ . "/../../modelo/modelo_conexion.php");
$conexion = new conexion();
$conexion->conectar();

/* ===== SESIÓN ===== */
$id_car = isset($_SESSION['S_SCHOOL']) ? (int)$_SESSION['S_SCHOOL'] : 0;
$semestre_sesion = isset($_SESSION['S_SEMESTRE']) ? (int)$_SESSION['S_SEMESTRE'] : 32;

if ($id_car <= 0) {
    echo "<div style='color:red; text-align:center; padding:20px; font-weight:bold;'>
            Error: La sesión no contiene ID de carrera (S_SCHOOL).
          </div>";
    exit;
}

/* ===== SEMESTRE (GET o sesión) ===== */
$semestre_sel = (isset($_GET['semestre']) && $_GET['semestre'] !== '')
    ? (int)$_GET['semestre']
    : $semestre_sesion;

/* ===== QUERY (replica lógica de director, pero SIN acciones de conformidad) ===== */
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
          AND ri.id_semestre = ?
        ORDER BY ri.fecha_revision DESC
        LIMIT 1
    ) AS estado_revision,

    (
        SELECT fecha_revision
        FROM tutoria_revision_director_informe_final ri
        WHERE ri.id_cargalectiva = ia.id_cargalectiva
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

if (!$stmt = $conexion->conexion->prepare($sql)) {
    die("❌ Error en prepare(): " . $conexion->conexion->error . "<br><pre>$sql</pre>");
}

$stmt->bind_param("iiii", $semestre_sel, $semestre_sel, $semestre_sel, $id_car);
$stmt->execute();
$res = $stmt->get_result();
$filas = $res->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Informes Finales</title>
  <style>
    body { background-color: #f8f9fc; font-family: Arial, sans-serif; }
    .card-custom { background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 1100px; margin: 50px auto; }
    table { width: 100%; text-align: center; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 10px; vertical-align: middle; }
    th { background-color: #154360; color: white; }
    .btn-primary, .btn-success {
      background-color: #2980b9; border: none; color: white; padding: 6px 12px;
      cursor: pointer; border-radius: 4px; font-size: 13px; text-decoration:none;
    }
    .btn-success { background-color: #27ae60; }
    .text-muted { color: #7f8c8d; }
    h3{ text-align:center;background:#154360;color:#fff;padding:15px;font-size:18px;font-weight:bold }
  </style>
</head>
<body>

<div class="card-custom">

  <!-- CONTROLES: Semestre + Reporte -->
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap;">
    <div style="display:flex; gap:10px; align-items:center;">
      <label for="semestre" style="font-weight:bold;">Semestre:</label>
      <select id="semestre" name="semestre" style="padding:6px 10px; font-size:14px;" onchange="cambiarSemestre()">
        <option value="32" <?= ($semestre_sel == 32 ? 'selected' : '') ?>>2025-I</option>
        <option value="33" <?= ($semestre_sel == 33 ? 'selected' : '') ?>>2025-II</option>
      </select>
    </div>

    <a href="direccion_escuela/reporte_envios_informe_final.php"
       onclick="return abrirVentanaPopupReporte(this.href);"
       class="btn-success btn-sm"
       style="white-space:nowrap;">
       📋 VER REPORTE
    </a>
  </div>

  <h3 class="text-center"><strong>INFORMES FINALES DE TUTORÍA</strong></h3>

  <table>
    <thead>
      <tr>
        <th>Ciclo</th>
        <th>Docente</th>
        <th>Fecha de Envío</th>
        <th>Acción</th>
        <th>Estado</th>
        <th>Fecha de Conformidad</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($filas) > 0): ?>
        <?php foreach ($filas as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['ciclo']) ?></td>
            <td><?= "{$row['abreviatura_doce']} {$row['apepa_doce']} {$row['apema_doce']}, {$row['nom_doce']}" ?></td>

            <td>
              <?= !empty($row['fecha_presentacion'])
                    ? date('d/m/Y', strtotime($row['fecha_presentacion']))
                    : '—' ?>
            </td>

            <td>
              <?php if ((int)$row['estado_envio'] === 2): ?>
                <a href="#"
                   onclick="return abrirVentanaPopupInforme('tutor_aula/vista_prev_informe_final.php?id_cargalectiva=<?= (int)$row['id_cargalectiva'] ?>&id_docente=<?= (int)$row['id_doce'] ?>');"
                   class="btn-primary">Ver Informe</a>
              <?php else: ?>
                <span class="text-muted">No enviado</span>
              <?php endif; ?>
            </td>

            <td>
              <?php if (!empty($row['estado_revision'])): ?>
                <span style="font-weight:bold; color:<?= ($row['estado_revision'] === 'CONFORME' ? '#27ae60' : '#c0392b') ?>;">
                  <?= htmlspecialchars($row['estado_revision']) ?>
                </span>
              <?php else: ?>
                <span class="text-muted">Pendiente</span>
              <?php endif; ?>
            </td>

            <td>
              <?php if (!empty($row['fecha_revision'])): ?>
                <?= date('d/m/Y H:i', strtotime($row['fecha_revision'])) ?>
              <?php else: ?>
                <span class="text-muted">Pendiente</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="6">No hay informes finales registrados para el semestre seleccionado.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

</div>

<script>
function cambiarSemestre() {
  const semestre = document.getElementById('semestre').value;
  // ajusta la ruta si tu página es otra
  window.location.href = "index.php?pagina=coordinador/vista_informe_final_aula.php&semestre=" + encodeURIComponent(semestre);
}

function abrirVentanaPopupReporte(url) {
  const w=900,h=700,l=(screen.width-w)/2,t=(screen.height-h)/2;
  window.open(url,'VentanaReporte',`width=${w},height=${h},top=${t},left=${l},resizable=yes,scrollbars=yes`);
  return false;
}

function abrirVentanaPopupInforme(url) {
  const w=900,h=700,l=(screen.width-w)/2,t=(screen.height-h)/2;
  const popup = window.open('', 'VistaInformeFinal', `width=${w},height=${h},top=${t},left=${l},resizable=yes,scrollbars=yes`);
  if (!popup || popup.closed || typeof popup.closed == 'undefined') {
    alert('Por favor, habilita los popups en tu navegador.');
    return false;
  }
  popup.document.write('<html><head><title>Cargando...</title></head><body><p style="font-family:sans-serif;">Cargando informe...</p></body></html>');
  popup.document.close();
  setTimeout(function(){ popup.location.href = url; }, 150);
  return false;
}
</script>

</body>
</html>
