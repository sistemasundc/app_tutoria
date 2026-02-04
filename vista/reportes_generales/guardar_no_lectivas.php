<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');

$conexion = new conexion();
$conexion->conectar();

date_default_timezone_set('America/Lima');

/* === Parámetros === */
$id_semestre = $_SESSION['S_SEMESTRE'] ?? null;
$id_usuario  = intval($_SESSION['S_IDUSUARIO'] ?? 0); // opcional
$id_doce     = isset($_POST['id_doce']) ? intval($_POST['id_doce']) : null;
$horas       = isset($_POST['horas'])   ? floatval($_POST['horas'])   : null;
$mes         = $_POST['mes'] ?? null; // no se usa para guardar horas, se mantiene por compatibilidad
$id_car      = isset($_POST['id_car']) ? intval($_POST['id_car']) : null; // opcional

if (!$id_semestre || !$id_doce || $horas === null) {
    http_response_code(400);
    echo "Parámetros incompletos.";
    exit;
}

/* Si no llega id_car, lo inferimos desde tutoria_docente_asignado (rol 6 = tutor de aula) */
if (!$id_car) {
    $sqlCar = "SELECT id_car
               FROM tutoria_docente_asignado
               WHERE id_doce = ? AND id_semestre = ? AND id_rol = 6
               ORDER BY id_car ASC LIMIT 1";
    $stmtCar = $conexion->conexion->prepare($sqlCar);
    $stmtCar->bind_param("ii", $id_doce, $id_semestre);
    $stmtCar->execute();
    $resCar = $stmtCar->get_result();
    if ($row = $resCar->fetch_assoc()) {
        $id_car = intval($row['id_car']);
    } else {
        http_response_code(400);
        echo "No se pudo determinar la carrera (id_car) para el docente.";
        exit;
    }
}

/* Normaliza horas */
$horas_semanales  = max(0, round($horas, 2));
$horas_mensuales  = round($horas_semanales * 4, 2); // misma regla que el reporte

/* Insert/Update en tutoria_control_horas_aula */
$sql = "
INSERT INTO tutoria_control_horas_aula
    (id_doce, id_car, id_semestre, horas_semanales, horas_mensuales, fuente, observacion, id_usuario, updated_at)
VALUES
    (?, ?, ?, ?, ?, 'manual', NULL, ?, NOW())
ON DUPLICATE KEY UPDATE
    horas_semanales = VALUES(horas_semanales),
    horas_mensuales = VALUES(horas_mensuales),
    fuente          = VALUES(fuente),
    id_usuario      = VALUES(id_usuario),
    updated_at      = VALUES(updated_at)
";

$stmt = $conexion->conexion->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo "Error en prepare(): " . $conexion->conexion->error;
    exit;
}

$stmt->bind_param(
    "iiiddi",                  // i,i,i,d,d,i
    $id_doce,
    $id_car,
    $id_semestre,
    $horas_semanales,
    $horas_mensuales,
    $id_usuario
);

if ($stmt->execute()) {
    echo "OK";
} else {
    http_response_code(500);
    echo "Error al guardar: " . $stmt->error;
}
