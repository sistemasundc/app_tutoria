<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/../../modelo/modelo_conexion.php');
$conexion = new conexion();
$cn = $conexion->conectar();

$id_car_sesion = $_SESSION['S_SCHOOL'] ?? null;
if (!$id_car_sesion) {
  echo json_encode(['ok' => false, 'msg' => 'No se detectó la carrera (sesión).']);
  exit;
}

$anio_actual = (int)date('Y');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : $anio_actual;
if ($anio < 2000 || $anio > ($anio_actual + 1)) $anio = $anio_actual;

$YEAR_EXPR = "
CASE
  WHEN fecha_envio IS NOT NULL THEN YEAR(fecha_envio)
  ELSE CAST(REGEXP_SUBSTR(numero_informe, '20[0-9]{2}') AS UNSIGNED)
END
";

$sql = "
  SELECT LOWER(mes_informe) AS mes, COUNT(*) AS cantidad
  FROM tutoria_informe_mensual
  WHERE estado_envio = 2
    AND id_car = ?
    AND ($YEAR_EXPR) = ?
  GROUP BY LOWER(mes_informe)
";

$stmt = $cn->prepare($sql);
if (!$stmt) {
  echo json_encode(['ok' => false, 'msg' => 'Error prepare: ' . $cn->error]);
  exit;
}

$stmt->bind_param("ii", $id_car_sesion, $anio);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
  $data[$row['mes']] = (int)$row['cantidad'];
}
$stmt->close();

echo json_encode([
  'ok' => true,
  'anio' => $anio,
  'data' => $data
], JSON_UNESCAPED_UNICODE);
