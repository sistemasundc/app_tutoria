<?php  
ini_set("display_errors", 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start(); 
date_default_timezone_set('America/Lima');

require_once("../../modelo/modelo_conexion.php");

// Verifica rol
if (!isset($_SESSION['S_IDUSUARIO']) || $_SESSION['S_ROL'] !== 'COORDINADOR GENERAL DE TUTORIA') {
    die('Acceso denegado');
}

// === VARIABLES POST ===
$accion           = $_POST['accion'] ?? 'guardar';
$numero_informe   = trim($_POST['numero_informe'] ?? '');
$mes_informe      = strtolower(trim($_POST['mes_informe'] ?? ''));
$para_vpa         = trim($_POST['para_vpa'] ?? '');
$de_coordinador   = trim($_POST['de_coordinador'] ?? '');
$asunto           = trim($_POST['asunto'] ?? '');
$no_lectivas      = trim($_POST['no_lectivas'] ?? '');
$fecha_registro   = date("Y-m-d");
$guardado         = 1;

// Validación obligatoria
if (empty($numero_informe) || empty($mes_informe)) {
    echo "FALTAN DATOS OBLIGATORIOS";
    var_dump($_POST);
    exit;
}

// Conexión
$conexion = new conexion();
$conexion->conectar();

// Verifica si ya existe el informe para ese mes
$sqlCheck = "SELECT id_informe FROM tutoria_informe_resultados_coordinador_general WHERE mes_informe = ?";
$stmtCheck = $conexion->conexion->prepare($sqlCheck);
if (!$stmtCheck) {
    die("Error en prepare SQLCheck: " . $conexion->conexion->error);
}
$stmtCheck->bind_param("s", $mes_informe);
$stmtCheck->execute();
$res = $stmtCheck->get_result();
$existe = $res->fetch_assoc();

if ($existe) {
    // Actualiza
    $sqlUpdate = "UPDATE tutoria_informe_resultados_coordinador_general
                  SET numero_informe = ?, para_vpa = ?, de_coordinador = ?, asunto = ?, no_lectivas = ?, fecha_registro = ?, guardado = ?
                  WHERE mes_informe = ?";
    $stmt = $conexion->conexion->prepare($sqlUpdate);
    if (!$stmt) {
        die("Error en UPDATE: " . $conexion->conexion->error);
    }
    $stmt->bind_param("ssssssis", $numero_informe, $para_vpa, $de_coordinador, $asunto, $no_lectivas, $fecha_registro, $guardado, $mes_informe);
    $stmt->execute();
} else {
    // Inserta
    $sqlInsert = "INSERT INTO tutoria_informe_resultados_coordinador_general
                  (numero_informe, mes_informe, para_vpa, de_coordinador, asunto, no_lectivas, fecha_registro, guardado)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->conexion->prepare($sqlInsert);
    if (!$stmt) {
        die("Error en INSERT: " . $conexion->conexion->error);
    }
    $stmt->bind_param("sssssssi", $numero_informe, $mes_informe, $para_vpa, $de_coordinador, $asunto, $no_lectivas, $fecha_registro, $guardado);
    $stmt->execute();
}

// Redirige al formulario
header("Location: ../index.php?pagina=reportes/admin_form_informe_mensual.php&mes=" . urlencode($mes_informe));
exit;
?>