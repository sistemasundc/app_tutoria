<?php   
require_once(__DIR__ . "/../../modelo/modelo_conexion.php");
date_default_timezone_set('America/Lima'); // Establecer zona horaria correcta

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

$semestre = $_SESSION['S_SEMESTRE'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_plan         = $_POST['id_plan_tutoria'];
    $id_cargalectiva = $_POST['id_cargalectiva'];
    $id_director     = $_POST['id_director'];
    $estado          = $_POST['accion'];
    $comentario      = trim($_POST['comentario'] ?? '');
    $mes             = strtolower(trim($_POST['mes_informe'] ?? ''));

    // Obtener la hora actual desde PHP (zona horaria Lima)
    $fecha_revision = date('Y-m-d H:i:s');

    $conexion = new conexion();
    $conexion->conectar();

    // Verificar si ya existe una revisión para ese mes, carga y director
    $verifica = $conexion->conexion->prepare("
        SELECT 1 FROM tutoria_revision_director_informe 
        WHERE id_plan_tutoria = ? AND id_cargalectiva = ? AND id_director = ? AND LOWER(mes_informe) = ?
        LIMIT 1
    ");
    $verifica->bind_param("iiis", $id_plan, $id_cargalectiva, $id_director, $mes);
    $verifica->execute();
    $verifica->store_result();

    if ($verifica->num_rows === 0) {
        // Obtener datos del director (nombre y carrera)
        $sql_datos = "SELECT CONCAT_WS(' ', grado, nombres, apaterno, amaterno) AS nombre_director, id_car 
                      FROM tutoria_usuario WHERE id_usuario = ?";
        $stmt_datos = $conexion->conexion->prepare($sql_datos);
        $stmt_datos->bind_param("i", $id_director);
        $stmt_datos->execute();
        $stmt_datos->bind_result($nombre_director, $id_car);
        $stmt_datos->fetch();
        $stmt_datos->close();

        // Insertar nueva revisión con la fecha desde PHP
        $sql = "INSERT INTO tutoria_revision_director_informe 
                (id_plan_tutoria, id_cargalectiva, id_director, mes_informe, estado_revision, comentario, fecha_revision, nombre_director, id_car, id_semestre)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->conexion->prepare($sql);
        $stmt->bind_param("iiisssssii", 
            $id_plan, 
            $id_cargalectiva, 
            $id_director, 
            $mes, 
            $estado, 
            $comentario, 
            $fecha_revision, 
            $nombre_director, 
            $id_car, 
            $semestre
        );

        if ($stmt->execute()) {
            echo "<script>
                alert('Revisión registrada correctamente');
                window.location.href = '../index.php?pagina=direccion_escuela/direccion_informe_mensual_aula.php&mes={$mes}';
            </script>";
        } else {
            echo "<script>
                alert('Error al registrar revisión');
                history.back();
            </script>";
        }
    } else {
        echo "<script>
            alert('Ya se ha registrado la revisión para este informe.');
            history.back();
        </script>";
    }
}
?>
