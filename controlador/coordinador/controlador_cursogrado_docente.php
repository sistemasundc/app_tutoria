<?php
session_start();
require '../../modelo/modelo_coordinador.php';
$MU = new Modelo_Coordinador();

$id_carga = htmlspecialchars($_POST['id_carga'], ENT_QUOTES, 'UTF-8');
$id_doce = htmlspecialchars($_POST['id_doce'], ENT_QUOTES, 'UTF-8');
$id_coodi = htmlspecialchars($_POST['id_coodi'], ENT_QUOTES, 'UTF-8');
$semestre = htmlspecialchars($_POST['anio'], ENT_QUOTES, 'UTF-8');

$consulta = $MU->VerificarDocenteAsignado($id_carga, $id_doce, $semestre);

if ($consulta == 0) {

    $consulta = $MU->Docente_Asignado($id_carga, $id_doce, $id_coodi);
    echo $consulta;
} else {
    echo 555;
}
