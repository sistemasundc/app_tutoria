<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
date_default_timezone_set('America/Lima');

if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], ['SUPERVISION','COORDINADOR GENERAL DE TUTORIA','VICEPRESIDENCIA ACADEMICA'])) {
    die('Acceso no autorizado');
}

require_once(__DIR__ . "/../../modelo/modelo_conexion.php");
$conexion = new conexion();
$cn = $conexion->conectar();  
if (!$cn) { die('No se pudo conectar a la BD'); }

$semestre_actual = (int)($_SESSION['S_SEMESTRE'] ?? 0);

// Listado para el combo
$escuelas = $cn->query("SELECT DISTINCT id_car, nom_car FROM carrera ORDER BY nom_car");

// Filtros
$id_car_filtro = isset($_GET['id_car']) && $_GET['id_car'] !== '' ? (int)$_GET['id_car'] : null;

/* 1 fila por id_cargalectiva en el semestre actual.
   Prioriza ENVIADO (estado_envio=2) y toma el más reciente; si no hay, último GUARDADO. */
$sql = "
WITH pick AS (
  SELECT
    tpc1.id_cargalectiva,
    (
      SELECT tpc2.id_comp
      FROM tutoria_plan_compartido tpc2
      WHERE tpc2.id_cargalectiva = tpc1.id_cargalectiva
        AND tpc2.id_semestre     = ?
      ORDER BY (tpc2.estado_envio = 2) DESC,
               tpc2.fecha_envio DESC,
               tpc2.id_comp DESC
      LIMIT 1
    ) AS id_comp_elegido
  FROM tutoria_plan_compartido tpc1
  WHERE tpc1.id_semestre = ?
  GROUP BY tpc1.id_cargalectiva
)
SELECT
  c.id_car, c.nom_car,
  cl.ciclo, cl.id_cargalectiva,
  d.id_doce, d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce,
  tpc.id_plan_tutoria,
  tpc.estado_envio,
  tpc.fecha_envio,
  rd.estado_revision,
  rd.fecha_revision
FROM pick
JOIN tutoria_plan_compartido tpc ON tpc.id_comp = pick.id_comp_elegido
JOIN carga_lectiva cl            ON cl.id_cargalectiva = tpc.id_cargalectiva
JOIN carrera c                   ON c.id_car          = tpc.id_car
JOIN docente d                   ON d.id_doce         = tpc.id_docente
LEFT JOIN tutoria_revision_director rd
       ON rd.id_plan_tutoria = tpc.id_plan_tutoria
      AND rd.id_cargalectiva = tpc.id_cargalectiva
WHERE tpc.id_semestre = ?
";

$params = [$semestre_actual, $semestre_actual, $semestre_actual];
$types  = "iii";

if (!is_null($id_car_filtro)) {
    $sql .= " AND tpc.id_car = ? ";
    $params[] = $id_car_filtro;
    $types   .= "i";
}

$sql .= " ORDER BY c.nom_car, cl.ciclo, d.apepa_doce, d.apema_doce, d.nom_doce";

