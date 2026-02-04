<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
date_default_timezone_set('America/Lima');

require_once('../../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();

$id_semestre = $_SESSION['S_SEMESTRE'] ?? 32;
$nombre_semestre = $_SESSION['S_NOMSEMESTRE'] ?? '2025-2';

$mes_filtro = $_GET['mes'] ?? 'octubre';

// obtener todas las carreras
$resCarreras = $conexion->conexion->query("SELECT id_car, UPPER(nom_car) AS nom_car FROM carrera ORDER BY nom_car");
$carreras = [];
while ($r = $resCarreras->fetch_assoc()) {
    $carreras[$r['id_car']] = $r['nom_car'];
}

// función para PIT
function obtenerPIT($conexion, $id_doce, $id_semestre) {
    $sql = "SELECT 1 FROM catelec WHERE id_doce=? AND id_semestre=? AND numcate=11 LIMIT 1";
    $stmt = $conexion->conexion->prepare($sql);
    $stmt->bind_param("ii", $id_doce, $id_semestre);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) return ['estado'=>'SI', 'color'=>'green'];

    $sql2 = "SELECT 1 FROM tutoria_horas_no_lectivas WHERE id_doce=? AND id_semestre=? LIMIT 1";
    $stmt2 = $conexion->conexion->prepare($sql2);
    $stmt2->bind_param("ii", $id_doce, $id_semestre);
    $stmt2->execute();
    $stmt2->store_result();
    if ($stmt2->num_rows > 0) return ['estado'=>'SI', 'color'=>'green'];

    return ['estado'=>'NO', 'color'=>'red'];
}

$datos = [];
$eg_datos = [];

