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

$mes_filtro = $_GET['mes'] ?? '';

$sql = "
SELECT 
    imc.id_plan_tutoria,
    imc.id_cargalectiva,
    imc.mes_informe,
    cl.ciclo,
    cl.id_doce,
    imc.fecha_envio,
    imc.id_informe,
    imc.estado_envio,
    d.abreviatura_doce,
    d.apepa_doce,
    d.apema_doce,
    d.nom_doce,
    (
        SELECT estado_revision
        FROM tutoria_revision_director_informe ri
        WHERE ri.id_plan_tutoria = imc.id_plan_tutoria
          AND ri.id_cargalectiva = imc.id_cargalectiva
          AND ri.id_director = ?
          AND LOWER(ri.mes_informe) = LOWER(imc.mes_informe)
        ORDER BY ri.fecha_revision DESC
        LIMIT 1
    ) AS estado_revision,

    (
        SELECT fecha_revision
        FROM tutoria_revision_director_informe ri
        WHERE ri.id_plan_tutoria = imc.id_plan_tutoria
          AND ri.id_cargalectiva = imc.id_cargalectiva
          AND ri.id_director = ?
          AND LOWER(ri.mes_informe) = LOWER(imc.mes_informe)
        ORDER BY ri.fecha_revision DESC
        LIMIT 1
    ) AS fecha_revision
FROM tutoria_informe_mensual imc
INNER JOIN carga_lectiva cl ON cl.id_cargalectiva = imc.id_cargalectiva
INNER JOIN docente d ON d.id_doce = cl.id_doce
WHERE imc.estado_envio = 2
  AND cl.id_semestre = ?
  AND cl.id_car = ?
";
$params = [$id_director, $id_director, $semestre, $id_car_director];
$types = "iiii";

if (!empty($mes_filtro)) {
    $sql .= " AND LOWER(imc.mes_informe) = ?";
    $params[] = strtolower($mes_filtro);
    $types .= "s";
}

$sql .= " ORDER BY cl.ciclo ASC, imc.fecha_envio DESC";
if (!$stmt = $conexion->conexion->prepare($sql)) {
    die("❌ Error en prepare(): " . $conexion->conexion->error . "<br><pre>$sql</pre>");
}
$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

