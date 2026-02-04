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

$id_director     = (int)$_SESSION['S_IDUSUARIO'];
$id_car_director = (int)$_SESSION['S_SCHOOL'];

// Semestre actual de la sesión (ej. 32 ó 33)
$semestre_sesion = isset($_SESSION['S_SEMESTRE']) ? (int)$_SESSION['S_SEMESTRE'] : 32;

// 1) leo semestre del GET, si no viene uso el de sesión
$semestre_sel = isset($_GET['semestre']) && $_GET['semestre'] !== ''
    ? (int)$_GET['semestre']
    : $semestre_sesion;

// 2) definimos qué meses pertenecen a cada semestre
$meses_por_semestre = [
    32 => ['abril','mayo','junio','julio'],
    33 => ['septiembre','octubre','noviembre','diciembre']
];

/*   $id_cargalectiva_real = $row['id_cargalectiva'];
  $semestre_row         = $row['id_semestre']; // viene del SELECT
  $mes_row              = strtolower($row['mes_informe']); */
// si el semestre_sel no está en el mapa, forzamos a 32 para no romper
if (!isset($meses_por_semestre[$semestre_sel])) {
    $semestre_sel = 32;
}

$meses_validos_del_semestre = $meses_por_semestre[$semestre_sel];

// 3) leo mes del GET
$mes_filtro = isset($_GET['mes']) && $_GET['mes'] !== ''
    ? strtolower($_GET['mes'])
    : '';

// si NO mandaron mes, proponemos un default "bonito":
// - si el semestre es el actual y hay un mes "reciente", podrías elegir el último mes del arreglo
// - aquí usaremos el último mes de ese semestre como default visual
if ($mes_filtro === '') {
    // último mes definido para ese semestre
    $mes_filtro = end($meses_validos_del_semestre);
    // reset pointer del array porque usamos end()
    reset($meses_validos_del_semestre);
}

// si el usuario mete un mes que NO corresponde al semestre seleccionado, lo ignoramos y usamos el último válido
if (!in_array($mes_filtro, $meses_validos_del_semestre, true)) {
    $mes_filtro = end($meses_validos_del_semestre);
    reset($meses_validos_del_semestre);
}

$conexion = new conexion();
$conexion->conectar();

/*
   Consulta:
   - Solo informes enviados (estado_envio=2)
   - Mis docentes (misma carrera)
   - Ese semestre
   - Ese mes
*/
$sql = "
SELECT 
    imc.id_plan_tutoria,
    imc.id_cargalectiva,
    imc.mes_informe,
    cl.ciclo,
    cl.id_doce,
    cl.id_semestre, 
    imc.fecha_envio,
    imc.id_informe,
    imc.estado_envio,
    d.abreviatura_doce,
    d.apepa_doce,
    d.apema_doce,
    d.nom_doce,

    (
        SELECT ri.estado_revision
        FROM tutoria_revision_director_informe ri
        WHERE ri.id_plan_tutoria = imc.id_plan_tutoria
          AND LOWER(ri.mes_informe) = LOWER(imc.mes_informe)
          AND ri.id_semestre = ?
          AND ri.id_car = ?
          AND (ri.id_cargalectiva = imc.id_cargalectiva OR ri.id_cargalectiva IS NULL)
        ORDER BY ri.fecha_revision DESC
        LIMIT 1
    ) AS estado_revision,

    (
        SELECT ri.fecha_revision
        FROM tutoria_revision_director_informe ri
        WHERE ri.id_plan_tutoria = imc.id_plan_tutoria
          AND LOWER(ri.mes_informe) = LOWER(imc.mes_informe)
          AND ri.id_semestre = ?
          AND ri.id_car = ?
          AND (ri.id_cargalectiva = imc.id_cargalectiva OR ri.id_cargalectiva IS NULL)
        ORDER BY ri.fecha_revision DESC
        LIMIT 1
    ) AS fecha_revision

FROM tutoria_informe_mensual imc
INNER JOIN carga_lectiva cl ON cl.id_cargalectiva = imc.id_cargalectiva
INNER JOIN docente d ON d.id_doce = cl.id_doce
WHERE imc.estado_envio = 2
  AND cl.id_semestre = ?
  AND cl.id_car = ?
  AND LOWER(imc.mes_informe) = ?
