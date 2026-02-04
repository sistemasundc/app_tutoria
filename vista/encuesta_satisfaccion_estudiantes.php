<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 
session_start();

require_once '../modelo/modelo_conexion.php';

$conexionObj = new conexion();
$conexion = $conexionObj->conectar();
if (!isset($_SESSION['S_IDESTU']) || !isset($_SESSION['S_EMAIL'])) {
    die("No ha iniciado sesión correctamente como estudiante.");
}
/* var_dump($_SESSION); */
$correoEstudiante = '';
$idEstu = $_SESSION['S_IDESTU'] ?? null;
/* echo "ID ESTUDIANTE EN SESIÓN: " . ($_SESSION['S_IDESTU'] ?? 'NO DEFINIDO'); */
$semestreActual = $_SESSION['S_SEMESTRE'] ?? 32;   
/* $semestreActual = 30; */
if ($idEstu) {
    $stmt = $conexion->prepare("SELECT email_estu FROM estudiante WHERE id_estu = ?");
    $stmt->bind_param("i", $idEstu);
    $stmt->execute();
    $stmt->bind_result($correoEstudiante);
    $stmt->fetch();
    $stmt->close();
}
$msg = '';
$tipoMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $correoEstudiante;
    $ciclo = $_POST['ciclo_academico'] ?? '';
    $escuelaProfesional = $_POST['escuela'] ?? ''; // AQUÍ CORREGIDO
    $respuestas = [
        $_POST['preg_1'],
        $_POST['preg_2'],
        $_POST['preg_3'],
        $_POST['preg_4'],
        $_POST['preg_5']
    ];
    $preg_6 = json_encode($_POST['preg_6'], JSON_UNESCAPED_UNICODE);
    $preg_7 = trim($_POST['preg_7']);

    if (!preg_match('/^[a-zA-Z0-9._%+-]+@undc\.edu\.pe$/', $correo)) {
        $msg = "Solo se permite correo institucional @undc.edu.pe";
        $tipoMsg = "error";
    } else {
        $stmt = $conexion->prepare("SELECT id FROM tutoria_encuesta_satisfaccion WHERE correo = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $msg = "Ya se ha registrado una respuesta con este correo.";
            $tipoMsg = "warning";
        } else {
            $stmt = $conexion->prepare("INSERT INTO tutoria_encuesta_satisfaccion 
                    (id_estu, correo, ciclo_academico, escuela_profesional, preg_1, preg_2, preg_3, preg_4, preg_5, preg_6, preg_7) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
           $stmt->bind_param("isssiiiisss", $idEstu, $correo, $ciclo, $escuelaProfesional,
                    $respuestas[0], $respuestas[1], $respuestas[2], $respuestas[3], $respuestas[4],
                    $preg_6, $preg_7);
            if ($stmt->execute()) {
                $msg = "¡Gracias! Tu respuesta fue registrada correctamente.";
                $tipoMsg = "success";
            } else {
                $msg = "Error al guardar la encuesta: " . $stmt->error;
                $tipoMsg = "error";
            }
        }
    }
}
// Obtener datos del estudiante (escuela, ciclo)
$sqlEstudiante = "
    SELECT cl.ciclo, ca.nom_car
    FROM asignacion_estudiante ae
    INNER JOIN carga_lectiva cl ON ae.id_cargalectiva = cl.id_cargalectiva
    INNER JOIN carrera ca ON cl.id_car = ca.id_car
    WHERE ae.id_estu = ? AND ae.id_semestre = ? AND cl.tipo = 'M'
    LIMIT 1
";

$datosEstudiante = $conexion->prepare($sqlEstudiante); 

if (!$datosEstudiante) {
    die("Error en prepare: " . $conexion->error); 
}

$datosEstudiante->bind_param("ii", $idEstu, $semestreActual); 
$datosEstudiante->execute();
$datosEstudiante->store_result();

if ($datosEstudiante->num_rows === 0) {
    echo "<div style='max-width:600px; margin:60px auto; background:white; padding:30px; border-radius:12px; text-align:center; box-shadow:0 0 10px rgba(0,0,0,0.2);'>
        <h2 style='color:#b02a37;'>No tienes cursos asignados en el presente semestre académico</h2>
        <p style='font-size:16px;'>Por lo tanto, no puedes responder la encuesta de satisfacción.</p>
        <a href='javascript:history.back()' style='margin-top:20px; display:inline-block; background:#0b7285; color:white; padding:10px 20px; border-radius:6px; text-decoration:none;'>Volver</a>
    </div>";
    exit;
}

$datosEstudiante->bind_result($cicloEstu, $escuelaProfesional); 
$datosEstudiante->fetch();
$datosEstudiante->close();