// Función auxiliar para obtener docentes del plan
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
    .btn-danger { background-color: #c0392b; color: white; }
    .btn-warning { background-color: #f39c12; color: white; }
    textarea {
      width: 100%;
      font-size: 13px;
    }
  @media (max-width: 768px) {
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
  td:nth-child(1)::before { content: "Ciclo"; }
  td:nth-child(2)::before { content: "Docente(s) a cargo"; }
  td:nth-child(3)::before { content: "Fecha de Envío"; }
  td:nth-child(4)::before { content: "Informe"; }
  td:nth-child(5)::before { content: "Estado"; }
  td:nth-child(6)::before { content: "Fecha de Conformidad"; }
  td:nth-child(7)::before { content: "Acciones"; }
  /*td:nth-child(7)::before { content: "Comentario"; }*/
  
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
/* Envuelve la tabla en un contenedor con scroll horizontal */
.table-responsive-custom {
  overflow-x: auto;
  width: 100%;
}

/* Opcional: mejora visual del scroll */
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
.table-responsive-custom {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch; /* para iOS */
}

.table-responsive-custom{
  min-width: 900px; /* Puedes ajustar a 1000px, 1100px si tienes muchas columnas */
  border-collapse: collapse;
}
</style>

<div class="container">
    <select name="mes" id="mes" class="form-control" style="max-width: 200px;" onchange="filtrarPorMes(this.value)">
    <option value="">-- Seleccione el mes --</option>
<!--     <option value="abril"  $mes_filtro == 'abril' ? 'selected' : '' ?>>Abril</option>
    <option value="mayo" $mes_filtro == 'mayo' ? 'selected' : '' ?>>Mayo</option>
    <option value="junio"  $mes_filtro == 'junio' ? 'selected' : '' ?>>Junio</option>
    <option value="julio"  $mes_filtro == 'julio' ? 'selected' : '' ?>>Julio</option> -->
    <option value="agosto" <?= $mes_filtro == 'agosto' ? 'selected' : '' ?>>Agosto</option>
    <option value="septiembre" <?= $mes_filtro == 'septiembre' ? 'selected' : '' ?>>Septiembre</option>
    <option value="octubre" <?= $mes_filtro == 'octubre' ? 'selected' : '' ?>>Octubre</option>
    <option value="noviembre" <?= $mes_filtro == 'noviembre' ? 'selected' : '' ?>>Noviembre</option>
    <option value="diciembre" <?= $mes_filtro == 'diciembre' ? 'selected' : '' ?>>Diciembre</option>
  </select>

  <script>
    function filtrarPorMes(mes) {
      window.location.href = "?pagina=direccion_escuela/direccion_informe_mensual_aula.php&mes=" + mes;
    }
  </script>
  
  <a style="float: right; margin-top:-25px;" href="direccion_escuela/reporte_envios_informe.php" onclick="return abrirVentanaPopup(this.href);" class="btn btn-success btn-sm">📋 VER REPORTE</a>
  <h3><strong>INFORMES MENSUALES - TUTORES DE AULA</strong></h3>
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
      <!--BOTOM INCONFORME-->
      <!--<th>Comentario</th>-->
    </tr>
  </thead>
    <tbody>
     <?php
      $filas = $res->fetch_all(MYSQLI_ASSOC);
      ?>

      <?php if (count($filas) > 0): ?>
        <?php foreach ($filas as $row): ?>
          <!-- FILAS DE LA TABLA -->
          <tr>
            <td><?= htmlspecialchars($row['ciclo']) ?></td>
            <td class="docentes-a-cargo">
                <ul><?= $row['abreviatura_doce'] . ' ' . $row['apepa_doce'] . ' ' . $row['apema_doce'] . ', ' . $row['nom_doce'] ?></ul>
            </td>
            <td>
              <?= !empty($row['fecha_envio']) ? date('d/m/Y H:i', strtotime($row['fecha_envio'])) : '<span class="text-muted">--</span>' ?>
            </td>
            <td>
              <?php if ($row['estado_envio'] == 2): ?>
                <?php
                  $mesInforme = strtolower(date('F', strtotime($row['fecha_envio'])));
                  $mesMap = ['april' => '04', 'may' => '05', 'june' => '06', 'july' => '07'];
                  $mesFinal = $mesMap[$mesInforme] ?? '05';
                ?>
                 <a href="#" onclick="abrirVentanaPopup('tutor_aula/vista_prev_informe_mensual.php?id_plan_tutoria=<?= $row['id_plan_tutoria'] ?>&id_cargalectiva=<?= $row['id_informe'] ? obtenerCargalectivaDesdeInforme($conexion, $row['id_informe']) : 0 ?>&mes=<?= $row['mes_informe'] ?>&id_docente=<?= $row['id_doce'] ?>'); return false;" class="btn btn-primary">Ver Informe</a>
              <?php else: ?>
                <span class="text-muted">No enviado</span>
              <?php endif; ?>
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
              <?= !empty($row['fecha_revision']) ? date('d/m/Y H:i', strtotime($row['fecha_revision'])) : '--' ?>
            </td>
            <td>
              <?php if ($row['estado_envio'] == 2 && !$row['estado_revision']): ?>
                <form method="POST" action="direccion_escuela/direccion_guardar_revision_informe.php" onsubmit="return confirm('¿Confirmar esta acción?')">
                  <input type="hidden" name="id_plan_tutoria" value="<?= $row['id_plan_tutoria'] ?>">
                  <input type="hidden" name="id_cargalectiva" value="<?= $row['id_cargalectiva'] ?>">
                  <input type="hidden" name="id_director" value="<?= $id_director ?>">
                  <input type="hidden" name="mes_informe" value="<?= $row['mes_informe'] ?>">
                  <button type="submit" name="accion" value="CONFORME" class="btn btn-success">Conforme</button>
                  <!-- <button type="submit" name="accion" value="CONFORME" class="btn btn-success" disabled>Conforme</button>-->
                  <!--BOTOM INCONFORME-->
                  <!--<button type="button" class="btn btn-danger" onclick="habilitarComentario(this)">Inconforme</button>-->
                </form>
              <?php else: ?>
                --
              <?php endif; ?>
            </td>
            <!--COMENTARIO-->
            <!--<td>-->
              <?php /* if (!empty($row['comentario'])): ?>
                <?= htmlspecialchars($row['comentario']) ?>
              <?php elseif ($row['estado_envio'] == 2 && !$row['estado_revision']): ?>
                <form method="POST" action="direccion_escuela/direccion_guardar_revision.php">
                  <input type="hidden" name="id_plan_tutoria" value="<?=$row['id_plan_tutoria'] ?>">
                  <input type="hidden" name="id_director" value="<?= $id_director ?>">
                  <textarea name="comentario" rows="2" placeholder="Comentario (si es inconforme)" disabled></textarea>
                  <button name="accion" value="INCONFORME" class="btn btn-warning mt-1" disabled>Enviar Inconforme</button>
                </form>
              <?php else: ?>
              <?php endif; */?>
            <!--</td>-->
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
       <tr>
            <td colspan="7" style="text-align: center; color: #c0392b; font-weight: bold;">
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

<script>

function abrirVentanaPopup(url) {
  const ancho = 900;
  const alto = 700;
  const izquierda = (screen.width - ancho) / 2;
  const arriba = (screen.height - alto) / 2;

  const popup = window.open('', 'VistaPrevTutoria', `width=${ancho},height=${alto},top=${arriba},left=${izquierda},resizable=yes,scrollbars=yes`);

  // Evitar popup bloqueado
  if (!popup || popup.closed || typeof popup.closed == 'undefined') {
    alert('Por favor, habilita los popups en tu navegador.');
    return false;
  }

  // Establece una pantalla de carga temporal
  popup.document.write('<html><head><title>Cargando...</title></head><body><p style="font-family:sans-serif;">Cargando informe...</p></body></html>');
  popup.document.close();

  // Redirige luego de 150ms para asegurar apertura
  setTimeout(function () {
    popup.location.href = url;
  }, 150);

  return false;
}

// para leer el mes seleccionado (si quieres pasarlo al reporte)
function abrirReporte() {
  const sel = document.getElementById('mes');
  const mes = sel && sel.value ? `?mes=${encodeURIComponent(sel.value)}` : '';
  const url = `direccion_escuela/reporte_envios_informe.php${mes}`;
  return abrirPopup(url, 'ReporteEnvios', 1000, 720);
}
function habilitarComentario(btn) {
  const form = btn.closest('form').nextElementSibling;
  if (form) {
    form.querySelector('textarea[name="comentario"]').disabled = false;
    form.querySelector('button[name="accion"]').disabled = false;
  }
}
<?php
function obtenerCargalectivaDesdeInforme($conexion, $id_informe) {
    $sql = "SELECT id_cargalectiva FROM tutoria_informe_mensual WHERE id_informe = ?";
    $stmt = $conexion->conexion->prepare($sql);
    $stmt->bind_param("i", $id_informe);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['id_cargalectiva'] ?? 0;
}
?>
</script>