ORDER BY cl.ciclo ASC, imc.fecha_envio DESC
";


if (!$stmt = $conexion->conexion->prepare($sql)) {
    die("❌ Error en prepare(): " . $conexion->conexion->error . "<br><pre>$sql</pre>");
}

$mes_filtro = strtolower($mes_filtro);

$stmt->bind_param(
    "iiiiiis",
    $semestre_sel, $id_car_director,  // subquery estado_revision
    $semestre_sel, $id_car_director,  // subquery fecha_revision
    $semestre_sel, $id_car_director,  // WHERE cl.id_semestre, cl.id_car
    $mes_filtro                        // WHERE mes
);

$stmt->execute();
$res = $stmt->get_result();

// helper para informe
function obtenerCargalectivaDesdeInforme($conexion, $id_informe) {
    $sql = "SELECT id_cargalectiva FROM tutoria_informe_mensual WHERE id_informe = ?";
    $stmt2 = $conexion->conexion->prepare($sql);
    $stmt2->bind_param("i", $id_informe);
    $stmt2->execute();
    $r = $stmt2->get_result()->fetch_assoc();
    return $r['id_cargalectiva'] ?? 0;
}
?>


<style>
    body { font-family: Arial, sans-serif;  background-color: #ecf0f1;  margin: 0;  padding: 0; }
    .container {  max-width: 98%;  margin: 40px auto;  padding: 20px;  background-color: white; border-radius: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h3 {  text-align: center;  background-color: #154360;  color: white; padding: 15px;  border-radius: 5px;  font-size: 20px;  margin-bottom: 20px;}
    table { width: 100%; border-collapse: collapse;  font-size: 14px; }
    th, td {  padding: 10px;  border: 1px solid #ccc;  text-align: center;}
    th { background-color: #154360;  color: white;  }
    .btn { padding: 6px 10px; border: none;  border-radius: 4px;  cursor: pointer;  font-size: 13px; }
    .btn-primary { background-color: #2980b9; color: white; }
    .btn-success { background-color: #27ae60; color: white; }
    .btn-danger { background-color: #c0392b; color: white; }
    .btn-warning { background-color: #f39c12; color: white; }
    textarea {  width: 100%;  font-size: 13px; }
  @media (max-width: 768px) {
  table, thead, tbody, th, td, tr {
    display: block;
  }
  thead {
    display: none;
  }
  tr {  margin-bottom: 15px;  background: #fff;  padding: 10px;  border-radius: 10px;  box-shadow: 0 0 5px #ccc;}
  td { text-align: left !important; padding-left: 45%; position: relative; border: none; border-bottom: 1px solid #eee;
  }
  td::before { position: absolute; top: 10px; left: 10px; width: 35%; font-weight: bold; white-space: nowrap;
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
<style>
/* (tu mismo CSS que ya tienes, lo dejamos igual) */
</style>

<div class="container">
  <!-- Filtros en la misma línea -->
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap;">

    <div style="display:flex; gap:10px; align-items:center;">
      <!-- Filtro: Semestre -->
      <label for="semestre" style="font-weight:bold;">Semestre:</label>
      <select id="semestre" name="semestre" class="form-control" style="max-width:180px;"
              onchange="aplicarFiltros()">
        <option value="32" <?= ($semestre_sel == 32 ? 'selected' : '') ?>>2025-I</option>
        <option value="33" <?= ($semestre_sel == 33 ? 'selected' : '') ?>>2025-II</option>
      </select>

      <!-- Filtro: Mes -->
      <label for="mes" style="font-weight:bold;">Mes:</label>
      <select id="mes" name="mes" class="form-control" style="max-width:180px;"
              onchange="aplicarFiltros()">
        <?php foreach ($meses_validos_del_semestre as $m): ?>
          <option value="<?= $m ?>" <?= ($mes_filtro === $m ? 'selected' : '') ?>>
            <?= ucfirst($m) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Botón de reporte -->
    <a href="direccion_escuela/reporte_envios_informe.php"
       onclick="return abrirVentanaPopup(this.href);"
       class="btn btn-success btn-sm">
       📋 Ver Reporte
    </a>
  </div>

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
        </tr>
      </thead>
      <tbody>
      <?php
        $filas = $res->fetch_all(MYSQLI_ASSOC);
        if (count($filas) > 0):
          foreach ($filas as $row):

            // 👇 AQUÍ va el bloque que me preguntaste
            $id_cargalectiva_real = $row['id_cargalectiva'];
            $semestre_row         = $row['id_semestre']; // viene del SELECT (ya lo agregaste en el SELECT)
            $mes_row              = strtolower($row['mes_informe']);
        ?>
        <tr>
          <td><?= htmlspecialchars($row['ciclo']) ?></td>
          <td class="docentes-a-cargo">
            <ul><?= $row['abreviatura_doce'].' '.$row['apepa_doce'].' '.$row['apema_doce'].', '.$row['nom_doce'] ?></ul>
          </td>
          <td><?= !empty($row['fecha_envio']) ? date('d/m/Y H:i', strtotime($row['fecha_envio'])) : '<span class="text-muted">--</span>' ?></td>
          <td>
            <?php if ($row['estado_envio'] == 2): ?>
              <?php
                $urlPopup = "tutor_aula/vista_prev_informe_mensual.php"
                  . "?id_plan_tutoria=" . $row['id_plan_tutoria']
                  . "&id_cargalectiva=" . $id_cargalectiva_real
                  . "&mes=" . $mes_row
                  . "&id_docente=" . $row['id_doce']
                  . "&id_semestre=" . $semestre_row;
              ?>
              <a href="#"
                onclick="return abrirVentanaPopup('<?= $urlPopup ?>');"
                class="btn btn-primary">
                Ver Informe
              </a>
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
              <span style="color:#7f8c8d;">Pendiente</span>
            <?php endif; ?>
          </td>
          <td><?= !empty($row['fecha_revision']) ? date('d/m/Y H:i', strtotime($row['fecha_revision'])) : '--' ?></td>
          <td>
            <?php if ($row['estado_envio'] == 2 && !$row['estado_revision']): ?>
              <form method="POST" action="direccion_escuela/direccion_guardar_revision_informe.php" onsubmit="return confirm('¿Confirmar esta acción?')">
                <input type="hidden" name="id_plan_tutoria" value="<?= $row['id_plan_tutoria'] ?>">
                <input type="hidden" name="id_cargalectiva" value="<?= $row['id_cargalectiva'] ?>">
                <input type="hidden" name="id_director" value="<?= $id_director ?>">
                <input type="hidden" name="mes_informe" value="<?= $row['mes_informe'] ?>">
                <button type="submit" name="accion" value="CONFORME" class="btn btn-success">Conforme</button>
              </form>
            <?php else: ?>
              --
            <?php endif; ?>
          </td>
        </tr>
      <?php
          endforeach;
        else:
      ?>
        <tr>
          <td colspan="7" style="text-align:center; color:#c0392b; font-weight:bold;">
            No hay informes registrados para el semestre y mes seleccionados.
          </td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function aplicarFiltros() {
  const semestre = document.getElementById('semestre').value;
  const mes = document.getElementById('mes').value;
  const base = "?pagina=direccion_escuela/direccion_informe_mensual_aula.php";
  const qs = "&semestre=" + encodeURIComponent(semestre) + "&mes=" + encodeURIComponent(mes);
  window.location.href = base + qs;
}

function abrirVentanaPopup(url) {
  const ancho = 900, alto = 700;
  const izquierda = (screen.width - ancho) / 2;
  const arriba = (screen.height - alto) / 2;
  const popup = window.open(url, 'VistaPrevTutoria', `width=${ancho},height=${alto},top=${arriba},left=${izquierda},resizable=yes,scrollbars=yes`);
  if (!popup || popup.closed || typeof popup.closed == 'undefined') {
    alert('Por favor, habilita los popups en tu navegador.');
    return false;
  }
  return false;
}
</script>
