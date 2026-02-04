<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');

$conexion = new conexion();
$conexion->conectar();

date_default_timezone_set('America/Lima');

/* Sesión actual */
$id_semestre = isset($_SESSION['S_SEMESTRE']) ? (int)$_SESSION['S_SEMESTRE'] : 0;
$id_car      = isset($_SESSION['S_SCHOOL'])   ? (int)$_SESSION['S_SCHOOL']   : 0;
$id_usuario  = isset($_SESSION['S_IDUSUARIO'])? (int)$_SESSION['S_IDUSUARIO']: null;

/* POST */
$id_doce = isset($_POST['id_doce']) ? (int)$_POST['id_doce'] : 0;
$valor   = isset($_POST['horas'])   ? trim((string)$_POST['horas']) : '';
$unidad  = $_POST['unidad'] ?? 'horas';   // 'horas' (académicas) o 'min'
$mes     = $_POST['mes'] ?? null;         // solo compatibilidad

if (!$id_semestre || !$id_car || !$id_doce || $valor === '') {
    http_response_code(400);
    echo "Parámetros incompletos.";
    exit;
}

/* Conversión a horas académicas (1h = 45min) */
if ($unidad === 'min') {
    $minutos = max(0, (float)$valor);
    $horas_semanales = round($minutos / 45, 2);
} else {
    $horas_semanales = max(0, (float)$valor);
}
if ($horas_semanales > 60) $horas_semanales = 60.0;

$horas_mensuales = round($horas_semanales * 4, 2);
$fuente = 'manual';

/* Validar que el docente pertenece a la carrera/semestre actual */
$sqlVal = "SELECT 1
           FROM tutoria_docente_asignado
           WHERE id_doce = ? AND id_car = ? AND id_semestre = ?
           LIMIT 1";
$stmtVal = $conexion->conexion->prepare($sqlVal);
if (!$stmtVal) {
    http_response_code(500);
    echo "Error en prepare() de validación: " . $conexion->conexion->error;
    exit;
}
$stmtVal->bind_param("iii", $id_doce, $id_car, $id_semestre);
$stmtVal->execute();
if ($stmtVal->get_result()->num_rows === 0) {
    http_response_code(403);
    echo "El docente no pertenece a la carrera/semestre actual.";
    exit;
}

/* UPSERT en tutoria_control_horas_aula */
$sql = "INSERT INTO tutoria_control_horas_aula
          (id_doce, id_car, id_semestre, horas_semanales, horas_mensuales, fuente, observacion, id_usuario)
        VALUES
          (?, ?, ?, ?, ?, ?, NULL, ?)
        ON DUPLICATE KEY UPDATE
          horas_semanales = VALUES(horas_semanales),
          horas_mensuales = VALUES(horas_mensuales),
          fuente          = VALUES(fuente),
          id_usuario      = VALUES(id_usuario)";
$stmt = $conexion->conexion->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo "Error en prepare(): " . $conexion->conexion->error;
    exit;
}
$stmt->bind_param(
    "iiiddsi",
    $id_doce,
    $id_car,
    $id_semestre,
    $horas_semanales,
    $horas_mensuales,
    $fuente,
    $id_usuario
);

if ($stmt->execute()) {
    echo "OK";
} else {
    http_response_code(500);
    echo "Error al guardar: " . $stmt->error;
}
