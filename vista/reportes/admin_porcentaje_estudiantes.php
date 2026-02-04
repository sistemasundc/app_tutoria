<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');

if (!isset($_SESSION['S_IDUSUARIO']) || !in_array($_SESSION['S_ROL'], [
    'COORDINADOR GENERAL DE TUTORIA',
    'SUPERVISION',
    'VICEPRESIDENCIA ACADEMICA'
])) {
    die('Acceso no autorizado');
}

$conexion = new conexion();
$cn = $conexion->conectar();

$id_semestre = $_SESSION['S_SEMESTRE'];
$id_car = isset($_GET['id_car']) ? intval($_GET['id_car']) : $_SESSION['S_SCHOOL'];

// Obtener lista de carreras
$carreras = [];
$sqlCarreras = "SELECT id_car, nom_car FROM carrera";
$resCarreras = $cn->query($sqlCarreras);
while ($fila = $resCarreras->fetch_assoc()) {
    $carreras[] = $fila;
}

// Total asignados
$sqlTotal = "SELECT COUNT(DISTINCT id_estudiante) AS total 
             FROM tutoria_asignacion_tutoria 
             WHERE id_semestre = ? 
             AND id_docente IN (
                 SELECT id_doce FROM carga_lectiva WHERE id_car = ? AND id_semestre = ?
             )";
$stmtTotal = $cn->prepare($sqlTotal);
$stmtTotal->bind_param("iii", $id_semestre, $id_car, $id_semestre);
$stmtTotal->execute();
$resTotal = $stmtTotal->get_result();
$totalEstudiantes = $resTotal->fetch_assoc()['total'] ?: 1;

// Labels y arrays
/* $labelsMeses = ['Abril', 'Mayo', 'Junio', 'Julio']; */
$labelsMeses = [ 'Septiembre', 'Octubre', 'Novienbre','Diciembre'];
$porcentajesGrupal = [];
$porcentajesIndividual = [];

/* for ($m = 4; $m <= 8; $m++) { */
for ($m = 9; $m <= 12; $m++) {
    // GRUPAL
    $sqlG = "SELECT COUNT(DISTINCT d.id_estu) AS total 
             FROM tutoria_detalle_sesion d
             INNER JOIN tutoria_sesiones_tutoria_f78 s ON s.id_sesiones_tuto = d.sesiones_tutoria_id
             INNER JOIN tutoria_asignacion_tutoria a ON d.asignacion_id = a.id_asignacion
             INNER JOIN carga_lectiva cl ON a.id_docente = cl.id_doce
             WHERE d.marcar_asis_estu = 1
             AND s.id_rol = 6
             AND cl.id_car = ?
             AND MONTH(s.fecha) = ?
             AND cl.id_semestre = ?";
    $stmtG = $cn->prepare($sqlG);
    $stmtG->bind_param("iii", $id_car, $m, $id_semestre);
    $stmtG->execute();
    $resG = $stmtG->get_result()->fetch_assoc();
    $porcentajesGrupal[] = min(round(($resG['total'] / $totalEstudiantes) * 100, 2), 100);

    // INDIVIDUAL
    $sqlI = "SELECT COUNT(DISTINCT d.id_estu) AS total 
             FROM tutoria_detalle_sesion_curso d
             INNER JOIN tutoria_sesiones_tutoria_f78 s ON s.id_sesiones_tuto = d.sesiones_tutoria_id
             INNER JOIN carga_lectiva cl ON d.id_cargalectiva = cl.id_cargalectiva
             WHERE d.marcar_asis_estu = 1
             AND s.id_rol = 2
             AND cl.id_car = ?
             AND MONTH(s.fecha) = ?
             AND cl.id_semestre = ?";
    $stmtI = $cn->prepare($sqlI);
    $stmtI->bind_param("iii", $id_car, $m, $id_semestre);
    $stmtI->execute();
    $resI = $stmtI->get_result()->fetch_assoc();
    $porcentajesIndividual[] = min(round(($resI['total'] / $totalEstudiantes) * 100, 2), 100);
}

$sqlNombre = "SELECT nom_car FROM carrera WHERE id_car = ?";
$stmtNombre = $cn->prepare($sqlNombre);
$stmtNombre->bind_param("i", $id_car);
$stmtNombre->execute();
$resNombre = $stmtNombre->get_result()->fetch_assoc();
$nombre_escuela = strtoupper($resNombre['nom_car']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Participación Estudiantil</title>
    <style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background-color: #f4f6f9;
    }
    .titulo-banda {
        width: 82.5%;
        background-color: #2c3e50;
        color: white;
        padding: 15px 20px;
        font-size: 20px;
        font-weight: bold;
        text-align: center;
        margin-top: 50px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.8);
    }
    .filtro {
        margin-top: 20px;
        text-align: center;
    }
    .grafico-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
        padding: 0 10px;
        margin-top: 30px;
    }
    .grafico-tabla {
        flex: 1 1 400px;
        max-width: 600px;
        background: white;
        padding: 20px;
        border-radius: 6px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.8);
    }
    .barra-container {
        margin: 15px 0;
    }
    .barra {
        height: 25px;
        background: #4e73df;
        color: white;
        padding-left: 10px;
        font-size: 13px;
        line-height: 25px;
        border-radius: 4px;
        width: 0;
        transition: width 1s ease-in-out;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    .barra-individual {
        background: #1cc88a;
    }
    .label-mes {
        font-weight: bold;
        margin-bottom: 5px;
    }
    </style>
</head>
<body>



<center>
<h2 class="titulo-banda">
    PORCENTAJE DE PARTICIPACIÓN ESTUDIANTIL - ESCUELA PROFESIONAL DE <?= strtoupper($nombre_escuela) ?>
</h2>
</center>
<div class="filtro">
    <form method="GET" action="index.php" style="text-align:center; margin-top: 10px;">
		  <input type="hidden" name="pagina" value="reportes/admin_porcentaje_estudiantes.php">
        <label for="id_car"><strong>FILTRAR POR ESCUELA PROFESIONAL:</strong></label>
        <select name="id_car" id="id_car" onchange="this.form.submit()"style="padding: 5px; margin-right: 10px;" >
            <?php foreach ($carreras as $car): ?>
                <option value="<?= $car['id_car'] ?>" <?= ($car['id_car'] == $id_car ? 'selected' : '') ?>>
                    <?= strtoupper($car['nom_car']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="grafico-container">
    <div class="grafico-tabla">
        <h3 style="text-align:center;">Participación en Tutoría Grupal</h3>
        <?php foreach ($labelsMeses as $i => $mes): ?>
            <div class="barra-container">
                <div class="label-mes"><?= $mes ?> (<?= $porcentajesGrupal[$i] ?>%)</div>
                <div class="barra" style="width: <?= $porcentajesGrupal[$i] ?>%;"><?= $porcentajesGrupal[$i] ?>%</div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grafico-tabla">
        <h3 style="text-align:center;">Participación en Tutoría Individual</h3>
        <?php foreach ($labelsMeses as $i => $mes): ?>
            <div class="barra-container">
                <div class="label-mes"><?= $mes ?> (<?= $porcentajesIndividual[$i] ?>%)</div>
                <div class="barra barra-individual" style="width: <?= $porcentajesIndividual[$i] ?>%;"><?= $porcentajesIndividual[$i] ?>%</div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
window.onload = () => {
    document.querySelectorAll('.barra').forEach(barra => {
        const finalWidth = barra.style.width;
        barra.style.width = '0';
        setTimeout(() => { barra.style.width = finalWidth; }, 100);
    });
};
</script>

</body>
</html>
