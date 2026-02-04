<?php
session_start();
require '../..//modelo/modelo_docente.php';
$docent = new Docente();
date_default_timezone_set('America/Lima');

$id_docente   = isset($_POST['iddoce']) ? (int)$_POST['iddoce'] : 0;
// puedes seguir usando el coordinador que envías por POST...
$id_doc_asig  = isset($_POST['coor']) ? (int)$_POST['coor'] : 0;
// ...o si prefieres solo sesión, usa: $id_doc_asig = (int)($_SESSION['S_IDUSUARIO'] ?? 0);

$semestre     = (int)($_SESSION['S_SEMESTRE'] ?? 0);   // <-- de sesión
$id_car       = (int)($_SESSION['S_SCHOOL']   ?? 0);   // <-- de sesión
$fecha        = date('Y-m-d');

// Validación mínima
if ($id_docente <= 0 || $id_doc_asig <= 0 || $semestre <= 0 || $id_car <= 0) {
    echo 0;
    exit;
}

// Llama al modelo con los nuevos parámetros
$consulta = $docent->Registrar_Docente($id_docente, $fecha, $semestre, $id_doc_asig, $id_car);
echo $consulta;
?>