$stmt = $cn->prepare($sql);
if (!$stmt) { die("Error al preparar SQL: ".$cn->error); }
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Planes de Tutoría – Tutores de Aula</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    body { background:#f0f2f5; font-family: Arial, sans-serif; }
    .contenedor { background:#fff; margin:40px auto; padding:10px; border-radius:10px; max-width:1200px; box-shadow:0 0 10px rgba(0,0,0,.1)}
    h3 {
    text-align: center;
    background-color: #154360;
    color: white;
    padding: 15px;
    border-radius: 5px;
    font-size: 20px;
    margin-bottom: 20px;
    }
    .filtro { text-align:center; margin-bottom:20px }
    select { padding:6px 10px; font-size:14px }
    table { width:100%; border-collapse: collapse; font-size:12px }
    th, td { border:1px solid #ccc; padding:8px; text-align:center }
    th { background:#154360; color:#fff }
    .docente { text-align:left }
    .btn-ver { background:#2980b9; color:#fff; padding:6px 10px; text-decoration:none; display:inline-block; border-radius:4px; font-size:13px; border:0; cursor:pointer }
    .tag-pendiente { background:#f9dede; color:#a33; padding:2px 6px; border-radius:4px; font-size:12px; font-weight:bold }
    .tag-conforme  { background:#def9e5; color:#117a3a; padding:2px 6px; border-radius:4px; font-size:12px; font-weight:bold }
    @media screen and (max-width: 768px) {
        .contenedor { margin:10px; padding:5px }
        table { font-size:12px; min-width:700px }
        .btn-ver { font-size:12px; padding:4px 8px }
        h3 { font-size:18px }
        .contenedor-tabla { overflow-x:auto; width:100% }
        .contenedor-tabla table { min-width:700px }
    }
    .tag-noenviado { background:#ffecc7; color:#9a6b00; padding:2px 6px; border-radius:4px; font-size:12px; font-weight:bold }
</style>
</head>
<body>
<div class="contenedor" style="overflow-x:auto;">
    <h3><strong>PLANES DE TUTORÍA – TUTORES DE AULA </strong></h3>

    <div class="filtro">
        <form method="GET" action="index.php">
            <input type="hidden" name="pagina" value="reportes_generales/vista_general_planes_tutor_aula.php">
            <label>Filtrar por Escuela Profesional: </label>
            <select name="id_car" onchange="this.form.submit()">
                <option value="">-- Todas --</option>
                <?php if ($escuelas): while ($e = $escuelas->fetch_assoc()): ?>
                    <option value="<?= (int)$e['id_car'] ?>" <?= (!is_null($id_car_filtro) && (int)$id_car_filtro === (int)$e['id_car']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['nom_car']) ?>
                    </option>
                <?php endwhile; endif; ?>
            </select>
        </form>
    </div>

    <div class="contenedor-tabla">
        <table>
            <thead>
                <tr>
                    <th>Escuela Profesional</th>
                    <th>Ciclo</th>
                    <th>Docente</th>
                    <th>Fecha de Envío</th>
                    <th>Acción</th>
                    <th>Conformidad</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                    <?php while ($row = $resultado->fetch_assoc()):
                            $docente = trim(($row['abreviatura_doce'] ? $row['abreviatura_doce'].' ' : '')
                                    . $row['apepa_doce'].' '.$row['apema_doce'].', '.$row['nom_doce']);

                            $enviado  = ((int)$row['estado_envio'] === 2);
                            $fechaEnvioCell = $enviado && $row['fecha_envio']
                                ? htmlspecialchars((string)$row['fecha_envio'])
                                : '<span class="tag-noenviado">En proceso</span>';

                            // Conformidad
                            $conforme = strtoupper((string)($row['estado_revision'] ?? '')) === 'CONFORME';
                            $celdaConformidad = $conforme
                                ? '<span class="tag-conforme">Conforme</span><br>'.htmlspecialchars((string)$row['fecha_revision'])
                                : '<span class="tag-pendiente">Pendiente</span>';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$row['nom_car']) ?></td>
                        <td><?= htmlspecialchars((string)$row['ciclo']) ?></td>
                        <td class="docente"><strong><?= htmlspecialchars($docente) ?></strong></td>

                        <!-- Fecha de envío -->
                        <td><?= $fechaEnvioCell ?></td>

                        <!-- Acción: ocultar botón si no fue enviado -->
                        <td>
                            <?php if ($enviado): ?>
                            <form onsubmit="return abrirVentanaPopup(this);" method="GET" action="https://tutoria.undc.edu.pe/vista/tutor_aula/vista_prev_plan_tutoria.php">
                                <input type="hidden" name="id_cargalectiva" value="<?= (int)$row['id_cargalectiva'] ?>">
                                <input type="hidden" name="id_plan" value="<?= (int)$row['id_plan_tutoria'] ?>">
                                <button type="submit" class="btn-ver">Ver plan</button>
                            </form>
                            <?php else: ?>
                            —
                            <?php endif; ?>
                        </td>

                        <!-- Conformidad -->
                        <td><?= $celdaConformidad ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr><td colspan="6">No hay planes registrados en el semestre actual.</td></tr>
                    <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function abrirVentanaPopup(form) {
    const ancho = 900, alto = 700;
    const izquierda = (screen.width - ancho) / 2;
    const arriba = (screen.height - alto) / 2;

    const url = new URL(form.action);
    const params = new URLSearchParams(new FormData(form));
    url.search = params.toString();

    window.open(
        url.toString(),
        'VistaPrevTutoria',
        `width=${ancho},height=${alto},top=${arriba},left=${izquierda},resizable=yes,scrollbars=yes`
    );
    return false;
}
</script>
</body>
</html>
