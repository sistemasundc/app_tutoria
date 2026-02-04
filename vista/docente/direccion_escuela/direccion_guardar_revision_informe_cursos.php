<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Lima');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once(__DIR__ . "/../../modelo/modelo_conexion.php");

if (!isset($_SESSION['S_IDUSUARIO']) || $_SESSION['S_ROL'] !== 'DIRECCION DE ESCUELA') {
    die('Acceso denegado');
}

// === Validar datos POST ===
$id_cargalectiva = $_POST['id_cargalectiva'] ?? null;
$mes_informe     = strtolower(trim($_POST['mes_informe'] ?? ''));
$id_director     = $_POST['id_director'] ?? null;
$accion          = $_POST['accion'] ?? '';
$comentario      = trim($_POST['comentario'] ?? '');

if (!$id_cargalectiva || !$mes_informe || !$id_director || $accion !== 'CONFORME') {
    die("Datos incompletos o inválidos.");
}

$conexion = new conexion();
$conexion->conectar();

// === Obtener nombre del director e id_car ===
$sql_datos = "SELECT CONCAT_WS(' ', grado, nombres, apaterno, amaterno) AS nombre_director, id_car
              FROM tutoria_usuario
              WHERE id_usuario = ?";
$stmt_datos = $conexion->conexion->prepare($sql_datos);
$stmt_datos->bind_param("i", $id_director);
$stmt_datos->execute();
$stmt_datos->bind_result($nombre_director, $id_car);
$stmt_datos->fetch();
$stmt_datos->close();

// === Verificar si ya existe la revisión ===
$sql_check = "SELECT COUNT(*) AS existe 
              FROM tutoria_revision_director_informe_curso 
              WHERE id_cargalectiva = ? AND LOWER(mes_informe) = ? AND id_director = ?";
$stmt_check = $conexion->conexion->prepare($sql_check);
$stmt_check->bind_param("isi", $id_cargalectiva, $mes_informe, $id_director);
$stmt_check->execute();
$res_check = $stmt_check->get_result()->fetch_assoc();
$existe = $res_check['existe'] ?? 0;

if ($existe > 0) {
    // Actualiza si ya existe
    $sql_update = "UPDATE tutoria_revision_director_informe_curso 
                   SET estado_revision = 'CONFORME', 
                       comentario = ?, 
                       fecha_revision = NOW(),
                       nombre_director = ?, 
                       id_car = ?
                   WHERE id_cargalectiva = ? AND LOWER(mes_informe) = ? AND id_director = ?";
    $stmt_update = $conexion->conexion->prepare($sql_update);
    $stmt_update->bind_param("ssiisi", $comentario, $nombre_director, $id_car, $id_cargalectiva, $mes_informe, $id_director);
    $stmt_update->execute();
} else {
    // Inserta si no existe
    $sql_insert = "INSERT INTO tutoria_revision_director_informe_curso
                   (id_cargalectiva, mes_informe, id_director, estado_revision, comentario, fecha_revision, nombre_director, id_car)
                   VALUES (?, ?, ?, 'CONFORME', ?, NOW(), ?, ?)";
    $stmt_insert = $conexion->conexion->prepare($sql_insert);
    $stmt_insert->bind_param("isissi", $id_cargalectiva, $mes_informe, $id_director, $comentario, $nombre_director, $id_car);
    $stmt_insert->execute();
}

$conexion->cerrar();

// Redirigir con éxito
header("Location: ../index.php?pagina=direccion_escuela/direccion_informe_mensual_cursos.php&id_cargalectiva=$id_cargalectiva&mes=$mes_informe&confirmado=1");
exit;
?>
