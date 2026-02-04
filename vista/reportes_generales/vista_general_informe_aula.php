<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('America/Lima');

if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], ['SUPERVISION', 'COORDINADOR GENERAL DE TUTORIA', 'VICEPRESIDENCIA ACADEMICA'])) {
    die('Acceso no autorizado');
}

require_once(__DIR__ . "/../../modelo/modelo_conexion.php");
$conexion = new conexion();
$conexion->conectar();

$mes_filtro = $_GET['mes'] ?? '';
$id_car_filtro = $_GET['id_car'] ?? '';

$escuelas = $conexion->conexion->query("SELECT DISTINCT id_car, nom_car FROM carrera ORDER BY nom_car");

$semestre_actual = (int)($_SESSION['S_SEMESTRE'] ?? 0);

// armamos una “pick” por (carga, mes) del semestre, priorizando ENVIADOS más recientes
$sql = "
SELECT 
    im.id_plan_tutoria,
    im.id_cargalectiva,
    cl.ciclo,
    cl.id_car,
    ca.nom_car,
    im.fecha_envio,
    rd.fecha_revision,
    im.mes_informe
FROM tutoria_informe_mensual im
JOIN carga_lectiva cl ON im.id_cargalectiva = cl.id_cargalectiva
JOIN carrera ca      ON cl.id_car = ca.id_car

/* unir solo la revisión CONFORME más reciente (si existe) para evitar multiplicar filas */
LEFT JOIN (
    SELECT id_plan_tutoria, mes_informe, MAX(fecha_revision) AS fecha_revision
    FROM tutoria_revision_director_informe
    WHERE estado_revision = 'CONFORME'
    GROUP BY id_plan_tutoria, mes_informe
) rd ON rd.id_plan_tutoria = im.id_plan_tutoria
     AND rd.mes_informe = im.mes_informe

WHERE im.id_semestre = ?
  /* aseguramos que 'im' sea EL informe escogido por (id_cargalectiva, mes_informe) */
  AND im.id_informe = (
      SELECT t2.id_informe
      FROM tutoria_informe_mensual t2
      WHERE t2.id_cargalectiva = im.id_cargalectiva
        AND t2.mes_informe   = im.mes_informe
        AND t2.id_semestre   = im.id_semestre
      ORDER BY (t2.estado_envio = 2) DESC, t2.fecha_envio DESC, t2.id_informe DESC
      LIMIT 1
  )
";

/* añadir filtros condicionales como antes: */
$types  = "i";
$params = [$semestre_actual];

if ($id_car_filtro !== '') {
    $sql    .= " AND cl.id_car = ? ";
    $types  .= "i";
    $params[] = (int)$id_car_filtro;
}
if ($mes_filtro !== '') {
    $sql    .= " AND im.mes_informe = ? ";
    $types  .= "s";
    $params[] = $mes_filtro;
}

$sql .= " ORDER BY ca.nom_car, cl.ciclo, im.id_cargalectiva, FIELD(im.mes_informe,'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre')";


$stmt = $conexion->conexion->prepare($sql);
if (!$stmt) { die("Error al preparar SQL: ".$conexion->conexion->error); }
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultado = $stmt->get_result();


