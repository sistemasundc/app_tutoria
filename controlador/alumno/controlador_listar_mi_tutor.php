<?php
session_start();
require '../../modelo/modelo_alumno.php';
$MU = new Alumno();

$id_alumno = htmlspecialchars($_POST['idalumno'], ENT_QUOTES, 'UTF-8');
$semestre = $_SESSION['S_SEMESTRE'];

$consulta = $MU->listar_mi_tutor($id_alumno, $semestre);

$data = json_encode($consulta);
if (count($consulta) > 0) {
    echo $data;
} else {
    echo 0;
}
?>