foreach ($carreras as $id_car => $nombre_carrera) {
    $sql = "
    SELECT cl.id_cargalectiva, cl.ciclo, cl.turno, cl.seccion, asi.nom_asi,
        d.id_doce, d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce,
        d.email_doce, d.condicion, d.celu_doce
    FROM carga_lectiva cl
    JOIN docente d ON d.id_doce = cl.id_doce
    JOIN asignatura asi ON asi.id_asi = cl.id_asi
    WHERE cl.id_car = ? AND cl.id_semestre = ? AND cl.tipo='M' AND (asi.tipo_c IS NULL OR asi.tipo_c != 'EG')
    AND NOT (cl.id_doce=17 AND cl.id_car=1)
    ORDER BY asi.nom_asi";
    $stmt = $conexion->conexion->prepare($sql);
    $stmt->bind_param("ii", $id_car, $id_semestre);
    $stmt->execute();
    $asignaturas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $sql_eg = "
    SELECT cl.id_cargalectiva, cl.ciclo, cl.turno, cl.seccion, asi.nom_asi,
        d.id_doce, d.abreviatura_doce, d.apepa_doce, d.apema_doce, d.nom_doce,
        d.email_doce, d.condicion, d.celu_doce
    FROM carga_lectiva cl
    JOIN docente d ON d.id_doce = cl.id_doce
    JOIN asignatura asi ON asi.id_asi = cl.id_asi
    WHERE cl.id_car = ? AND cl.id_semestre = ? AND cl.tipo='M' AND asi.tipo_c = 'EG'
    AND NOT (cl.id_doce=17 AND cl.id_car=1)
    ORDER BY asi.nom_asi";
    $stmt_eg = $conexion->conexion->prepare($sql_eg);
    $stmt_eg->bind_param("ii", $id_car, $id_semestre);
    $stmt_eg->execute();
    $asignaturas_eg = $stmt_eg->get_result()->fetch_all(MYSQLI_ASSOC);

    $sqlInf = "
    SELECT id_cargalectiva, estado_envio, fecha_envio, LOWER(mes_informe) as mes
    FROM tutoria_informe_mensual_curso
    WHERE id_car = ?";
    $paramsInf = [$id_car];
    $typesInf = "i";
    if (!empty($mes_filtro)) {
        $sqlInf .= " AND LOWER(mes_informe) = ?";
        $paramsInf[] = strtolower($mes_filtro);
        $typesInf .= "s";
    }

    $stmtInf = $conexion->conexion->prepare($sqlInf);
    $stmtInf->bind_param($typesInf, ...$paramsInf);
    $stmtInf->execute();
    $resInf = $stmtInf->get_result();
    $informes = [];
    while ($row = $resInf->fetch_assoc()) {
        $informes[$row['id_cargalectiva']] = $row;
    }

    foreach ($asignaturas as $row) {
        $id_cargalectiva = $row['id_cargalectiva'];
        $estado_raw = $informes[$id_cargalectiva]['estado_envio'] ?? null;
        if ($estado_raw != 2) {
            $docente = strtoupper($row['abreviatura_doce'].' '.$row['apepa_doce'].' '.$row['apema_doce'].', '.$row['nom_doce']);
            $pit = obtenerPIT($conexion, $row['id_doce'], $id_semestre);
            $datos[$nombre_carrera][] = [
                'n' => count($datos[$nombre_carrera] ?? []) + 1,
                'asignatura' => $row['nom_asi'],
                'docente' => $docente,
                'correo' => $row['email_doce'],
                'turno' => $row['turno'],
                'seccion' => $row['seccion'],
                'condicion' => $row['condicion'] ?? '',
                'celular' => $row['celu_doce'] ?? '',
                'estado' => $estado_raw == 1 ? 'GUARDADO (sin envío)' : 'NO CUMPLIÓ',
                'pit' => $pit
            ];
        }
    }

    foreach ($asignaturas_eg as $row) {
        $id_cargalectiva = $row['id_cargalectiva'];
        $estado_raw = $informes[$id_cargalectiva]['estado_envio'] ?? null;
        if ($estado_raw != 2) {
            $docente = strtoupper($row['abreviatura_doce'].' '.$row['apepa_doce'].' '.$row['apema_doce'].', '.$row['nom_doce']);
            $pit = obtenerPIT($conexion, $row['id_doce'], $id_semestre);
            $eg_datos[$nombre_carrera][] = [
                'n' => count($eg_datos[$nombre_carrera] ?? []) + 1,
                'asignatura' => $row['nom_asi'],
                'docente' => $docente,
                'correo' => $row['email_doce'],
                'turno' => $row['turno'],
                'seccion' => $row['seccion'],
                'condicion' => $row['condicion'] ?? '',
                'celular' => $row['celu_doce'] ?? '',
                'estado' => $estado_raw == 1 ? 'GUARDADO (sin envío)' : 'NO CUMPLIÓ',
                'pit' => $pit
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>TUTORES DE CURSO</title>
<style>
body { font-family: Arial; background: #fff; padding: 20px; }
.contenedor { max-width: 1200px; margin: auto; }
h2, h3 { text-align: center; font-size: 16px;}
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { padding: 6px; border: 1px solid #ccc; font-size: 12px; }
th { background: #34495e; color: white; }
td:last-child, th:last-child {
    text-align: center;
}
.fila-roja { background: #f5b7b1; }
.fila-amarilla { background:rgb(238, 218, 153); }
.filtro-form select { margin-right: 20px; padding: 5px; text }
.boton-imprimir { float: right; background: #1a5276; color: white; padding: 6px 12px; border: none; cursor: pointer; }
@media print { .boton-imprimir, .filtro-form { display: none; } }
</style>
</head>
<body>
<div class="contenedor">
<button class="boton-imprimir" onclick="window.print()">🖨️ Imprimir</button>

<h2>LISTA DE DOCENTES QUE NO CUMPLIERON CON EL ENVÍO DE INFORMES MENSUALES - <?= htmlspecialchars($nombre_semestre) ?></h2>

<form method="GET" class="filtro-form">
<center>
<label><strong>Mes:</strong>
<select name="mes" onchange="this.form.submit()">
<option value="">-- Todos --</option>
<?php foreach (['abril', 'mayo', 'junio', 'julio'] as $mes): ?>
<option value="<?= $mes ?>" <?= $mes_filtro == $mes ? 'selected' : '' ?>><?= ucfirst($mes) ?></option>
<?php endforeach; ?>
</select></label>
</center>
</form>

<?php if (empty($datos) && empty($eg_datos)): ?>
<p style="text-align:center;">Todos los docentes cumplieron o no hay registros.</p>
<?php endif; ?>

<?php foreach ($datos as $carrera => $registros): ?>
<h3><?= $carrera ?></h3>
<table>
<tr>
<th>N°</th><th>Asignatura</th><th>Sección | Turno</th><th>Docente</th><th>Correo</th><th>Condición</th><th>Celular</th><th>Estado</th><th>TUTORÍA</th>
</tr>
<?php foreach ($registros as $row): ?>
<?php $clase = 'fila-roja'; if ($row['estado'] === 'GUARDADO (sin envío)') $clase = 'fila-amarilla'; ?>
<tr class="<?= $clase ?>">
<td><?= $row['n'] ?></td>
<td><?= $row['asignatura'] ?></td>
<td><?= $row['seccion'] ?> | <?= $row['turno'] ?></td>
<td><?= $row['docente'] ?></td>
<td><?= $row['correo'] ?></td>
<td><?= $row['condicion'] ?></td>
<td><?= $row['celular'] ?></td>
<td><?= $row['estado'] ?></td>
<td style="color:<?= $row['pit']['color'] ?>; font-weight:bold;"><?= $row['pit']['estado'] ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endforeach; ?>

<?php foreach ($eg_datos as $carrera => $registros): ?>
<h3><?= $carrera ?> — <span style="color:#2980b9;">ESTUDIOS GENERALES</span></h3>
<table>
<tr>
<th>N°</th><th>Asignatura</th><th>Sección | Turno</th><th>Docente</th><th>Correo</th><th>Condición</th><th>Celular</th><th>Estado</th><th>PIT</th>
</tr>
<?php foreach ($registros as $row): ?>
<?php $clase = 'fila-roja'; if ($row['estado'] === 'GUARDADO (sin envío)') $clase = 'fila-amarilla'; ?>
<tr class="<?= $clase ?>">
<td><?= $row['n'] ?></td>
<td><?= $row['asignatura'] ?></td>
<td><?= $row['seccion'] ?> | <?= $row['turno'] ?></td>
<td><?= $row['docente'] ?></td>
<td><?= $row['correo'] ?></td>
<td><?= $row['condicion'] ?></td>
<td><?= $row['celular'] ?></td>
<td><?= $row['estado'] ?></td>
<center><td style=" color:<?= $row['pit']['color'] ?>; font-weight:bold;"><?= $row['pit']['estado'] ?></td></center>
</tr>
<?php endforeach; ?>
</table>
<?php endforeach; ?>

</div>
</body>
</html>
