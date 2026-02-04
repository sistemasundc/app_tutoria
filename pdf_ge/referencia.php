<?php  
ini_set("display_errors", 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require_once(__DIR__ . "/../modelo/modelo_conexion.php");
$conexion = new conexion();
$conexion->conectar();
$db = $conexion->conexion;

if (!isset($_GET['id']) || !isset($_GET['semestre'])) {
    echo "Parámetros incompletos.";
    exit;
}

$id_der = intval($_GET['id']);
$semestre = intval($_GET['semestre']);

$sql = "SELECT 
            d.fecha,
            d.hora,
            d.motivo_ref,
            d.area_apoyo_id,
            e.apepa_estu, e.apema_estu, e.nom_estu,
            COALESCE(cl.ciclo, '') AS ciclo_estu,
            COALESCE(cl.seccion, '') AS seccion,
            COALESCE(cl.turno, '') AS turno,
            doc.abreviatura_doce,
            doc.apepa_doce, doc.apema_doce, doc.nom_doce,
            doc.email_doce,
            ca.nom_car
        FROM tutoria_derivacion_tutorado_f6 d
        INNER JOIN estudiante e ON e.id_estu = d.id_estudiante
        INNER JOIN docente doc ON doc.id_doce = d.id_docente
        LEFT JOIN carga_lectiva cl ON cl.id_doce = d.id_docente AND cl.id_semestre = ?
        LEFT JOIN carrera ca ON ca.id_car = doc.id_car
        WHERE d.id_derivaciones = ? 
        LIMIT 1";

$stmt = mysqli_prepare($db, $sql);
if (!$stmt) {
    echo "Error en prepare: " . mysqli_error($db);
    exit;
}
$stmt->bind_param("ii", $semestre, $id_der);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "No se encontró referencia con ese ID.";
    exit;
}

$row = $result->fetch_assoc();

$fecha = $row['fecha'];
$hora = $row['hora'];
$motivo = $row['motivo_ref'];
$tipo = intval($row['area_apoyo_id']);
$tutor = strtoupper(trim($row['abreviatura_doce'] . ' ' . $row['apepa_doce'] . ' ' . $row['apema_doce'] . ' ' . $row['nom_doce']));
$correo = $row['email_doce'];
$escuela = strtoupper($row['nom_car']);
$ciclo = strtoupper($row['ciclo_estu']);
$seccion = strtoupper($row['seccion']);
$turno = strtoupper($row['turno']);
$estudiante = '1) ' . strtoupper($row['apepa_estu'] . ' ' . $row['apema_estu'] . ' ' . $row['nom_estu']);
$tipo_sesion = ['', 'Médico', 'Asistente Social', 'Psicólogo', 'Defensoría'];
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hoja de Referencia - UNDC</title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }
        body {
            font-family: 'Times New Roman', serif;
            margin: 0;
            padding: 0;
            background: #f0f0f0;
        }
        header {
            text-align: center;
            margin-bottom: 20px;
        }
        header img {
            width: 120px;
        }
        .titulo {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin-top: 10px;
        }
        .subtitulo {
            text-align: center;
            font-size: 17px;
            margin-top: 5px;
            font-weight: bold;
        }
        .bloque {
            margin-top: 25px;
        }
        .etiqueta {
            font-weight: bold;
            font-size: 15px;
            margin-bottom: 4px;
        }
        .box {
            border: 1px solid #999;
            padding: 10px;
            font-size: 15px;
        }
        .documento {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: auto;
            background: white;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            box-sizing: border-box;
            position: relative;
        }
        .logo-superior {
            position: absolute;
            top: 40px;
            left: 40px;
            width: 220px;
            max-width: 100%;
        }
        .formato {
            position: absolute;
            top: 40px;
            right: 40px;
            width: 320px;
            max-width: 100%;
        }
    </style>
</head>
<body>
<div class="documento">
    <header>
        <img src="../img/undc.png" class="logo-superior" alt="Logo UNDC">
        <img src="pdf/img/undc_f6.JPG" class="formato" alt="UNDC">
        <br><br>
        <div class="titulo">ESCUELA PROFESIONAL DE <?= $escuela ?></div>
        <div class="subtitulo">Hoja de Referencia</div>
        <div class="subtitulo">Año Académico: <?= $semestre ?></div>
    </header>
    <pre style=" font-size: 15px;">

    <strong>Fecha:</strong> <?= $fecha ?>                               <strong>hora:</strong> <?= $hora ?> <br>
    <strong>Tutor:</strong> <?= $tutor ?><br>
    <strong>Estudiante(s):</strong><br>
    <?= $estudiante ?><br>
    <strong>Motivo de la referencia:</strong><br>
    <?= nl2br($motivo) ?><br>
    <strong>Dirigido a:</strong><br>
    <?php for ($i = 1; $i < count($tipo_sesion); $i++): ?>
       <?= ($i == $tipo) ? '[X]' : '[ ]' ?> <?= $tipo_sesion[$i] ?><br>
    <?php endfor; ?>

    <center>
    <em>Fecha: <?= $fecha ?></em>
    <?= $tutor ?><br>
    <?= $correo ?>
    </center>
    </pre>
    <footer style=" margin-top: 300px;">
       <center> <img src="pdf/img/footer.JPG" alt="Footer UNDC"></center>
    </footer>
</div>
</body>
</html>