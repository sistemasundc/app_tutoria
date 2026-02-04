<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
date_default_timezone_set('America/Lima');

require_once('../../modelo/modelo_conexion.php');
$conexion = new conexion();
$conexion->conectar();

if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], ['DIRECTOR DE DEPARTAMENTO ACADEMICO', 'DIRECCION DE ESCUELA', 'SUPERVISION', 'COORDINADOR GENERAL DE TUTORIA','VICEPRESIDENCIA ACADEMICA'])) {
    die('Acceso no autorizado');
}

$id_car = isset($_GET['id_car']) ? intval($_GET['id_car']) : ($_SESSION['S_SCHOOL'] ?? null);
$id_semestre = $_SESSION['S_SEMESTRE'] ?? 32;
$nombre_semestre = $_SESSION['S_NOMSEMESTRE'] ?? '2025-1';

$ciclo_filtro = $_GET['ciclo'] ?? '';
$mes_filtro = $_GET['mes'] ?? 'julio';

// Obtener nombre de la carrera
$nombre_carrera = '……………………………';
$stmtCar = $conexion->conexion->prepare("SELECT nom_car FROM carrera WHERE id_car = ?");
$stmtCar->bind_param("i", $id_car);
$stmtCar->execute();
$resCar = $stmtCar->get_result();
if ($row = $resCar->fetch_assoc()) $nombre_carrera = strtoupper($row['nom_car']);