function obtenerDocentes($conexion, int $id_carga, ?int $id_plan = null, ?int $id_semestre = null): array {
    $out = [];

    // 1) Docentes del plan (si viene id_plan)
    if (!empty($id_plan)) {
        $sql1 = "
            SELECT DISTINCT d.id_doce, d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce
            FROM tutoria_plan_compartido tpc
            JOIN docente d ON d.id_doce = tpc.id_docente
            WHERE tpc.id_plan_tutoria = ? AND tpc.id_cargalectiva = ?
            ORDER BY (tpc.estado_envio = 2) DESC, tpc.fecha_envio DESC, tpc.id_comp DESC
        ";
        if ($st1 = $conexion->conexion->prepare($sql1)) {
            $st1->bind_param("ii", $id_plan, $id_carga);
            $st1->execute();
            $r1 = $st1->get_result();
            while ($d = $r1->fetch_assoc()) { $out[] = $d; }
            $st1->close();
        }
    }

    // 2) Si no encontró, usar asignación por carga+semestre
    if (empty($out) && !empty($id_semestre)) {
        $sql2 = "
            SELECT DISTINCT d.id_doce, d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce
            FROM tutoria_asignacion_tutoria tat
            JOIN docente d ON d.id_doce = tat.id_docente
            WHERE tat.id_carga = ? AND tat.id_semestre = ? AND tat.tipo_asignacion_id IN (1,2)
            ORDER BY d.apepa_doce, d.apema_doce, d.nom_doce
        ";
        if ($st2 = $conexion->conexion->prepare($sql2)) {
            $st2->bind_param("ii", $id_carga, $id_semestre);
            $st2->execute();
            $r2 = $st2->get_result();
            while ($d = $r2->fetch_assoc()) { $out[] = $d; }
            $st2->close();
        }
    }

    return $out;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Informes - Tutores de aula </title>
<style>
body { background-color: #f0f2f5; font-family: Arial, sans-serif; }
.contenedor { background: white; margin: 40px auto; padding: 10px; border-radius: 10px; max-width: 1200px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
h3 {
    text-align: center;
    background-color: #154360;
    color: white;
    padding: 15px;
    border-radius: 5px;
    font-size: 20px;
    margin-bottom: 20px;
    }
.filtro { text-align: center; margin-bottom: 20px; }
select, button { padding: 6px 10px; font-size: 14px; margin-right: 10px; }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
th { background-color: #154360; color: white; }
.docentes-lista { text-align: left; font-size: 1.2rem; }
.docentes-lista ul { margin: 0; padding-left: 18px; }
.btn-ver { background-color: #2980b9; color: white; padding: 6px 10px; text-decoration: none; display: inline-block; border-radius: 4px; font-size: 13px; }
.overlay {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background-color: rgba(0,0,0,0.5);
  display: none; align-items: center; justify-content: center;
  z-index: 9999;
}
.alerta-central {
  background: #fff;
  padding: 20px 30px;
  border-radius: 8px;
  text-align: center;
  max-width: 400px; width: 90%;
  position: relative;
  box-shadow: 0 0 10px rgba(0,0,0,0.3);
  animation: fadeIn 0.3s ease;
}
.alerta-central strong {
  font-size: 16px; color: #e74c3c;
}
.alerta-central .cerrar {
  position: absolute; top: 8px; right: 12px;
  font-size: 18px; color: #999; cursor: pointer;
}
.alerta-central .cerrar:hover {
  color: #000;
}
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.9); }
  to { opacity: 1; transform: scale(1); }
}
    .tag-pendiente { background:#f9dede; color:#a33; padding:2px 6px; border-radius:4px; font-size:12px; font-weight:bold }
    .tag-conforme  { background:#def9e5; color:#117a3a; padding:2px 6px; border-radius:4px; font-size:12px; font-weight:bold }
</style>
</head>
<body>
<div class="contenedor" style="overflow-x:auto;">
<h3><strong> INFORMES MENSUALES - TUTORES DE AULA</strong></h3>

<div class="filtro">
<form method="GET" action="index.php">
<input type="hidden" name="pagina" value="reportes_generales/vista_general_informe_aula.php">
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
<select name="mes">
<option value="">-- Seleccione --</option>
<!-- ?php foreach (["abril","mayo","junio","julio","agosto"] as $mes): ?> -->
<?php foreach (["septiembre","octubre","noviembre","diciembre"] as $mes): ?> 
<option value="<?= $mes ?>" <?= $mes_filtro == $mes ? 'selected' : '' ?>><?= ucfirst($mes) ?></option>
<?php endforeach; ?>
</select>
<button type="submit">Filtrar</button>
</form>
</div>

<table>
<thead>
<tr>
<th>Escuela Profesional</th>
<th>Ciclo</th>  
<th>Tutor(a)</th>
<th>Fecha de Envío</th>
<th>Acción</th>
<th>Fecha de Conformidad</th>
</tr>
</thead>
<tbody>
<?php if ($resultado && $resultado instanceof mysqli_result && $resultado->num_rows > 0): ?>
<?php while ($row = $resultado->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['nom_car']) ?></td>
<td><?= htmlspecialchars($row['ciclo']) ?></td>
<td class="docentes-lista">
<?php
$docentes = obtenerDocentes($conexion, $row['id_cargalectiva'], $row['id_plan_tutoria'], $semestre_actual);

if ($docentes):
    echo "<ul>";
    foreach ($docentes as $d) {
        echo "<p><strong>{$d['abreviatura_doce']} {$d['apepa_doce']} {$d['apema_doce']}, {$d['nom_doce']}</strong></p>";
    }
    echo "</ul>";
else:
    echo "—";
endif;
?>
</td>
<td><?= $row['fecha_envio'] ?? '------' ?></td>
<td>
<form onsubmit="return abrirVentanaPopup(this);" method="GET" action="https://tutoria.undc.edu.pe/vista/tutor_aula/vista_prev_informe_mensual.php">
<input type="hidden" name="id_cargalectiva" value="<?= $row['id_cargalectiva'] ?>">
<input type="hidden" name="id_plan" value="<?= $row['id_plan_tutoria'] ?>">
<input type="hidden" name="mes" value="<?= $mes_filtro ?>">
<button type="submit" class="btn-ver">Ver informe</button>
</form>
</td>
<td ><?php $fr = $row['fecha_revision'] ?? ''; ?>
<?= $fr !== '' ? htmlspecialchars($fr) : 'Pendiente' ?></td>
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
    return false;
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
    'VistaPrevInformeMensual',
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