$docentes = [];

$sql = "
SELECT DISTINCT d.id_doce, d.nom_doce, d.apepa_doce, d.apema_doce, d.abreviatura_doce
FROM asignacion_estudiante ae
INNER JOIN carga_lectiva cl ON ae.id_cargalectiva = cl.id_cargalectiva
INNER JOIN docente d ON cl.id_doce = d.id_doce
WHERE ae.id_estu = ? AND ae.id_semestre = ? AND cl.tipo = 'M'
";

$stmtDoc = $conexion->prepare($sql);
if (!$stmtDoc) {
    die("Error en prepare: " . $conexion->error);
}

$stmtDoc->bind_param("ii", $idEstu, $semestreActual); 
if (!$stmtDoc->execute()) {
    die("Error al ejecutar consulta de docentes: " . $stmtDoc->error);
}

$result = $stmtDoc->get_result();
$docentes = [];
while ($row = $result->fetch_assoc()) {
    $docentes[] = $row;
}
$stmtDoc->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" type="image/png" href="../img/favicon.png" />
    <title>SISTECU - UNDC</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #e8f3f8;
            margin: 0;
            padding: 0;
        }
        .header {
            background: #0b4f6c;
            color: #fff;
            text-align: center;
            padding: 20px;
            position: relative;
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .header img {
            position: absolute;
            top: 10px;
            
            height: auto;
        }

        .header .logo-left {
            left: 20px;
            margin-top: 10px;
            width: 70px;
        }

        .header .logo-right {
            right: 20px;
            margin-top: 10px;
            width: 130px;
        }

        .header h1 {
            margin: 0;
            font-size: 25px;
        }

        .header h2 {
            margin: 5px 0 0 0;
            font-size: 15px;
            font-weight: normal;
            color:rgb(255, 255, 255);
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            padding: 35px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        input[type="email"],
        input[type="text"],
        select {
            width: 100%;
            padding: 8px 10px;
            height: 40px;
            margin-top: 6px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 15px;
            box-sizing: border-box;
            background-color: #f9f9f9;
        }
        h2 {
            text-align: center;
            color: #0b7285;
            margin-bottom: 30px;
            font-weight: 600;
            font-weight: bold;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #333;
        }
        input[type="email"],
        input[type="text"],
        select {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 15px;
        }
        .pregunta {
            margin-top: 20px;
            font-weight: bold;
            color: #444;
        }
        .opciones {
            display: flex;
            justify-content: space-between;
            margin: 10px 0 20px 0;
            gap: 8px;
        }
        .opciones label {
            flex: 1;
            text-align: center;
            background:rgb(219, 218, 218);
            padding: 8px;
            border-radius: 5px;
            cursor: pointer;
        }
        .opciones input[type="radio"] {
            margin-right: 5px;
        }
        .boton {
            background-color: #0b7285;
            color: white;
            padding: 12px;
            width: 100%;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            margin-top: 25px;
            cursor: pointer;
        }
        .boton:hover {
            background-color: #095b6b;
        }

        /* Estilo de Modal */
        .modal {
            display: <?= $msg ? 'flex' : 'none' ?>;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            justify-content: center;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .modal-content {
            background: white;
            padding: 25px 35px;
            border-radius: 10px;
            max-width: 450px;
            text-align: center;
            position: relative;
            font-size: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .modal.success .modal-content { border-left: 6px solid #2ecc71; }
        .modal.error .modal-content { border-left: 6px solid #e74c3c; }
        .modal.warning .modal-content { border-left: 6px solid #f1c40f; }

        .modal .close {
            position: absolute;
            right: 12px;
            top: 10px;
            font-size: 20px;
            cursor: pointer;
            color: #888;
        }
        .nombre-docente {
            display: block;
            font-weight: bold;
            background: #e3f6ff;
            color: #0b7285;
            padding: 6px 10px;
            border-left: 4px solid #0b7285;
            border-radius: 6px;
            margin: 20px 0 10px;
        }

        @media(max-width: 600px){
            .opciones { flex-direction: column; align-items: flex-start; }
            .opciones label { text-align: left; }
        }
    </style>
</head>
<body>
<div class="header">
    <img src="../img/logo-uni.png" alt="Logo Izquierdo" class="logo-left">
    <img src="../Login/images/logo_sistecu2.png" alt="Logo Derecho" class="logo-right">
    <h1>UNIVERSIDAD NACIONAL DE CAÑETE</h1>
    <h2>Encuesta de Satisfacción del Programa de Tutoría y Consejería Universitaria</h2>
</div>
<div class="container">
    <form method="post">
        <div style="display: flex; gap: 20px;">
            <div style="flex: 1;">
                <label>Correo Institucional:</label>
                <input type="email" name="correo" required 
                value="<?= htmlspecialchars($correoEstudiante) ?>" readonly
                style="background-color: #e9e9e9; border: none; border-radius: 6px; width: 100%; padding: 10px; font-size: 14px;" />
            </div>
            <div style="flex: 1;">
                <label>Ciclo Académico:</label>
                <input type="text" name="ciclo_academico" id="ciclo_academico"
                value="<?= htmlspecialchars($cicloEstu) ?>" readonly
                style="background-color: #e9e9e9; border: none; border-radius: 6px; width: 100%; padding: 10px; font-size: 14px;" />
            </div>
        </div>
        <label>Escuela Profesional:</label>
        <input type="text" value="<?= htmlspecialchars($escuelaProfesional) ?>" readonly 
        style="background-color: #e9e9e9; border: none; border-radius: 6px; width: 100%; padding: 10px; font-size: 14px;" />
        <input type="hidden" name="escuela" value="<?= htmlspecialchars($escuelaProfesional) ?>">
        <?php
        // Preguntas 1 a 5
        $preguntas = [
            "1. ¿Cómo percibe las tutorías y consejerías realizadas en el semestre?",
            "2. ¿Considera que los temas abordados fueron de utilidad?",
            "3. ¿Qué tan satisfecho(a) está con la frecuencia y duración de las tutorías y consejerías?",
            "4. ¿Qué tan satisfecho(a) se siente después de asistir a las tutorías y consejerías?",
            "5. ¿Cómo calificarías la calidad de la retroalimentación que recibes de tus docentes durante las tutorías y consejerías?"
        ];

        $opciones = [1 => "Muy insatisfecho", 2 => "Insatisfecho", 3 => "Indiferente", 4 => "Satisfecho", 5 => "Muy satisfecho"];

        foreach ($preguntas as $i => $texto) {
            echo "<div class='pregunta'>$texto</div><div class='opciones'>";
            foreach ($opciones as $val => $etiqueta) {
                echo "<label><input type='radio' name='preg_" . ($i+1) . "' value='$val' required> $etiqueta</label>";
            }
            echo "</div>";
        }

        // Pregunta 6: dinámica por docente
        echo "<div class='pregunta'>6. ¿Cómo considera las tutorías realizadas por sus docentes a cargo de los cursos actuales?</div>";

        if (empty($docentes)) {
            echo "<p style='color:#b02a37; font-weight:bold;'>No tienes docentes asignados en el presente semestre académico.</p>";
        } else {
            foreach ($docentes as $docente) {
                $id_doce = $docente['id_doce'];
                $nombre = trim($docente['abreviatura_doce'] . ' ' . $docente['nom_doce'] . ' ' . $docente['apepa_doce'] . ' ' . $docente['apema_doce']);

                echo "<div class='nombre-docente'>• $nombre</div><div class='opciones'>";
                foreach ($opciones as $val => $etiqueta) {
                    echo "<label><input type='radio' name='preg_6[$id_doce]' value='$val' required> $etiqueta</label>";
                }
                echo "</div>";
            }
        }

        // Pregunta 7: abierta
        echo "<div class='pregunta'>7. ¿Qué considera que se pueda mejorar en el programa de Tutoría y Consejería Universitaria? </div><br>";
        echo "<textarea name='preg_7' rows='4' style='width:100%; border-radius:6px; border:1px solid #ccc;' required></textarea>";
        ?>
        <!-- <p style='color: red'>*</p> -->
        <button type="submit" class="boton"><strong>ENVIAR RESPUESTA</strong></button>
    </form>
</div>

<!-- MODAL -->
<?php if ($msg): ?>
<div class="modal <?= $tipoMsg ?>">
    <div class="modal-content">
        <span class="close" onclick="document.querySelector('.modal').style.display='none';">&times;</span>
        <p><?= htmlspecialchars($msg) ?></p>

        <?php if ($tipoMsg === 'success'): ?>
            <a href="https://sisacademico.undc.edu.pe/" 
               style="margin-top: 15px; display: inline-block; background: #0b7285; color: #fff; padding: 8px 20px; border-radius: 6px; text-decoration: none;">
                👉 IR AL SISTEMA ACADÉMICO
            </a>
        <?php endif; ?>
        <br>
        <?php if ($tipoMsg === 'success'): ?>
            <a href="https://tutoria.undc.edu.pe/" 
               style="margin-top: 15px; display: inline-block; background: #0b7285; color: #fff; padding: 8px 20px; border-radius: 6px; text-decoration: none;">
                👉 VOLVER AL SISTEMA DE TUTORÍA
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>


</body>
</html>
