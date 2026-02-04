<?php  
ini_set("display_errors", 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
require_once("../../modelo/modelo_conexion.php");

if (!isset($_GET['id_derivacion']) || empty($_GET['id_derivacion'])) {
    echo "ID de derivación no proporcionado.";
    exit;
}

$id_derivacion = intval($_GET['id_derivacion']);

$conexion = new conexion();
$cn = $conexion->conectar();

$sql = "SELECT 
    d.apepa_doce, d.apema_doce, d.nom_doce,
    d.email_doce,
    e.apepa_estu, e.apema_estu, e.nom_estu,
    e.email_estu, e.celu_estu,
    c.nom_car,
    cl.ciclo, cl.turno, cl.seccion,
    f6.motivo_ref, f6.resultado_contra, f6.observaciones, f6.fechaDerivacion
FROM tutoria_derivacion_tutorado_f6 f6
INNER JOIN docente d ON d.id_doce = f6.id_docente
INNER JOIN estudiante e ON e.id_estu = f6.id_estudiante
INNER JOIN tutoria_asignacion_tutoria tat ON tat.id_estudiante = f6.id_estudiante
INNER JOIN carga_lectiva cl ON cl.id_cargalectiva = tat.id_carga
INNER JOIN carrera c ON c.id_car = cl.id_car
WHERE f6.id_derivaciones = ?";

$stmt = $cn->prepare($sql);
if (!$stmt) {
    echo "Error al preparar la consulta: " . $cn->error;
    exit;
}

$stmt->bind_param("i", $id_derivacion);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Docente
    $nombre_docente = $row['apepa_doce'] . " " . $row['apema_doce'] . " " . $row['nom_doce'];
    $correo_docente = $row['email_doce'];

    // Estudiante
    $estudiante = $row['apepa_estu'] . " " . $row['apema_estu'] . " " . $row['nom_estu'];
    $correo_estudiante = $row['email_estu'];
    $telefono_estudiante = $row['celu_estu'];

    // Datos académicos
    $escuela = $row['nom_car'];
    $ciclo = $row['ciclo'];
    $turno = $row['turno'];
    $seccion = isset($row['seccion']) ? $row['seccion'] : '---';

    // Derivación
    $fecha = $row['fechaDerivacion'];
    $motivo = $row['motivo_ref'];
    $resultado = $row['resultado_contra'];
    $observaciones = $row['observaciones'];
} else {
    echo "No se encontraron datos.";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte de Derivación</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            line-height: 2; /* ← Aumentamos el interlineado */
        }
        h2 {
            text-align: center;
        }
        p {
            margin: 15px 0;     /* Más espacio entre líneas */
            line-height: 1.5;   /* Más interlineado solo en párrafos */
        }

        .grupo {
            margin-bottom: 15px;
        }
        
        textarea {
            width: 100%;
            height: 90px;
            resize: none;
            line-height: 1.5;
            padding: 8px;
            font-size: 14px;
        }
    </style>
</head>
<body onload="window.print()">
    <h2>Reporte de Derivación</h2>

    <div class="grupo">
        <p><strong>Tutor(a):</strong> <?php echo $nombre_docente; ?></p>
        <p><strong>Correo del Tutor(a):</strong> <?php echo $correo_docente; ?></p>
        <p><strong>Fecha de derivación:</strong> <?php echo $fecha; ?></p>
    </div>

    <div class="grupo">
        <p><strong>Escuela Profesional:</strong> <?php echo $escuela; ?></p>
    </div>

    <div class="grupo">
        <p><strong>Estudiante derivado:</strong> <?php echo $estudiante; ?></p>
        <p><strong>Correo del estudiante:</strong> <?php echo $correo_estudiante; ?></p>
        <p><strong>Teléfono:</strong> <?php echo $telefono_estudiante; ?></p>
        <p>
            <strong>Ciclo:</strong> <?php echo $ciclo; ?>&nbsp;&nbsp;&nbsp;
            <strong>Turno:</strong> <?php echo $turno; ?>&nbsp;&nbsp;&nbsp;
            <strong>Sección:</strong> <?php echo $seccion; ?>
        </p>
    </div>

    <div class="grupo">
        <p><strong>Motivo de derivación:</strong></p>
        <textarea readonly><?php echo $motivo; ?></textarea>
    </div>

   
</body>
</html>
