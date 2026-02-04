<?php
session_start();
require '../../modelo/modelo_docente.php';
$docent = new Docente();

$tema = htmlspecialchars($_POST['tema'], ENT_QUOTES, 'UTF-8');
$compromiso = htmlspecialchars($_POST['compromiso'], ENT_QUOTES, 'UTF-8');
$tipo_session = htmlspecialchars($_POST['tipo'], ENT_QUOTES, 'UTF-8');
$obser = htmlspecialchars($_POST['obs'], ENT_QUOTES, 'UTF-8');
$reu_otros = htmlspecialchars($_POST['reu_otro'], ENT_QUOTES, 'UTF-8');
$id_registro = htmlspecialchars($_POST['id_update'], ENT_QUOTES, 'UTF-8');

$array_asig = htmlspecialchars($_POST['array_asig'], ENT_QUOTES, 'UTF-8');
$array_asignacion = explode(",", $array_asig);

$consulta = $docent->ActualizarSessionTutoria($tema, $compromiso, $tipo_session, $obser, $reu_otros, $id_registro);

if (!empty($array_asig)) {
    if ($consulta == 1) {
        for ($i = 0; $i < count($array_asignacion); $i++) {
            $consulta = $docent->RegistrarDetalleSessionTutoria($array_asignacion[$i], $id_registro);
        }
    }
}

echo $consulta;
