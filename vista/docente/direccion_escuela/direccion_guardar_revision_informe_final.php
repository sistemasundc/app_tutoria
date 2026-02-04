<?php
require_once(__DIR__ . "/../../modelo/modelo_conexion.php");
date_default_timezone_set('America/Lima');
session_start();

$semestre = $_SESSION['S_SEMESTRE'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_plan_tutoria = intval($_POST['id_plan_tutoria']);
    $id_cargalectiva = intval($_POST['id_cargalectiva']);
    $id_director     = intval($_POST['id_director']);
    $estado          = trim($_POST['accion']);
    $comentario      = trim($_POST['comentario'] ?? '');

    $conexion = new conexion();
    $conexion->conectar();

    // Verificar si ya existe revisión
    $verifica = $conexion->conexion->prepare("
        SELECT 1 FROM tutoria_revision_director_informe_final
        WHERE id_cargalectiva = ? AND id_director = ? AND id_semestre = ?
        LIMIT 1
    ");
    $verifica->bind_param("iii", $id_cargalectiva, $id_director, $semestre);
    $verifica->execute();
    $verifica->store_result();

    if ($verifica->num_rows === 0) {
        // Obtener datos del director
        $sql_datos = "SELECT CONCAT_WS(' ', grado, nombres, apaterno, amaterno) AS nombre_director, id_car 
                      FROM tutoria_usuario WHERE id_usuario = ?";
        $stmt_datos = $conexion->conexion->prepare($sql_datos);
        $stmt_datos->bind_param("i", $id_director);
        $stmt_datos->execute();
        $stmt_datos->bind_result($nombre_director, $id_car);
        $stmt_datos->fetch();
        $stmt_datos->close();

        // Insertar revisión
        $sql = "INSERT INTO tutoria_revision_director_informe_final 
                (id_cargalectiva, id_director, estado_revision, comentario, fecha_revision, nombre_director, id_car, id_semestre)
                VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)";
        $stmt = $conexion->conexion->prepare($sql);
        $stmt->bind_param("iisssii", 
            $id_cargalectiva,
            $id_director,
            $estado,
            $comentario,
            $nombre_director,
            $id_car,
            $semestre
        );

        if ($stmt->execute()) {
            echo "<script>alert('✅ Revisión registrada correctamente'); window.location.href = '../index.php?pagina=direccion_escuela/direccion_informe_final_aula.php';</script>";
        } else {
            $errorMsg = json_encode('❌ Error MySQL: ' . $stmt->error);
            echo "<script>alert($errorMsg); history.back();</script>";
        }
    } else {
        echo "<script>alert('⚠️ Ya se ha registrado una revisión para este informe.'); history.back();</script>";
    }
}
?>
