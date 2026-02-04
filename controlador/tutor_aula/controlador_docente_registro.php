<?php
require_once '../../modelo/modelo_docente.php'; // corrige ruta si es necesario
$docent = new Docente();

date_default_timezone_set('America/Lima');

$id_docente = htmlspecialchars($_POST['iddoce'], ENT_QUOTES, 'UTF-8');
$id_coor = htmlspecialchars($_POST['coor'], ENT_QUOTES, 'UTF-8');
$semestre = htmlspecialchars($_POST['anio'], ENT_QUOTES, 'UTF-8');
$fecha = date('Y-m-d');

$consulta = $docent->Registrar_Docente($id_docente, $fecha, $semestre, $id_coor);
echo $consulta;
?>
