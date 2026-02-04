<?php
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
date_default_timezone_set('America/Lima');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_FILES['evidencias']) || !isset($_POST['id_sesion'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
    exit;
}

$id_sesion = intval($_POST['id_sesion']);
$archivos = $_FILES['evidencias'];
$ruta_destino = __DIR__ . '/../evidencias_sesion/'; // Ruta absoluta al directorio de imágenes

if (!is_dir($ruta_destino)) {
    mkdir($ruta_destino, 0755, true); // crear si no existe
}

require_once('../modelo/modelo_conexion.php');
$conexion = new conexion();
$cn = $conexion->conectar();

$errores = [];
$total = count($archivos['name']);

for ($i = 0; $i < $total && $i < 2; $i++) {
    $nombre_archivo = time() . '_' . basename($archivos['name'][$i]);
    $ruta_archivo = $ruta_destino . $nombre_archivo;
    $tipo = mime_content_type($archivos['tmp_name'][$i]);

    if (!in_array($tipo, ['image/jpeg', 'image/png'])) {
        $errores[] = "Archivo {$archivos['name'][$i]} no es válido.";
        continue;
    }

    if ($archivos['size'][$i] > 5 * 1024 * 1024) {
        $errores[] = "Archivo {$archivos['name'][$i]} supera el tamaño permitido.";
        continue;
    }

    if (move_uploaded_file($archivos['tmp_name'][$i], $ruta_archivo)) {
        $stmt = $cn->prepare("INSERT INTO tutoria_evidencias_sesion (sesiones_tutoria_id, archivo) VALUES (?, ?)");
        $stmt->bind_param("is", $id_sesion, $nombre_archivo);
        if (!$stmt->execute()) {
            $errores[] = "Error al registrar {$nombre_archivo} en la base de datos.";
        }
    } else {
        $errores[] = "No se pudo mover el archivo {$archivos['name'][$i]}.";
    }
}

if (empty($errores)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'messages' => $errores]);
}