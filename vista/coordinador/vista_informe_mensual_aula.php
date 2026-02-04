<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Lima');

if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], ['DIRECTOR DE DEPARTAMENTO ACADEMICO','COMITÉ - SUPERVISIÓN'])) {
    die('Acceso no autorizado');
}

require_once(__DIR__ . "/../../modelo/modelo_conexion.php");
$conexion = new conexion();
$conexion->conectar();

$id_car = $_SESSION['S_SCHOOL'] ?? null;

// Semestre actual de la sesión (ej. 32 ó 33)
$semestre_sesion = isset($_SESSION['S_SEMESTRE']) ? (int)$_SESSION['S_SEMESTRE'] : 32;

// 1) leo semestre del GET, si no viene uso el de sesión
$semestre_sel = isset($_GET['semestre']) && $_GET['semestre'] !== ''
    ? (int)$_GET['semestre']
    : $semestre_sesion;

// 2) meses por semestre (igual que Dirección)
$meses_por_semestre = [
    32 => ['abril','mayo','junio','julio'],
    33 => ['septiembre','octubre','noviembre','diciembre']
];

// si el semestre_sel no está en el mapa, forzamos a 32
if (!isset($meses_por_semestre[$semestre_sel])) {
    $semestre_sel = 32;
}

$meses_validos_del_semestre = $meses_por_semestre[$semestre_sel];

// 3) leo mes del GET
$mes_filtro = isset($_GET['mes']) && $_GET['mes'] !== ''
    ? strtolower($_GET['mes'])
    : '';

// default: último mes del semestre
if ($mes_filtro === '') {
    $mes_filtro = end($meses_validos_del_semestre);
    reset($meses_validos_del_semestre);
}

// si el usuario manda un mes que no corresponde al semestre, se corrige
if (!in_array($mes_filtro, $meses_validos_del_semestre, true)) {
    $mes_filtro = end($meses_validos_del_semestre);
    reset($meses_validos_del_semestre);
}

if (!$id_car) {
    echo "<div style='color:red; text-align:center; padding:20px; font-weight:bold;'> Error: La sesión no contiene ID de carrera.</div>";
    exit;
}

$sql = "
SELECT 
    im.id_informe,
    im.id_plan_tutoria,
    im.id_cargalectiva,
    im.id_docente,
    im.mes_informe,
    im.estado_envio,
    im.fecha_envio,
    cl.ciclo,
    cl.id_car,
    cl.id_semestre,
    d.abreviatura_doce,
    d.apepa_doce,
    d.apema_doce,
    d.nom_doce,

    (
      SELECT ri.estado_revision
      FROM tutoria_revision_director_informe ri
      WHERE ri.id_plan_tutoria = im.id_plan_tutoria
        AND LOWER(ri.mes_informe) = LOWER(im.mes_informe)
        AND ri.id_semestre = ?
        AND ri.id_car = ?
        AND (ri.id_cargalectiva = im.id_cargalectiva OR ri.id_cargalectiva IS NULL)
      ORDER BY ri.fecha_revision DESC
      LIMIT 1
    ) AS estado_revision,

    (
      SELECT ri.fecha_revision
      FROM tutoria_revision_director_informe ri
      WHERE ri.id_plan_tutoria = im.id_plan_tutoria
        AND LOWER(ri.mes_informe) = LOWER(im.mes_informe)
        AND ri.id_semestre = ?
        AND ri.id_car = ?
        AND (ri.id_cargalectiva = im.id_cargalectiva OR ri.id_cargalectiva IS NULL)
      ORDER BY ri.fecha_revision DESC
      LIMIT 1
    ) AS fecha_revision

FROM tutoria_informe_mensual im
JOIN carga_lectiva cl  ON im.id_cargalectiva = cl.id_cargalectiva
JOIN docente d         ON d.id_doce = im.id_docente
WHERE cl.id_car = ?
  AND cl.id_semestre = ?
  AND im.estado_envio = 2
  AND LOWER(im.mes_informe) = ?
ORDER BY cl.ciclo, d.apepa_doce
";


$stmt = $conexion->conexion->prepare($sql);
if (!$stmt) {
    die("Error en prepare: " . $conexion->conexion->error);
}

