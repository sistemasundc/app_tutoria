<?php  
if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('America/Lima');

if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], ['SUPERVISION', 'COORDINADOR GENERAL DE TUTORIA', 'VICEPRESIDENCIA ACADEMICA'])) {
    die('Acceso no autorizado');
}

require_once(__DIR__ . "/../../modelo/modelo_conexion.php");
$conexion = new conexion();
$conexion->conectar();

$id_car_filtro = $_GET['id_car'] ?? '';

$escuelas = $conexion->conexion->query("SELECT DISTINCT id_car, nom_car FROM carrera ORDER BY nom_car");

$semestre_actual = (int)($_SESSION['S_SEMESTRE'] ?? 0);

$sql = "
SELECT 
    ia.id_informe_final,
    ia.id_doce,
    ia.id_cargalectiva,
    cl.ciclo,
    cl.id_car,
    ca.nom_car,
    ia.fecha_presentacion,
    rev.fecha_revision,
    d.abreviatura_doce,
    d.apepa_doce,
    d.apema_doce,
    d.nom_doce
FROM tutoria_informe_final_aula ia
JOIN (
    /* 1 fila por (carga, docente) del semestre, priorizando el enviado más reciente */
    SELECT 
        iax.id_cargalectiva,
        iax.id_doce,
        MAX(CONCAT(
            IFNULL(DATE_FORMAT(iax.fecha_presentacion,'%Y%m%d%H%i%s'),'00000000000000'),
            '-', LPAD(iax.id_informe_final,10,'0')
        )) AS pick_key
    FROM tutoria_informe_final_aula iax
    WHERE iax.semestre_id = ?       
      AND iax.estado_envio = 2       -- solo enviados
    GROUP BY iax.id_cargalectiva, iax.id_doce
) pk ON pk.id_cargalectiva = ia.id_cargalectiva
    AND pk.id_doce        = ia.id_doce
    AND CONCAT(
        IFNULL(DATE_FORMAT(ia.fecha_presentacion,'%Y%m%d%H%i%s'),'00000000000000'),
        '-', LPAD(ia.id_informe_final,10,'0')
    ) = pk.pick_key

LEFT JOIN tutoria_revision_director_informe_final rev 
       ON rev.id_cargalectiva = ia.id_cargalectiva
JOIN carga_lectiva cl ON ia.id_cargalectiva = cl.id_cargalectiva
JOIN carrera ca       ON cl.id_car = ca.id_car
JOIN docente d        ON d.id_doce = ia.id_doce   -- <<< AQUÍ EL JOIN >>>

WHERE ia.semestre_id = ?
  AND ia.estado_envio = 2
";

$types  = "ii";
$params = [$semestre_actual, $semestre_actual];

if ($id_car_filtro !== '') {
    $sql    .= " AND cl.id_car = ? ";
    $types  .= "i";
    $params[] = (int)$id_car_filtro;
}

$sql .= " ORDER BY ca.nom_car, cl.ciclo, ia.id_cargalectiva";

$stmt = $conexion->conexion->prepare($sql);
if (!$stmt) { die("Error al preparar SQL: ".$conexion->conexion->error); }
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultado = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Informes Finales Conformes</title>
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
</style>
</head>
<body>
<div class="contenedor" style="overflow-x:auto;">
<h3><strong> INFORMES FINALES - TUTORES DE AULA</strong></h3>

<div class="filtro">
<form method="GET" action="index.php">
<input type="hidden" name="pagina" value="reportes_generales/vista_general_informe_final_aula.php">
<label>Escuela Profesional:</label>
<select name="id_car">
<option value="">-- Todas --</option>
<?php $escuelas->data_seek(0); while ($e = $escuelas->fetch_assoc()): ?>
<option value="<?= $e['id_car'] ?>" <?= $id_car_filtro == $e['id_car'] ? 'selected' : '' ?>>
<?= htmlspecialchars($e['nom_car']) ?>
</option>
<?php endwhile; ?>
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
  <strong>
    <?= htmlspecialchars(($row['abreviatura_doce'] ?? '').' '.($row['apepa_doce'] ?? '').' '.($row['apema_doce'] ?? '').', '.($row['nom_doce'] ?? '')) ?>
  </strong>
</td>
<td><?= $row['fecha_presentacion'] ?? '—' ?></td>
<td>
<form onsubmit="return abrirVentanaPopup(this);" method="GET" action="https://tutoria.undc.edu.pe/vista/tutor_aula/vista_prev_informe_final.php">
<input type="hidden" name="id_cargalectiva" value="<?= $row['id_cargalectiva'] ?>">
<input type="hidden" name="id_docente" value="<?= $row['id_doce'] ?>">
<button type="submit" class="btn-ver">Ver informe</button>
</form>
</td>
<td>
<?php if (!empty($row['fecha_revision'])): ?>
    <?= $row['fecha_revision'] ?>
<?php else: ?>
    <span style="background-color: #e2807dff; padding: 5px; border-radius: 5px; color: #fffdfdff; font-weight: bold;">Pendiente</span>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="6">No hay informes finales conformes registrados.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<script>
function abrirVentanaPopup(form) {
  const ancho = 900;
  const alto = 700;
  const izquierda = (screen.width - ancho) / 2;
  const arriba = (screen.height - alto) / 2;

  const url = new URL(form.action);
  const params = new URLSearchParams(new FormData(form));
  url.search = params.toString();

  window.open(
    url.toString(),
    'VistaPrevInformeFinal',
    `width=${ancho},height=${alto},top=${arriba},left=${izquierda},resizable=yes,scrollbars=yes`
  );

  return false;
}
</script>
</body>
</html>
