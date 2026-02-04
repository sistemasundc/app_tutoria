<?php
require_once("../../modelo/modelo_conexion.php");
$conexion = new conexion();
$conexion->conectar();

$id_cargalectiva = $_GET['id_cargalectiva'] ?? null;
$mes = $_GET['mes'] ?? '';

if (!$id_cargalectiva || !$mes) {
    echo json_encode(['existe' => false]);
    exit;
}

$sql = "SELECT 1 FROM tutoria_informe_mensual 
        WHERE id_cargalectiva = ? AND mes_informe = ? AND estado_envio = 2 LIMIT 1";
$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("is", $id_cargalectiva, $mes);
$stmt->execute();
$res = $stmt->get_result();

echo json_encode(['existe' => $res->num_rows > 0]);
?>