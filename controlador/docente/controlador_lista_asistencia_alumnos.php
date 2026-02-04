<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
session_start();

require_once('../../modelo/modelo_conexion.php');
require_once('../../modelo/modelo_docente.php');

$docente = new Docente();

$id_sesion = isset($_POST['id']) ? htmlspecialchars($_POST['id'], ENT_QUOTES, 'UTF-8') : null;
$id_carga  = isset($_POST['id_carga']) ? htmlspecialchars($_POST['id_carga'], ENT_QUOTES, 'UTF-8') : null;

if (!$id_sesion || !$id_carga) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}
//  Enviamos ambos al modelo
$consulta = $docente->ListaAsistenciaAlumnos_TC($id_carga, $id_sesion);

if ($consulta === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la consulta']);
    exit;
}
echo json_encode($consulta, JSON_UNESCAPED_UNICODE);