// Ciclos únicos
$ciclos_actuales = [];
$resCiclos = $conexion->conexion->query("
  SELECT DISTINCT ciclo 
  FROM carga_lectiva 
  WHERE id_car = $id_car AND id_semestre = $id_semestre AND tipo = 'M'
");
while ($row = $resCiclos->fetch_assoc()) {
    $ciclos_actuales[] = $row['ciclo'];
}

// Obtener asignaturas
$sql = "
SELECT DISTINCT
    cl.id_cargalectiva,
    cl.ciclo,
    cl.turno,
    cl.seccion,
    asi.nom_asi,
    d.id_doce,
    d.abreviatura_doce,
    d.apepa_doce,
    d.apema_doce,
    d.nom_doce,
    d.email_doce,
    d.condicion,
    d.celu_doce
FROM carga_lectiva cl
JOIN docente d ON (
    (
        cl.id_doce = d.id_doce
        AND (
            ? IN ('junio', 'julio')
            OR cl.id_cargalectiva != 4945
        )
    )
    OR (
        d.id_doce = 19
        AND cl.id_cargalectiva = 4945
        AND ? IN ('abril', 'mayo')
    )
)
JOIN asignatura asi ON asi.id_asi = cl.id_asi
WHERE cl.id_car = ? AND cl.id_semestre = ? AND cl.tipo='M'
AND NOT (cl.id_doce = 17 AND cl.id_car = 1)
";

$params = [$mes_filtro, $mes_filtro, $id_car, $id_semestre];
$types = "ssii";

if (!empty($ciclo_filtro)) {
    $sql .= " AND cl.ciclo = ?";
    $params[] = $ciclo_filtro;
    $types .= "s";
}

$sql .= " ORDER BY asi.nom_asi";

$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$asignaturas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener informes
$sqlInf = "SELECT id_cargalectiva, estado_envio, fecha_envio, LOWER(mes_informe) as mes FROM tutoria_informe_mensual_curso WHERE id_car = ?";
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

// Clasificar
$enviaron = [];
$no_enviaron = [];

foreach ($asignaturas as $i => $row) {
    $id_cargalectiva = $row['id_cargalectiva'];
    $docente = strtoupper($row['abreviatura_doce'].' '.$row['apepa_doce'].' '.$row['apema_doce'].', '.$row['nom_doce']);
    $correo = $row['email_doce'];
    $asignatura = $row['nom_asi'];
    $fecha_raw = $informes[$id_cargalectiva]['fecha_envio'] ?? null;
    $fecha = (!empty($fecha_raw) && $fecha_raw != '0000-00-00') ? date('d/m/Y', strtotime($fecha_raw)) : '';
    $estado_raw = $informes[$id_cargalectiva]['estado_envio'] ?? null;

    $registro = [
        'n' => $i + 1,
        'asignatura' => $asignatura,
        'docente' => $docente,
        'correo' => $correo,
        'fecha' => $fecha,
        'turno' => $row['turno'],
        'seccion' => $row['seccion'],
        'condicion' => $row['condicion'] ?? '',
        'celular' => $row['celu_doce'] ?? '',
        'estado' => 'NO CUMPLIÓ'
    ];

    if ($estado_raw === 2) {
        $registro['estado'] = 'CUMPLIÓ';
        $enviaron[] = $registro;
    } elseif ($estado_raw === 1) {
        $registro['estado'] = 'GUARDADO (sin envío)';
        $no_enviaron[] = $registro;
    } else {
        $no_enviaron[] = $registro;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Informe Mensual - TUTORES DE CURSO</title>
<style>
body { font-family: Arial; background: #fff; padding: 20px; }
.contenedor { max-width: 1100px; margin: auto; }
h2, h3 { text-align: center; font-size: 16px;}
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { padding: 6px; border: 1px solid #ccc; font-size: 12px; }
th { background: #34495e; color: white; }
.fila-verde { background: #d4efdf; }
.fila-roja { background: #f5b7b1; }
.fila-amarilla { background:rgb(238, 218, 153); }
.filtro-form select { margin-right: 20px; padding: 5px; }
.boton-imprimir { float: right; background: #1a5276; color: white; padding: 6px 12px; border: none; cursor: pointer; }
.filtro-mes { max-width: 300px; margin: 0 auto 20px; }
@media print { .boton-imprimir, .filtro-form { display: none; } }
</style>
</head>
<body>
<div class="contenedor">
<button class="boton-imprimir" onclick="window.print()">🖨️ Imprimir</button>

<h2>DOCENTES TUTORES DE CURSO - <?= htmlspecialchars($nombre_semestre) ?></h2>
<h2>ESCUELA PROFESIONAL DE <?= $nombre_carrera ?></h2>

<form method="GET" class="filtro-form">
<input type="hidden" name="id_car" value="<?= htmlspecialchars($id_car) ?>">
<center>
<label><strong>Ciclo:</strong>
<select name="ciclo" onchange="this.form.submit()">
<option value="">-- Todos --</option>
<?php foreach ($ciclos_actuales as $c): ?>
<option value="<?= $c ?>" <?= $ciclo_filtro == $c ? 'selected' : '' ?>><?= $c ?></option>
<?php endforeach; ?>
</select></label>
<label><strong>Mes:</strong>
<select name="mes" onchange="this.form.submit()">
<option value="">-- Todos --</option>
<?php foreach (['abril', 'mayo', 'junio', 'julio'] as $mes): ?>
<option value="<?= $mes ?>" <?= $mes_filtro == $mes ? 'selected' : '' ?>><?= ucfirst($mes) ?></option>
<?php endforeach; ?>
</select></label>
</center>
</form>

<h3>DOCENTES QUE ENVIARON SU INFORME EN EL MES DE <?= strtoupper($mes_filtro) ?></h3>
<table>
<tr>
<th>N°</th>
<th>Asignatura</th>
<th>Sección | Turno</th>
<th>Docente</th>
<th>Correo</th>
<th>Condición</th>
<th>Celular</th>
<th>Fecha de Envío</th>
<th>Estado</th>
</tr>
<?php if (empty($enviaron)): ?>
<tr><td colspan="9">Ningún docente ha enviado.</td></tr>
<?php else: foreach ($enviaron as $row): ?>
<tr class="fila-verde">
<td><?= $row['n'] ?></td>
<td><?= $row['asignatura'] ?></td>
<td><?= $row['seccion'] ?> | <?= $row['turno'] ?></td>
<td><?= $row['docente'] ?></td>
<td><?= $row['correo'] ?></td>
<td><?= $row['condicion'] ?></td>
<td><?= $row['celular'] ?></td>
<td><?= $row['fecha'] ?></td>
<td><?= $row['estado'] ?></td>
</tr>
<?php endforeach; endif; ?>
</table>

<h3>DOCENTES QUE NO ENVIARON SU INFORME EN EL MES DE <?= strtoupper($mes_filtro) ?> </h3>
<table>
<tr>
<th>N°</th>
<th>Asignatura</th>
<th>Sección | Turno</th>
<th>Docente</th>
<th>Correo</th>
<th>Condición</th>
<th>Celular</th>
<th>Estado</th>
</tr>
<?php if (empty($no_enviaron)): ?>
<tr><td colspan="8">Todos los docentes cumplieron con el envío del informe.</td></tr>
<?php else: foreach ($no_enviaron as $row): ?>
<?php
$clase = 'fila-roja';
if ($row['estado'] === 'GUARDADO (sin envío)') $clase = 'fila-amarilla';
?>
<tr class="<?= $clase ?>">
<td><?= $row['n'] ?></td>
<td><?= $row['asignatura'] ?></td>
<td><?= $row['seccion'] ?> | <?= $row['turno'] ?></td>
<td><?= $row['docente'] ?></td>
<td><?= $row['correo'] ?></td>
<td><?= $row['condicion'] ?></td>
<td><?= $row['celular'] ?></td>
<td><?= $row['estado'] ?></td>
</tr>
<?php endforeach; endif; ?>
</table>
</div>
</body>
</html>
