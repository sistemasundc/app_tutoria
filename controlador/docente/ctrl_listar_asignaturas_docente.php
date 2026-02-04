<?php 
session_start();
require_once("../../modelo/modelo_conexion.php");

if (!isset($_SESSION['S_IDUSUARIO']) || $_SESSION['S_ROL'] !== 'DOCENTE') {
    die(json_encode([]));
}

$conexion = new conexion();
$conexion->conectar();

$id_doce = $_SESSION['S_IDUSUARIO'];

$sql = "SELECT 
            a.nom_asi, 
            cl.ciclo, 
            cl.turno, 
            cl.seccion, 
            cl.id_cargalectiva
        FROM carga_lectiva cl
        JOIN asignatura a ON cl.id_asi = a.id_asi
        WHERE cl.id_doce = ? AND cl.id_semestre = 33";

$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("i", $id_doce);
$stmt->execute();
$result = $stmt->get_result();

$asignaturas = [];
while ($fila = $result->fetch_assoc()) {
    $asignaturas[] = $fila;
}

echo json_encode($asignaturas);
?>