$stmt->bind_param(
  "iiiiiis",
  $semestre_sel, $id_car,   // subquery 1
  $semestre_sel, $id_car,   // subquery 2
  $id_car, $semestre_sel,   // WHERE
  $mes_filtro               // WHERE
);
$stmt->execute();
$resultado = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informes Mensuales</title>
    <style>
        body { background-color: #f8f9fc; font-family: Arial, sans-serif; }
        .card-custom { background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 1100px; margin: 50px auto; }
        table { width: 100%; text-align: center; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; vertical-align: middle; }
        th { background-color: #154360; color: white; }
        .btn-primary, .btn-success {
            background-color: #2980b9; border: none; color: white; padding: 6px 12px;
            cursor: pointer; border-radius: 4px; font-size: 13px;
        }
        .btn-success { background-color: #27ae60; }
        h3{ text-align:center;background: #154360;color:#fff;padding:15px;font-size:18px;font-weight:bold }
    </style>
</head>
<body>
    <div class="card-custom">
        <h3 class="text-center"><strong>INFORMES MENSUALES DE TUTORÍA</strong></h3>

        <!-- Filtros: Semestre + Mes (misma lógica que Director) -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap;">

          <div style="display:flex; gap:10px; align-items:center;">
            <label for="semestre"><strong>Semestre:</strong></label>
            <select id="semestre" name="semestre" onchange="aplicarFiltros()" style="padding:6px 10px; font-size:14px;">
              <option value="32" <?= ($semestre_sel == 32 ? 'selected' : '') ?>>2025-I</option>
              <option value="33" <?= ($semestre_sel == 33 ? 'selected' : '') ?>>2025-II</option>
            </select>

            <label for="mes"><strong>Mes:</strong></label>
            <select id="mes" name="mes" onchange="aplicarFiltros()" style="padding:6px 10px; font-size:14px;">
              <?php foreach ($meses_validos_del_semestre as $m): ?>
                <option value="<?= $m ?>" <?= ($mes_filtro === $m ? 'selected' : '') ?>>
                    <?= ucfirst($m) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <a style="float:right; margin-bottom:15px;"
             href="direccion_escuela/reporte_envios_informe.php"
             onclick="return abrirVentanaPopup2(this.href);"
             class="btn btn-success btn-sm">📋 VER REPORTE</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Ciclo</th>
                    <th>Docente(s) a cargo</th>
                    <th>Mes</th>
                    <th>Fecha de Envío</th>
                    <th>Acción</th>
                    <th>Fecha de Conformidad</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado->num_rows > 0): ?>
                    <?php while ($row = $resultado->fetch_assoc()): 
                        $id_cargalectiva_real = $row['id_cargalectiva'];
                        $semestre_row         = (int)$row['id_semestre'];
                        $mes_row              = strtolower($row['mes_informe']);
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($row['ciclo']) ?></td>
                            <td>
                                <?= "{$row['abreviatura_doce']} {$row['apepa_doce']} {$row['apema_doce']}, {$row['nom_doce']}" ?>
                            </td>
                            <td><?= ucfirst($row['mes_informe']) ?></td>
                            <td><?= !empty($row['fecha_envio']) ? date('d/m/Y H:i', strtotime($row['fecha_envio'])) : '—' ?></td>
                            <td>
                                <?php if ($row['estado_envio'] == 2): ?>
                                    <?php
                                      $urlPopup = "tutor_aula/vista_prev_informe_mensual.php"
                                        . "?id_plan_tutoria=" . $row['id_plan_tutoria']
                                        . "&id_cargalectiva=" . $id_cargalectiva_real
                                        . "&mes=" . $mes_row
                                        . "&id_docente=" . $row['id_docente']
                                        . "&id_semestre=" . $semestre_row;
                                    ?>
                                    <a href="#"
                                       onclick="return abrirVentanaPopup('<?= $urlPopup ?>');"
                                       class="btn btn-primary btn-sm">
                                        Ver Informe
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No enviado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['fecha_revision'])): ?>
                                <?= date('d/m/Y H:i', strtotime($row['fecha_revision'])) ?>
                                <?php else: ?>
                                <span style="color:#c0392b; font-weight:bold;">Conformidad pendiente</span>
                                <?php endif; ?>

                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">No hay informes conforme con los filtros seleccionados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<script>
function aplicarFiltros() {
  const semestre = document.getElementById('semestre').value;
  const mes = document.getElementById('mes').value;
  const base = "index.php?pagina=coordinador/vista_informe_mensual_aula.php";
  window.location.href = base + "&semestre=" + encodeURIComponent(semestre) + "&mes=" + encodeURIComponent(mes);
}

function abrirVentanaPopup(url) {
  const ancho = 900, alto = 700;
  const izquierda = (screen.width - ancho) / 2;
  const arriba = (screen.height - alto) / 2;
  const popup = window.open(url, 'VistaPrevInforme', `width=${ancho},height=${alto},top=${arriba},left=${izquierda},resizable=yes,scrollbars=yes`);
  if (!popup || popup.closed || typeof popup.closed == 'undefined') {
    alert('Por favor, habilita los popups en tu navegador.');
    return false;
  }
  return false;
}

function abrirVentanaPopup2(url) {
  const ancho = 900, alto = 700;
  const izquierda = (screen.width - ancho) / 2;
  const arriba = (screen.height - alto) / 2;
  window.open(url, 'VistaPrevTutoria', `width=${ancho},height=${alto},top=${arriba},left=${izquierda},resizable=yes,scrollbars=yes`);
  return false;
}
</script>

</body>
</html>
